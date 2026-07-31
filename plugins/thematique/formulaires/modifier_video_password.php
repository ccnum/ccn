<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Formulaire de modification du mot de passe Vimeo d'un document déjà joint,
 * accessible depuis le front (cf ajouter_document.html). Répercute le
 * changement sur Vimeo via l'API (plugin api_vimeo), comme le fait déjà
 * api_vimeo_post_edition() pour une édition passant par le formulaire
 * générique d'édition de document.
 *
 * @package SPIP\Thematique\Formulaires
 */

function formulaires_modifier_video_password_charger_dist($id_document = 0) {
	$mot_de_passe = sql_getfetsel('vimeo_password', 'spip_documents', 'id_document=' . intval($id_document));

	return [
		'id_document' => $id_document,
		'vimeo_password' => $mot_de_passe ?: '',
	];
}

function formulaires_modifier_video_password_verifier_dist($id_document = 0) {
	$erreurs = [];

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'document', $id_document)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
	}

	return $erreurs;
}

function formulaires_modifier_video_password_traiter_dist($id_document = 0) {
	$res = ['editable' => true];

	$doc = sql_fetsel('fichier', 'spip_documents', 'id_document=' . intval($id_document));
	if (!$doc || strpos($doc['fichier'], 'vimeo.com') === false) {
		$res['message_erreur'] = _T('thematique:erreur_maj_mot_de_passe_video');
		return $res;
	}

	$mot_de_passe = _request('vimeo_password') ?? '';

	include_spip('inc/api_vimeo');
	if (!api_vimeo_set_password($doc['fichier'], $mot_de_passe)) {
		$res['message_erreur'] = _T('thematique:erreur_maj_mot_de_passe_video');
		return $res;
	}

	sql_updateq('spip_documents', ['vimeo_password' => $mot_de_passe], 'id_document=' . intval($id_document));

	$res['message_ok'] = _T('thematique:mot_de_passe_video_modifie');
	// Recharge le bloc documents : referme le formulaire (ré-affiché masqué
	// par défaut) et évite d'avoir à recharger la page à la main.
	$res['message_ok'] .= "<script type='text/javascript'>"
		. "if (window.jQuery) jQuery(function(){ajaxReload('documents');});"
		. '</script>';

	return $res;
}
