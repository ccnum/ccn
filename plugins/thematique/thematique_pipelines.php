<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('action/editer_liens');
// thematique_annee_scolaire() est normalement auto-incluse via thematique_fonctions.php,
// mais ce n'est pas garanti dans tous les contextes d'appel (ex: pipeline cioidc_userinfo
// déclenché depuis une action hors squelette), d'où cet include explicite.
include_spip('thematique_fonctions');
// Fonctions thematique_cioidc_*, utilisées uniquement par thematique_cioidc_userinfo()
// ci-dessous : pas auto-incluses (fichier sous inc/), à charger explicitement.
include_spip('inc/thematique_cioidc');

/**
 * Calcule le rôle (prof/intervenant/admin/eleve) et l'avatar de session à
 * chaque écriture du fichier de session (pipeline preparer_fichier_session),
 * plutôt que de le recalculer dans chaque squelette qui en a besoin via
 * #SESSION_SET{role, #SESSION{id_auteur}|thematique_donner_role}.
 *
 * Accroché à preparer_fichier_session (déclenché par ajouter_session() à
 * chaque écriture du fichier de session : connexion, ou tout session_set())
 * plutôt qu'à preparer_visiteur_session (déclenché uniquement dans
 * auth_init_droits(), donc seulement pour un accès à /ecrire ou le
 * formulaire de connexion natif SPIP, jamais pour la connexion SSO
 * utilisée par la quasi-totalité des comptes réels). preparer_visiteur_session
 * a l'inconvénient supplémentaire de ne jamais réécrire son résultat dans
 * le fichier de session (cf le commentaire "qui ne figure pas dans le
 * fichier de session" dans ecrire/inc/auth.php) : il ne survivait donc que
 * le temps de la requête en cours. Sans ce hook sur preparer_fichier_session,
 * #SESSION{role} restait vide sur toutes les pages publiques pour un compte
 * connecté via l'ENT (cioidc_session() contourne auth_loger() et écrit la
 * session directement via ajouter_session()) : la sidebar "mission"
 * retombait toujours sur le fond consigne_pour_autre, même pour un
 * intervenant ou un admin correctement reconnus côté base.
 *
 * Expose aussi #SESSION{avatar} pour le menu haut : soit une URL de photo
 * laclasse.com (champ extra 'avatar' de spip_auteurs, alimenté par
 * thematique_cioidc_userinfo() via le SSO ENT), soit l'emoji de la classe
 * pour un prof (thematique_avatar_animal(), prioritaire sur la photo ENT —
 * "l'icône classe est son avatar" même s'il a une photo dans l'ENT), soit
 * vide (repli SVG géré côté squelette). Un seul SESSION{avatar} : c'est au
 * squelette de distinguer URL/emoji (cf authentification.html).
 *
 * @param array $flux
 * @return array
 */
function thematique_preparer_fichier_session($flux) {
	$id_auteur = intval($flux['data']['id_auteur'] ?? 0);
	$role = thematique_donner_role($id_auteur);
	$flux['data']['role'] = $role;

	if ($role === 'prof' && $animal = thematique_avatar_animal($id_auteur)) {
		$flux['data']['avatar'] = $animal;
	}
	return $flux;
}

// Pre_boucles
// Retourne les articles et articles syndiqués en lien avec l'année scolaire
function thematique_pre_boucle($boucle) {
	if (($boucle->type_requete != 'articles') && ($boucle->type_requete != 'syndic_articles')) {
		return $boucle;
	}

	if (isset($boucle->modificateur['tout']) || strstr($_SERVER['REQUEST_URI'], '/ecrire')) {
		return $boucle;
	}

	// thematique_annee_scolaire() doit être appelée à l'exécution du squelette compilé
	// (et non interpolée ici), sinon l'année scolaire de la toute première compilation
	// resterait figée dans le cache pour toutes les requêtes suivantes.
	$date = $boucle->id_table . '.date';
	$boucle->where[] = [
		"'AND'",
		["'>='", "'$date'", "'\"'.thematique_annee_scolaire().'-08-01\"'"],
		["'<='", "'$date'", "'\"'.(thematique_annee_scolaire() + 1).'-08-01\"'"],
	];
	return $boucle;
}

function thematique_jqueryui_plugins($scripts) {
	$scripts[] = 'jquery.ui.draggable';
	$scripts[] = 'jquery.ui.tooltip';
	/*
	$scripts[] = 'jquery.ui.mouse';
	$scripts[] = 'jquery.ui.position';
	$scripts[] = 'jquery.ui.droppable';
	$scripts[] = 'jquery.ui.effect';
	$scripts[] = 'jquery.ui.effect-bounce';
	*/
	return $scripts;
}

function thematique_insert_head($flux) {

	// Ne pas ré-insérer les balises script lors d'un rechargement ajax
	// (ex: $.load()) qui recharge un fragment dans une page déjà initialisée.
	// On se base sur l'en-tête X-Requested-With plutôt que sur le paramètre
	// 'mode' seul : une vraie navigation complète vers une url mode=ajax-detail
	// (lien direct, rafraîchissement) doit quand même charger les scripts.
	if (thematique_est_requete_ajax()
		&& (_request('mode') === 'ajax' || _request('mode') === 'ajax-detail')) {
		return $flux;
	}

	$scripts = [
		'js/publier_mission.js',
		'js/addCloseModal.js',
		'js/forum.js',
		'js/ajouter_document_forum.js',
		'js/article.js',
		'js/article_email_consigne.js',
		'js/classe.js',
		'js/consigne.js',
		'js/controleurs.js',
		'js/deferred_count.js',
		'js/documents_portfolio_swiper_init.js',
		'js/getClassColorByClassName.js',
		'js/getClassIconByClassName.js',
		'js/globales.js',
		'js/intervenant.js',
		'js/jquery.isotope.min.js',
		'js/layout.js',
		'js/main.js',
		'js/menu_haut.js',
		'js/projet.js',
		'js/reponse.js',
		'js/reponse_binome_scroll.js',
		'js/sidebarCacheTooltip.js',
		'js/custom-tabs.js',
	];

	foreach ($scripts as $script) {
		$chemin = find_in_path($script);
		// Version = mtime du fichier : casse le cache navigateur à chaque
		// déploiement, ce qui permet d'activer un cache long en aval (cf
		// .htaccess) sans risquer de servir du JS périmé après une mise à jour.
		$version = $chemin ? @filemtime($chemin) : null;
		$flux .= "<script src='" . $chemin . ($version ? '?' . $version : '') . "' defer></script>\n";
	}

	return $flux;
}

function thematique_notifications_destinataires($flux) {
	$id_article = null;

	if (
		isset($flux['args']['id'])
		and isset($flux['args']['quoi'])
		and $flux['args']['quoi'] === 'instituerarticle'
		and $flux['args']['options']['statut'] === 'publie'
		and $flux['args']['options']['statut_ancien'] !== 'publie'
	) {
		$id_article = intval($flux['args']['id']);
	}

	if (
		isset($flux['args']['quoi'])
		and $flux['args']['quoi'] === 'forumvalide'
		and isset($flux['args']['options']['forum']['objet'])
		and $flux['args']['options']['forum']['objet'] === 'article'
	) {
		$id_article = intval($flux['args']['options']['forum']['id_objet']);
	}

	if ($id_article) {
		spip_log('publication de ' . $flux['args']['quoi'] . ' ' . $id_article, 'thematique');
		$flux['data'][] = $GLOBALS['meta']['email_envoi'];
		$article = sql_fetsel('*', 'spip_articles', 'id_article=' . $id_article);
		if (!$article) {
			return $flux;
		}
		$titre_rub = sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique=' . intval($article['id_secteur']));
		if ($article['id_consigne'] == '0' and is_numeric($titre_rub)) {
			spip_log(
				'lier à la consigne ' . $article['id_consigne'] . ' et au secteur ' . $article['id_secteur'],
				'thematique'
			);
			// Prendre les admin restreint des sous rubriques (des écoles)
			$id_rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($article['id_secteur']));
			$id_rubriques = array_map('intval', array_column($id_rubriques, 'id_rubrique'));
			if ($id_rubriques) {
				$auteurs_restreint = sql_select(
					'auteurs.id_auteur, auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", sql_in('lien.id_objet', $id_rubriques), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					spip_log('auteur id=' . intval($ar['id_auteur']), 'thematique');
					$flux['data'][] = $ar['email'];
				}
			}
		} else {
			spip_log('lier au secteur ' . $article['id_secteur'], 'thematique');
			$annee_scolaire = thematique_annee_scolaire();
			spip_log('lier à l année ' . $annee_scolaire, 'thematique');
			$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%'));
			spip_log('lier au secteur ' . $id_secteur, 'thematique');
			$id_rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($id_secteur));
			$id_rubriques = array_map('intval', array_column($id_rubriques, 'id_rubrique'));
			if ($id_rubriques) {
				$auteurs_restreint = sql_select(
					'auteurs.id_auteur, auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", sql_in('lien.id_objet', $id_rubriques), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					spip_log('auteur id=' . intval($ar['id_auteur']), 'thematique');
					$flux['data'][] = $ar['email'];
				}
			}
		}
	}
	return $flux;
}

function thematique_cioidc_userinfo($flux) {
	spip_log('userinfo args=' . json_encode($flux['args']) . ' data=' . json_encode($flux['data']), 'cioidc');

	$email = $flux['data']['MailAdressePrincipal'] ?? '';
	$uid = $flux['args']['uid'] ?? '';
	$auteur = thematique_cioidc_resoudre_auteur($uid, $email);
	if (!$auteur) {
		spip_log('userinfo aucun auteur trouvé pour email=' . $email, 'cioidc');
		return $flux;
	}
	spip_log('userinfo auteur trouvé id=' . $auteur['id_auteur'] . ' nom=' . $auteur['nom'], 'cioidc');
	$auteur = thematique_cioidc_maj_champ($auteur, 'email', $email, "de l'email");
	// URL de la photo de profil laclasse.com (avatar réel, distinct des pictos
	// icon-avatar-masculin/feminin utilisés en repli quand l'ENT n'en fournit pas).
	$avatar = $flux['data']['avatar'] ?? '';
	$auteur = thematique_cioidc_maj_champ($auteur, 'avatar', $avatar, "de l'avatar");

	$classes_groupes = thematique_cioidc_normaliser_liste($flux['data']['ENTClassesGroupes'] ?? []);
	$classes_reelles = thematique_cioidc_classes_reelles($classes_groupes);

	// ENTPersonProfils indique le rôle ENT du compte (ENS/TUT/ELV), utilisé ci-dessous
	// pour affiner le nom affiché et plus bas pour déterminer le statut SPIP.
	$profils = $flux['data']['ENTPersonProfils [ENS|TUT|ELV]'] ?? '';
	$is_enseignant = (strpos($profils, 'ENS') !== false);
	spip_log('userinfo ENTPersonProfils=' . $profils . ' => enseignant:' . ($is_enseignant ? 'oui' : 'non'), 'cioidc');

	$uai_liste = thematique_cioidc_normaliser_liste($flux['data']['ENTAllUai'] ?? []);
	$is_webmestre = thematique_cioidc_est_webmestre($uai_liste, $is_enseignant);
	$is_eleve = (strpos($profils, 'ELV') !== false);
	$role_ent = thematique_cioidc_role_affiche($profils, $is_webmestre);

	$nom = thematique_cioidc_nom_affiche($flux['data'], $classes_reelles, $role_ent, $uai_liste);
	$auteur = thematique_cioidc_maj_champ($auteur, 'nom', $nom, 'du nom');

	$annee_scolaire = thematique_annee_scolaire();
	[, $id_travail_classes, $id_consignes] = thematique_cioidc_rubriques_annee($annee_scolaire);

	$groupes_libres = thematique_cioidc_normaliser_liste($flux['data']['ENTGroupesLibres'] ?? []);
	$nom_site = $GLOBALS['meta']['nom_site'] ?? '';
	$groupes_libres_pertinents = thematique_cioidc_groupes_libres_pertinents($groupes_libres, $nom_site, $annee_scolaire);
	spip_log(
		'userinfo nom_site=' . $nom_site
			. ' nb ENTClassesGroupes=' . count($classes_groupes)
			. ' nb ENTGroupesLibres=' . count($groupes_libres)
			. ' nb pertinents=' . count($groupes_libres_pertinents),
		'cioidc'
	);

	$a_un_groupe_pertinent = count($classes_reelles) > 0 || count($groupes_libres_pertinents) > 0;
	$statut = thematique_cioidc_calculer_statut($is_webmestre, $profils, $a_un_groupe_pertinent);
	spip_log('userinfo groupe_pertinent=' . ($a_un_groupe_pertinent ? 'oui' : 'non') . ' => statut:' . $statut, 'cioidc');
	$auteur = thematique_cioidc_maj_champ($auteur, 'statut', $statut, 'du statut');

	if ($is_webmestre && ($auteur['webmestre'] ?? 'non') !== 'oui') {
		spip_log('userinfo passage webmestre', 'cioidc');
		sql_updateq('spip_auteurs', ['webmestre' => 'oui'], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur['webmestre'] = 'oui';
	}

	thematique_cioidc_bloquer_si_archive($auteur);

	if ($is_webmestre) {
		// Un webmestre n'est restreint à aucune rubrique : on retire d'éventuels
		// liens d'admin restreint hérités d'un statut précédent.
		objet_dissocier(['id_auteur' => $auteur['id_auteur']], ['rubrique' => '*']);
	}

	[$classes_a_lier, $projets_a_lier] = thematique_cioidc_resoudre_liens_rubriques(
		$is_enseignant,
		$is_webmestre,
		$is_eleve,
		$classes_reelles,
		$groupes_libres_pertinents,
		$id_travail_classes,
		$id_consignes
	);

	spip_log(
		$auteur['id_auteur'] . ' / ' . $auteur['nom'] . ' => enseignant:' . ($is_enseignant ? 'oui' : 'non')
			. ' eleve:' . ($is_eleve ? 'oui' : 'non')
			. ' classes:' . implode(',', $classes_a_lier)
			. ' projets:' . implode(',', $projets_a_lier),
		'cioidc'
	);

	thematique_cioidc_associer_rubriques(
		$auteur,
		$is_enseignant,
		$is_webmestre,
		$is_eleve,
		$classes_a_lier,
		$projets_a_lier
	);

	return $flux;
}

/**
 * Enregistre les tâches de fond thematique_rentree_annee et
 * thematique_rentree_poubelle (genie/) via le pipeline plutôt que la
 * balise <genie> de paquet.xml : équivalent en interne (cf
 * ecrire/inc/genie.php), mais évalué dynamiquement à chaque calcul des
 * tâches de fond au lieu de nécessiter que SPIP revérifie le paquet du
 * plugin pour les enregistrer.
 *
 * @param array $taches_generales
 * @return array
 */
function thematique_taches_generales_cron($taches_generales) {
	$taches_generales['thematique_rentree_annee'] = 86400;
	$taches_generales['thematique_rentree_poubelle'] = 86400;
	return $taches_generales;
}

/**
 * Amorce les crayons sur les fragments sans <head> (ex: noisettes/sidebar/*
 * chargées par loadContentInMainSidebar() via #INCLURE{...,ajax}).
 *
 * Le plugin crayons (Crayons_affichage_final) sait très bien détecter et
 * autoriser ces crayons, mais son injection du script (inc_crayons_preparer_page_dist)
 * cherche un `</head>` dans la page pour savoir où insérer le <script> :
 * absent sur un simple fragment HTML, elle abandonne silencieusement et
 * crayons.js n'est jamais chargé. On ne modifie pas ce plugin communautaire :
 * on complète ici, pour nos propres fragments, en rejouant le même calcul
 * de droits et en demandant l'injection en mode 'head' (simple ajout en fin
 * de fragment, sans dépendre d'un </head>).
 *
 * @param string $page
 * @return string
 */
function thematique_affichage_final($page) {
	if (!test_plugin_actif('crayons')) {
		return $page;
	}
	// Déjà pris en charge par crayons lui-même (page complète avec <head>),
	// ou déjà amorcé par cette fonction sur un hit précédent : rien à faire.
	if (strpos($page, 'crayon') === false || strpos($page, '</head>') !== false || strpos(
		$page,
		'startCrayons'
	) !== false) {
		return $page;
	}

	include_spip('inc/crayons');
	if (
		(function_exists('analyse_droits_rapide') ? analyse_droits_rapide() : analyse_droits_rapide_dist())
		and preg_match_all(_PREG_CRAYON, $page, $regs, PREG_SET_ORDER)
	) {
		include_spip('inc/autoriser');
		$droits = [];
		$droits_accordes = 0;
		foreach ($regs as $reg) {
			[, $crayon, $type, $champ, $id] = $reg;
			if (autoriser('modifier', $type, $id, null, ['champ' => $champ])) {
				$droits['.' . $crayon] = true;
				$droits_accordes++;
			}
		}
		if ($droits) {
			$Crayons_preparer_page = charger_fonction('Crayons_preparer_page', 'inc');
			$page = $Crayons_preparer_page(
				$page,
				$droits_accordes === count($regs) ? '*' : implode(',', array_keys($droits)),
				wdgcfg(),
				'head'
			);
		}
	}

	return $page;
}
