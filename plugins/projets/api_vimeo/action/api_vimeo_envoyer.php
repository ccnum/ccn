<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Envoi à la demande d'un document mp4 local vers Vimeo, depuis le bouton
 * ajouté sous le document dans le BO (cf api_vimeo_document_desc_actions).
 * Même file d'attente que l'envoi automatique à l'ajout du document.
 *
 * @param int|null $id_document
 */
function action_api_vimeo_envoyer_dist($id_document = null) {
	if (is_null($id_document)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_document = $securiser_action();
	}
	$id_document = intval($id_document);

	include_spip('inc/autoriser');
	if (!$id_document || !autoriser('modifier', 'document', $id_document)) {
		return;
	}

	include_spip('inc/api_vimeo');
	$doc = sql_fetsel('extension, distant, vimeo_statut', 'spip_documents', 'id_document=' . $id_document);
	if (!$doc || !api_vimeo_envoi_possible($doc)) {
		return;
	}

	api_vimeo_mettre_en_file($id_document);
}
