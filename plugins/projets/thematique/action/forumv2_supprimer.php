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
 *
 * Réponse HTTP seule (appel fetch() de callbackCliqueSurSupprimerMonCommentaire()
 * dans forum.js) : 200 si le commentaire est bien à la poubelle, 403 sinon.
 */
function action_forumv2_supprimer_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_forum = intval($securiser_action());
	if (!$id_forum || !autoriser('forumsupprimer', 'forum', $id_forum)) {
		http_response_code(403);
		exit;
	}

	$row = sql_fetsel('*', 'spip_forum', 'id_forum=' . $id_forum);
	if ($row) {
		include_spip('action/instituer_forum');
		instituer_un_forum('poubelle', $row);
	}
	$statut = sql_getfetsel('statut', 'spip_forum', 'id_forum=' . $id_forum);

	http_response_code($statut === 'poubelle' ? 200 : 403);
	exit;
}
