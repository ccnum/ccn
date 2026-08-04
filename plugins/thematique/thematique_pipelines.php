<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('action/editer_liens');
// thematique_annee_scolaire() est normalement auto-incluse via thematique_fonctions.php,
// mais ce n'est pas garanti dans tous les contextes d'appel (ex: pipeline cioidc_userinfo
// déclenché depuis une action hors squelette), d'où cet include explicite.
include_spip('thematique_fonctions');

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
		$flux .= "<script src='" . find_in_path($script) . "' defer></script>\n";
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

// cioidc_session() résout le compte qui recevra réellement la session par login=uid
// (uid_champ_spip='login', cf. cioidc_verifier_identifiant()) : on doit chercher avec
// le même critère en priorité, sinon on met à jour un autre compte (ex: un doublon
// historique retrouvé par email) que celui qui sera effectivement connecté.
function thematique_cioidc_resoudre_auteur($uid, $email) {
	$auteur = $uid ? sql_fetsel('id_auteur,nom,statut,email,webmestre', 'spip_auteurs', 'login=' . sql_quote($uid)) : null;
	if (!$auteur) {
		$auteur = sql_fetsel('id_auteur,nom,statut,email,webmestre', 'spip_auteurs', 'email=' . sql_quote($email));
	}
	return $auteur;
}

// Si $valeur diffère du champ actuel de l'auteur, met à jour spip_auteurs et logue le
// changement. Factorise le motif commun aux mises à jour d'email/nom/statut ci-dessous.
function thematique_cioidc_maj_champ(array $auteur, string $champ, $valeur, string $libelle_log) {
	if ($valeur && $valeur !== $auteur[$champ]) {
		spip_log('userinfo mise à jour ' . $libelle_log . ' : ' . $auteur[$champ] . ' => ' . $valeur, 'cioidc');
		sql_updateq('spip_auteurs', [$champ => $valeur], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur[$champ] = $valeur;
	}
	return $auteur;
}

// Normalise un attribut CAS multivalué : arrive en objet unique (pas en tableau)
// quand il n'y a qu'une seule valeur.
function thematique_cioidc_normaliser_liste($valeur) {
	if (is_object($valeur)) {
		return [$valeur];
	}
	return $valeur ?: [];
}

// ENTClassesGroupes (member_type=ENS, group_type=CLS) : la/les classe(s) réellement
// enseignée(s) par ce prof, utilisée pour affiner son nom affiché et pour le lien
// "Travail des classes". Absent chez un intervenant qui n'a pas de classe en charge,
// seulement un groupe projet (ENTGroupesLibres).
function thematique_cioidc_classes_reelles(array $classes_groupes) {
	return array_values(array_filter(
		$classes_groupes,
		fn ($groupe) => ($groupe->group_type ?? '') === 'CLS' && !empty($groupe->group_name)
	));
}

// Les comptes ENS rattachés à un établissement listé dans _THEMATIQUE_RNE_WEBMESTRES
// (ex: établissement pilote de l'équipe projet) restent administrateurs complets
// plutôt que rédacteurs, sans restriction de rubrique. ENTAllUai liste tous les UAI
// de rattachement (pas seulement le principal).
function thematique_cioidc_est_webmestre($uai_liste, bool $is_enseignant) {
	$uai_liste = array_map('strval', (array) thematique_cioidc_normaliser_liste($uai_liste));
	$rne_webmestres = array_filter(array_map('trim', explode(',', _THEMATIQUE_RNE_WEBMESTRES)));
	$is_webmestre = $is_enseignant && (bool) array_intersect($uai_liste, $rne_webmestres);
	spip_log(
		'userinfo ENTAllUai=' . implode(',', $uai_liste) . ' => webmestre:' . ($is_webmestre ? 'oui' : 'non'),
		'cioidc'
	);
	return $is_webmestre;
}

// Rôle ENT affiché (Enseignant/Tuteur/Élève), remplacé par "Admin" pour un webmestre :
// affiché ci-dessous à la suite du nom plutôt que le rôle ENT d'origine.
function thematique_cioidc_role_affiche(string $profils, bool $is_webmestre) {
	if ($is_webmestre) {
		return 'Admin';
	}
	$roles_ent = ['ENS' => 'Enseignant', 'TUT' => 'Tuteur', 'ELV' => 'Élève'];
	foreach ($roles_ent as $code => $libelle) {
		if (strpos($profils, $code) !== false) {
			return $libelle;
		}
	}
	return null;
}

// Nom affiché : prénom/nom ENT, suivi du rôle ENT et de la classe (première classe
// réelle du prof) pour distinguer dans la liste des auteurs un même prof intervenant
// sur plusieurs CCN (cf issue #44). Le group_name reçu de l'ENT est préfixé par
// "CCN - " : préfixe redondant qu'on retire.
function thematique_cioidc_nom_affiche(array $flux_data, array $classes_reelles, ?string $role_ent) {
	$prenom = $flux_data['LaclassePrenom'] ?? '';
	$nom_famille = $flux_data['LaclasseNom'] ?? '';
	$nom = trim($prenom . ' ' . $nom_famille);
	if ($nom && $role_ent) {
		$nom .= ' - ' . $role_ent;
	}
	if ($nom && ($groupe_classe = $classes_reelles[0]->group_name ?? null)) {
		if (stripos($groupe_classe, 'CCN - ') === 0) {
			$groupe_classe = substr($groupe_classe, strlen('CCN - '));
		}
		$nom .= ' - ' . $groupe_classe;
	}
	return $nom;
}

// Résout le secteur de l'année scolaire en cours (ex: "2025"), et sous ce secteur les
// rubriques "Travail des classes" (rôle prof) et "Consignes" (rôle intervenant).
function thematique_cioidc_rubriques_annee(string $annee_scolaire) {
	$id_secteur = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);
	spip_log('userinfo annee_scolaire=' . $annee_scolaire . ' id_secteur=' . $id_secteur, 'cioidc');

	$id_travail_classes = null;
	$id_consignes = null;
	if ($id_secteur) {
		$id_travail_classes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Travail des classes%') . ' AND id_secteur=' . intval($id_secteur)
		);
		$id_consignes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Consignes%') . ' AND id_secteur=' . intval($id_secteur)
		);
	}
	spip_log('userinfo id_travail_classes=' . $id_travail_classes . ' id_consignes=' . $id_consignes, 'cioidc');

	return [$id_secteur, $id_travail_classes, $id_consignes];
}

// ENTGroupesLibres : le groupe projet (ex: nom de l'intervenant/du binôme) → lien
// "Consignes". Un même compte ENT peut porter des groupes de plusieurs
// thématiques/années ("Textile 2023", "On tourne 2025", ...) : seuls ceux de la
// thématique de CE site (meta nom_site) et de l'année scolaire en cours sont
// pertinents ici.
function thematique_cioidc_groupes_libres_pertinents(array $groupes_libres, string $nom_site, string $annee_scolaire) {
	if (!$nom_site) {
		return [];
	}
	$pertinents = [];
	foreach ($groupes_libres as $groupe) {
		$nom_groupe = $groupe->name ?? '';
		if (
			$nom_groupe
			&& stripos($nom_groupe, $nom_site) !== false
			&& strpos($nom_groupe, $annee_scolaire) !== false
		) {
			$pertinents[] = $groupe;
		}
	}
	return $pertinents;
}

// Statut SPIP selon le rôle ENT. Un enseignant sans classe réelle ni groupe projet
// pertinent pour ce site/cette année reste simple visiteur ('6forum') : pas de droit
// de rédaction tant qu'il n'est pas effectivement affecté à quelque chose ici.
function thematique_cioidc_calculer_statut(bool $is_webmestre, string $profils, bool $a_un_groupe_pertinent) {
	if ($is_webmestre) {
		return '0minirezo';
	}
	if (strpos($profils, 'ELV') !== false) {
		return '6forum';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $a_un_groupe_pertinent ? '1comite' : '6forum';
	}
	return null;
}

// Sur un CCN archivé (_CCN_PROJET_ACTIVE=false), seuls les comptes webmestre peuvent
// se connecter via l'ENT/OIDC. Vérifié seulement après la promotion webmestre (dans
// l'appelant) : un webmestre qui se connecte pour la première fois sur ce site précis
// n'a pas encore 'oui' en base avant ce point (chaque CCN a sa propre table
// spip_auteurs), il serait bloqué à tort si on testait plus tôt. cioidc_session()
// (plugins/cioidc/inc/cioidc_session.php) ouvre la session juste après ce pipeline :
// rediriger ici empêche la session de s'ouvrir, sans avoir à toucher au plugin tiers
// cioidc.
function thematique_cioidc_bloquer_si_archive(array $auteur) {
	if (
		defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE
		&& ($auteur['webmestre'] ?? 'non') !== 'oui'
	) {
		spip_log('userinfo connexion refusée (CCN archivé, non-webmestre) id=' . $auteur['id_auteur'], 'cioidc');
		include_spip('inc/headers');
		redirige_par_entete(generer_url_public('cioidc_erreur_archive'));
	}
}

// Rubriques de classe/projet à lier à l'auteur : rôle prof (classes réelles + groupes
// projet pertinents) ou rôle élève (même classe que son prof, même recherche/création,
// pour rattacher l'élève à sa classe à son tour, ex: affichage de l'emoji de classe
// hors du contexte d'une rubrique).
function thematique_cioidc_resoudre_liens_rubriques(
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_reelles,
	array $groupes_libres_pertinents,
	$id_travail_classes,
	$id_consignes
) {
	$classes_a_lier = [];
	$projets_a_lier = [];

	if ($is_enseignant && !$is_webmestre) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
		foreach ($groupes_libres_pertinents as $groupe) {
			if ($id_projet = thematique_trouver_ou_creer_rubrique($groupe->name ?? '', $id_consignes)) {
				$projets_a_lier[] = $id_projet;
			}
		}
	} elseif ($is_eleve) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
	}

	return [$classes_a_lier, $projets_a_lier];
}

// Associe effectivement l'auteur aux rubriques résolues ci-dessus (+ le blog
// pédagogique commun à tous les profs).
function thematique_cioidc_associer_rubriques(
	array $auteur,
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_a_lier,
	array $projets_a_lier
) {
	if ($is_enseignant && !$is_webmestre) {
		$blog = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote('Blog pédagogique'));
		if ($blog) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
		}
		foreach ($projets_a_lier as $id_projet) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_projet]);
		}
	}
	if (($is_enseignant && !$is_webmestre) || $is_eleve) {
		foreach ($classes_a_lier as $id_classe) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_classe]);
		}
	}
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

	$classes_groupes = thematique_cioidc_normaliser_liste($flux['data']['ENTClassesGroupes'] ?? []);
	$classes_reelles = thematique_cioidc_classes_reelles($classes_groupes);

	// ENTPersonProfils indique le rôle ENT du compte (ENS/TUT/ELV), utilisé ci-dessous
	// pour affiner le nom affiché et plus bas pour déterminer le statut SPIP.
	$profils = $flux['data']['ENTPersonProfils [ENS|TUT|ELV]'] ?? '';
	$is_enseignant = (strpos($profils, 'ENS') !== false);
	spip_log('userinfo ENTPersonProfils=' . $profils . ' => enseignant:' . ($is_enseignant ? 'oui' : 'non'), 'cioidc');

	$is_webmestre = thematique_cioidc_est_webmestre($flux['data']['ENTAllUai'] ?? [], $is_enseignant);
	$is_eleve = (strpos($profils, 'ELV') !== false);
	$role_ent = thematique_cioidc_role_affiche($profils, $is_webmestre);

	$nom = thematique_cioidc_nom_affiche($flux['data'], $classes_reelles, $role_ent);
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
