<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Enregistre la position (X, Y) d'un article sur la timeline, après un
 * glisser-déposer (consigne.js, reponse.js, article.js, main.js).
 *
 * Remplace l'ancien squelette noisettes/ajax/article-sauve-coordonnees.html,
 * qui écrivait #ENV{X}/#ENV{Y} dans un bloc <?php évalué (exécution de code,
 * audit 2026-10) et n'avait pas de jeton CSRF.
 *
 * URL signée pour l'auteur connecté (arg fixe "coordonnees", cf CCN.urlSauverCoordonnees
 * dans noisettes/timeline.html) ; l'objet et les coordonnées arrivent en POST.
 */
function action_thematique_sauver_coordonnees_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$type_objet = _request('type_objet');
	$id_objet = intval(_request('id_objet'));
	$x = _request('X');
	$y = _request('Y');

	if (
		!in_array($type_objet, ['article', 'syndic_article'], true)
		|| !$id_objet
		|| !is_numeric($x)
		|| !is_numeric($y)
		|| !in_array($GLOBALS['visiteur_session']['statut'] ?? '', ['0minirezo', '1comite'], true)
		|| !autoriser('modifier', $type_objet, $id_objet)
	) {
		return;
	}

	sql_updateq(
		table_objet_sql($type_objet),
		['X' => floatval($x), 'Y' => floatval($y)],
		id_table_objet($type_objet) . '=' . $id_objet
	);
}
