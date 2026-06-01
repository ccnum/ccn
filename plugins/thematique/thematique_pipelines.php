<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('action/editer_liens');

// Pre_boucles
// Retourne les articles et articles syndiqués en lien avec l'année scolaire
function thematique_pre_boucle($boucle) {
	$affichage = '_affichage';

	$annee = constant('_ANNEE_SCOLAIRE');
	$mois = '08';
	$jour = '01';

	$annee2 = intval(constant('_ANNEE_SCOLAIRE')) + 1;
	$mois2 = '08';
	$jour2 = '01';

	$date_debut = $annee . '.' . $mois . '.' . $jour;
	$date_fin = $annee2 . '.' . $mois2 . '.' . $jour2;

	if (($boucle->type_requete == 'articles') || ($boucle->type_requete == 'syndic_articles')) {
		$date = $boucle->id_table . '.date';
		if ((!isset($boucle->modificateur['tout'])) && (!strstr(
			$_SERVER['REQUEST_URI'],
			'/ecrire'
		)) && ($affichage != 'unepage')) {
			$boucle->where[] = [
				"'AND'",
				["'>='", "'$date'", ("'\"$annee-$mois-$jour\"'")],
				["'<='", "'$date'", ("'\"$annee2-$mois2-$jour2\"'")],
			];
		}
	}
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
	$scripts = [
		'js/article_blog.js',
		'js/article_evenement.js',
		'js/bouton.js',
		'js/bundled/html4+html5/jquery.history.js',
		'js/classe.js',
		'js/consigne.js',
		'js/controleurs.js',
		'js/globales.js',
		'js/intervenant.js',
		'js/jquery.isotope.min.js',
		'js/layout.js',
		'js/main.js',
		'js/projet.js',
		'js/reponse.js',
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
			$rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($article['id_secteur']));
			foreach ($rubriques as $r) {
				spip_log('dans les sous rubriques ' . $r['id_rubrique'], 'thematique');
				$auteurs_restreint = sql_select(
					'auteurs.id_auteur, auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", 'lien.id_objet=' . intval($r['id_rubrique']), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					spip_log('auteur id=' . intval($ar['id_auteur']), 'thematique');
					$flux['data'][] = $ar['email'];
				}
			}
		} else {
			spip_log('lier au secteur ' . $article['id_secteur'], 'thematique');
			$annee_scolaire = intval(constant('_ANNEE_SCOLAIRE'));
			spip_log('lier à l année ' . $annee_scolaire, 'thematique');
			$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%'));
			spip_log('lier au secteur ' . $id_secteur, 'thematique');
			$rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($id_secteur));
			foreach ($rubriques as $r) {
				spip_log('lier à la rubrique ' . $r['id_rubrique'], 'thematique');
				$auteurs_restreint = sql_select(
					'auteurs.id_auteur, auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", 'lien.id_objet=' . intval($r['id_rubrique']), "auteurs.statut='0minirezo'"]
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
	$auteur = sql_fetsel('id_auteur,nom', 'spip_auteurs', 'email=' . sql_quote($flux['args']['email']));
	if (!$auteur) {
		return $flux;
	}

	$is_enseignant = false;
	$classes_a_lier = [];

	// Trouver le secteur de l'année scolaire en cours (ex: "2025")
	$annee_scolaire = intval(constant('_ANNEE_SCOLAIRE'));
	$id_secteur = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);

	// Trouver la rubrique "Travail des classes" sous ce secteur
	$id_travail_classes = null;
	if ($id_secteur) {
		$id_travail_classes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Travail des classes%') . ' AND id_secteur=' . intval($id_secteur)
		);
	}

	foreach ($flux['data']['ENTClassesGroupes'] ?? [] as $c_g) {
		//$c_g->member_type; $c_g->group_id; $c_g->group_structure_id; $c_g->group_name; $c_g->group_type;
		if ($c_g->member_type == 'ENS') {
			$is_enseignant = true;
			// Chercher la rubrique de classe (ex: "3EME2") sous "Travail des classes" de l'année en cours
			if ($id_travail_classes && !empty($c_g->group_name)) {
				$id_classe = sql_getfetsel(
					'id_rubrique',
					'spip_rubriques',
					'titre LIKE ' . sql_quote('%' . $c_g->group_name . '%') . ' AND id_parent=' . intval($id_travail_classes)
				);
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
