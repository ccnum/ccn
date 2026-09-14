<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Supprime un commentaire (et ses réponses) en le passant à la poubelle.
 *
 * Délègue à instituer_un_forum() du plugin forum dist, qui gère déjà
 * la descente récursive sur l'arborescence du message, l'invalidation
 * du cache et le pipeline post_edition.
 */
function action_forumv2_supprimer_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_forum = intval($arg);
	if (!autoriser('forumsupprimer', 'forum', $id_forum)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$row = sql_fetsel('*', 'spip_forum', 'id_forum=' . $id_forum);
	if ($row) {
		include_spip('action/instituer_forum');
		instituer_un_forum('poubelle', $row);
	}

	if ($redirect = _request('redirect')) {
		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
}
