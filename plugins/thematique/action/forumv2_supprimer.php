<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_forumv2_supprimer_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_forum = intval($arg);
	if (!autoriser('forumsupprimer', 'forum', $id_forum)) {
		spip_log("supression autorisée", "debug");
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	forumv2_supprimer_recursif($id_forum);

	if ($redirect = _request('redirect')) {
		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
}

/**
 * Supprime un commentaire et toutes ses réponses, à toute profondeur.
 */
function forumv2_supprimer_recursif($id_forum) {
	// Collecte tous les id_forum du sous-arbre (le commentaire + ses descendants)
	$ids_a_supprimer = [$id_forum];
	$niveau_courant = [$id_forum];

	while ($niveau_courant) {
		$enfants = sql_allfetsel(
			'id_forum',
			'spip_forum',
			'id_parent IN (' . implode(',', array_map('intval', $niveau_courant)) . ')'
		);

		if (!$enfants) {
			break;
		}

		$ids_enfants = array_map(fn($ligne) => intval($ligne['id_forum']), $enfants);
		$ids_a_supprimer = array_merge($ids_a_supprimer, $ids_enfants);
		$niveau_courant = $ids_enfants;
	}

	sql_delete('spip_forum', 'id_forum IN (' . implode(',', array_map('intval', $ids_a_supprimer)) . ')');

	return $ids_a_supprimer;
}