<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('action/editer_liens');

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
		'js/addCloseModal.js',
		'js/admin_intervenant_select.js',
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

function thematique_cioidc_userinfo($flux) {
	spip_log('userinfo args=' . json_encode($flux['args']) . ' data=' . json_encode($flux['data']), 'cioidc');

	$email = $flux['data']['MailAdressePrincipal'] ?? '';
	$auteur = sql_fetsel('id_auteur,nom,statut,email', 'spip_auteurs', 'email=' . sql_quote($email));
	if (!$auteur) {
		// Compte auto-créé par cioidc (login=uid, sans email) : chercher par login en secours
		$uid = $flux['args']['uid'] ?? '';
		$auteur = sql_fetsel('id_auteur,nom,statut,email', 'spip_auteurs', 'login=' . sql_quote($uid));
	}
	if (!$auteur) {
		spip_log('userinfo aucun auteur trouvé pour email=' . $email, 'cioidc');
		return $flux;
	}
	spip_log('userinfo auteur trouvé id=' . $auteur['id_auteur'] . ' nom=' . $auteur['nom'], 'cioidc');

	if ($email && $email !== $auteur['email']) {
		spip_log('userinfo mise à jour de l\'email : ' . $auteur['email'] . ' => ' . $email, 'cioidc');
		sql_updateq('spip_auteurs', ['email' => $email], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur['email'] = $email;
	}

	$prenom = $flux['data']['LaclassePrenom'] ?? '';
	$nom_famille = $flux['data']['LaclasseNom'] ?? '';
	$nom = trim($prenom . ' ' . $nom_famille);
	if ($nom && $nom !== $auteur['nom']) {
		spip_log('userinfo mise à jour du nom : ' . $auteur['nom'] . ' => ' . $nom, 'cioidc');
		sql_updateq('spip_auteurs', ['nom' => $nom], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur['nom'] = $nom;
	}

	$classes_a_lier = [];

	// Trouver le secteur de l'année scolaire en cours (ex: "2025")
	$annee_scolaire = thematique_annee_scolaire();
	$id_secteur = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);
	spip_log('userinfo annee_scolaire=' . $annee_scolaire . ' id_secteur=' . $id_secteur, 'cioidc');

	// Trouver la rubrique "Travail des classes" sous ce secteur
	$id_travail_classes = null;
	if ($id_secteur) {
		$id_travail_classes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Travail des classes%') . ' AND id_secteur=' . intval($id_secteur)
		);
	}
	spip_log('userinfo id_travail_classes=' . $id_travail_classes, 'cioidc');

	$profils = $flux['data']['ENTPersonProfils [ENS|TUT|ELV]'] ?? '';
	$is_enseignant = (strpos($profils, 'ENS') !== false);
	spip_log('userinfo ENTPersonProfils=' . $profils . ' => enseignant:' . ($is_enseignant ? 'oui' : 'non'), 'cioidc');

	$statut = null;
	if (strpos($profils, 'ELV') !== false) {
		$statut = '6forum';
	} elseif (strpos($profils, 'ENS') !== false) {
		$statut = '0minirezo';
	}
	if ($statut && $statut !== $auteur['statut']) {
		spip_log('userinfo mise à jour du statut : ' . $auteur['statut'] . ' => ' . $statut, 'cioidc');
		sql_updateq('spip_auteurs', ['statut' => $statut], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur['statut'] = $statut;
	}

	$groupes_libres = $flux['data']['ENTGroupesLibres'] ?? [];
	spip_log('userinfo nb ENTGroupesLibres=' . count($groupes_libres), 'cioidc');

	if ($is_enseignant) {
		foreach ($groupes_libres as $groupe) {
			//$groupe->structure_id; $groupe->id; $groupe->name;
			spip_log('userinfo groupe=' . json_encode($groupe), 'cioidc');
			// Chercher la rubrique de classe (ex: "3EME2") sous "Travail des classes" de l'année en cours
			if ($id_travail_classes && !empty($groupe->name)) {
				$id_classe = sql_getfetsel(
					'id_rubrique',
					'spip_rubriques',
					'titre LIKE ' . sql_quote('%' . $groupe->name . '%') . ' AND id_parent=' . intval($id_travail_classes)
				);
				spip_log('userinfo recherche classe name=' . $groupe->name . ' => id_classe=' . $id_classe, 'cioidc');
				if ($id_classe) {
					$classes_a_lier[] = $id_classe;
				}
			}
		}
	}

	spip_log(
		$auteur['id_auteur'] . ' / ' . $auteur['nom'] . ' => enseignant:' . ($is_enseignant ? 'oui' : 'non') . ' classes:' . implode(
			',',
			$classes_a_lier
		),
		'cioidc'
	);

	if ($is_enseignant) {
		$blog = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote('Blog pédagogique'));
		if ($blog) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
		}
		foreach ($classes_a_lier as $id_classe) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_classe]);
		}
	}

	return $flux;
}
