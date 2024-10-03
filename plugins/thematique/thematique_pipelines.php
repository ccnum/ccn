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
	$flux .= "<script type='text/javascript' src='" . find_in_path('js/article_blog.js') . "'></script>\n";
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

function thematique_post_edition($flux) {
	if (
		isset($flux['args']['table'])
		and $flux['args']['table'] === 'spip_articles'
		and isset($flux['args']['action'])
		and $flux['args']['action'] === 'instituer'
	) {
		$notifications = charger_fonction('notifications', 'inc');
		$notifications(
			'instituerarticle',
			$flux['args']['id_objet'],
			['statut' => 'publie', 'statut_ancien' => 'propose', 'date' => date('Y-m-d H:i:s')]
		);
	}
	return $flux;
}
