<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('action/editer_liens');

// Pre_boucles
// Retourne les articles et articles syndiqués en lien avec l'année scolaire
function thematique_pre_boucle($boucle) {
	$affichage = '_affichage';

	$annee = _ANNEE_SCOLAIRE;
	$mois = '08';
	$jour = '01';

	$annee2 = intval(_ANNEE_SCOLAIRE) + 1;
	$mois2 = '08';
	$jour2 = '01';

	$date_debut = $annee . '.' . $mois . '.' . $jour;
	$date_fin = $annee2 . '.' . $mois2 . '.' . $jour2;

	if (($boucle->type_requete == 'articles') || ($boucle->type_requete == 'syndic_articles')) {
		$date = $boucle->id_table . '.date';
		if ((!isset($boucle->modificateur['tout'])) && (!strstr(
			$_SERVER['REQUEST_URI'],
			'/ecrire'
		)) && (!$affichage == 'unepage')) {
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
	$flux .= "\n<script type='text/javascript' src='" . find_in_path('js/article_blog.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/article_evenement.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/bouton.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path(
		'js/bundled/html4+html5/jquery.history.js'
	) . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/classe.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/consigne.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/controleurs.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/globales.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/intervenant.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/jquery.isotope.min.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/layout.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/main.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/projet.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/reponse.js') . "'></script>\n";

	return $flux;
}

function thematique_notifications_destinataires($flux) {
	if (
		isset($flux['args']['id'])
		and isset($flux['args']['quoi'])
		and $flux['args']['quoi'] === 'instituerarticle'
		and $flux['args']['options']['statut'] === 'publie'
		and $flux['args']['options']['statut_ancien'] !== 'publie'
	) {
		spip_log('publication de ' . $flux['args']['quoi'] . ' ' . $flux['args']['id'], 'thematique');
		$flux['data'][] = $GLOBALS['meta']['email_envoi'];
		$article = sql_fetsel('*', 'spip_articles', 'id_article=' . intval($flux['args']['id']));
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
					'auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", 'lien.id_objet=' . intval($r['id_rubrique']), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					spip_log('les auteurs ' . $ar['email'], 'thematique');
					$flux['data'][] = $ar['email'];
				}
			}
		} else {
			spip_log('lier au secteur ' . $article['id_secteur'], 'thematique');
			if (date('m') >= '08') {
				$annee_scolaire = date('Y');
			} else {
				$annee_scolaire = date('Y') - 1;
			}
			spip_log('lier à l année ' . $annee_scolaire, 'thematique');
			$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'titre LIKE "%' . intval($annee_scolaire).'%"');
			spip_log('lier au secteur ' . $id_secteur, 'thematique');
			$rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($id_secteur));
			foreach ($rubriques as $r) {
				spip_log('lier à la rubrique ' . $r['id_rubrique'], 'thematique');
				$auteurs_restreint = sql_select(
					'auteurs.email',
					'spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur',
					["lien.objet='rubrique'", 'lien.id_objet=' . intval($r['id_rubrique']), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					spip_log('les auteurs ' . $ar['email'], 'thematique');
					$flux['data'][] = $ar['email'];
				}
			}
		}
	}
	return $flux;
}

function thematique_cioidc_userinfo($flux) {
	$auteur = sql_fetsel('id_auteur,nom', 'spip_auteurs', 'email=' . sql_quote($flux['args']['email'])); // Chercher l'auteur qui vient de se loguer
	// Géré les groupes qui sont dans l'OpenID
	$droits_spip = [];
	$groupe_libres = $flux['data']['ENTGroupesLibres'];
	foreach ($groupe_libres as $g_l) {
		//$g_l['structure_id']; $g_l['id']; $g_l['name'];
		if (preg_match('/^(.*)\s(\d{4})$/', $g_l['name'], $matches)) {
			$ccn = $matches[1];
			$annee = $matches[2];
			$droits_spip[] = ['ccn' => $ccn, 'annee' => $annee];
		}
	}
	$classes_groupes = $flux['data']['ENTClassesGroupes'];
	foreach ($classes_groupes as $c_g) {
		//$c_g['member_type']; $c_g['group_id']; $c_g['group_structure_id']; $c_g['group_name']; $c_g['group_type'];
		$droits_spip[] = ['type' => $c_g['member_type']];
	}
	spip_log($auteur['id_auteur'] . ' / ' . $auteur['nom'] . ' => ' . $droits_spip, 'cioidc');
	if ($droits_spip['type'] == 'ENS') {
		// Rattaché le prof sur la rubrique "Blog pédagogique"
		$blog = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote('Blog pédagogique'));
		objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
	}

	return $flux;
}