<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Charger
 * @return array
 */
function formulaires_configurer_entravaux_charger_dist() {

	return [
		'accesferme' => is_entravaux() ? '1' : '',
		'message' => $GLOBALS['meta']['entravaux_message'] ?? '',
		'disallow_robots' => $GLOBALS['meta']['entravaux_disallow_robots'] ?? '',
		'autoriser_travaux' => lire_config('entravaux/autoriser_travaux')
	];
}

/**
 * Traiter
 * @return array
 */
function formulaires_configurer_entravaux_traiter_dist() {

	include_spip('entravaux_administrations');
	if (_request('accesferme')) {
		entravaux_poser_verrou('accesferme');
	} else {
		entravaux_lever_verrou('accesferme');
	}


	foreach (['message','disallow_robots'] as $k) {
		ecrire_meta('entravaux_' . $k, _request($k) ?: '', 'non');
	}
	ecrire_config('entravaux/autoriser_travaux', _request('autoriser_travaux'));
	return ['message_ok' => _T('config_info_enregistree')];
}
