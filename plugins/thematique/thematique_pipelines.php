<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Pre_boucles
// Retourne les articles et articles syndiqués en lien avec l'année scolaire
function thematique_pre_boucle($boucle) {
	$affichage = _affichage;

	$annee = _annee_scolaire;
	$mois = '08';
	$jour = '01';

	$annee2 = intval(_annee_scolaire) + 1;
	$mois2 = '08';
	$jour2 = '01';

	$date_debut = $annee . '.' . $mois . '.' . $jour;
	$date_fin = $annee2 . '.' . $mois2 . '.' . $jour2;

	if (($boucle->type_requete == 'articles') || ($boucle->type_requete == 'syndic_articles')) {
		$date = $boucle->id_table . '.date';
		if ((!isset($boucle->modificateur['tout'])) && (!strstr($_SERVER['REQUEST_URI'], '/ecrire')) && (!$affichage == 'unepage')) {
			$boucle->where[] = [
				"'AND'",
				["'>='", "'$date'", ("'\"$annee-$mois-$jour\"'")],
				["'<='", "'$date'", ("'\"$annee2-$mois2-$jour2\"'")]
			];
		}
	}
	return $boucle;
}

function thematique_jqueryui_plugins($scripts) {
	$scripts[] = 'jquery.ui.core';
	$scripts[] = 'jquery.ui.widget';
	$scripts[] = 'jquery.ui.mouse';
	$scripts[] = 'jquery.ui.position';
	$scripts[] = 'jquery.ui.draggable';
	$scripts[] = 'jquery.ui.droppable';
	$scripts[] = 'jquery.ui.tooltip';
	$scripts[] = 'jquery.ui.effect';
	$scripts[] = 'jquery.ui.effect-bounce';

	return $scripts;
}

function thematique_insert_head($flux) {
	$flux .= "\n<script type='text/javascript' src='" . find_in_path('js/article_blog.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/article_evenement.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/bouton.js') . "'></script>\n";
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/bundled/html4+html5/jquery.history.js') . "'></script>\n";
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
		$flux['data'][] = $GLOBALS['meta']['email_envoi'];
		$article = sql_fetsel('*', 'spip_articles', 'id_article=' . intval($flux['args']['id']));
		if ($article['id_consigne'] == '0') {
			// Prendre les admin restreint des sous rubriques (des écoles)
			$rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_secteur=' . intval($article['id_secteur']));
			foreach ($rubriques as $r) {
				$auteurs_restreint = sql_select(
					"auteurs.email",
					"spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur",
					["lien.objet='rubrique'", "lien.id_objet=" . intval($r['id_rubrique']), "auteurs.statut='0minirezo'"]
				);
				foreach ($auteurs_restreint as $ar) {
					$flux['data'][] = $ar['email'];
				}
			}
		} else {
			$auteurs_restreint = sql_select(
				"auteurs.email",
				"spip_auteurs AS auteurs JOIN spip_auteurs_liens AS lien ON auteurs.id_auteur=lien.id_auteur",
				["lien.objet='rubrique'", "lien.id_objet=" . intval($article['id_secteur']), "auteurs.statut='0minirezo'"]
			);
			foreach ($auteurs_restreint as $ar) {
				$flux['data'][] = $ar['email'];
			}
		}
	}
	return $flux;
}
