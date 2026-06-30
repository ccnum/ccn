<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/flock');

/**
 * Retourne le chemin du fichier state
 */
function oauth2_client_state_file($state) {

	$dir = sous_repertoire(_DIR_TMP, 'oauth2_client');

	return $dir . "oauth2_state_{$state}.json";
}

/**
 * Stocke le state OAuth2
 */
function oauth2_client_state_store($state, $data) {

	$file = oauth2_client_state_file($state);
	$data = json_encode($data);

	if (ecrire_fichier($file, $data)) {
	} else {
		spip_log("OAuth2: state store error: $file", 'oauthclient.'._LOG_ERREUR);
	}
}

/**
 * Charge le state OAuth2
 */
function oauth2_client_state_load($state) {

	$file = oauth2_client_state_file($state);

	if (!file_exists($file)) {
		spip_log("Oauth2: state load: not found $file", 'oauthclient.'._LOG_DEBUG);
		return null;
	}

	$res = lire_fichier($file,$contenu);

	if (!$res) {
		spip_log("Oauth2: state load: read error $file", 'oauthclient.'._LOG_ERREUR);
		return null;
	}

	$data = json_decode($contenu, true);

	// TTL 10 minutes
	if (!empty($data['created_at']) && (time() - $data['created_at'] > 600)) {
		supprimer_fichier($file);
		spip_log("Oauth2: state load: expired $file", 'oauthclient.'._LOG_DEBUG);
		return null;
	}

	spip_log("Oauth2: state load: ok $file", 'oauthclient.'._LOG_DEBUG);

	return $data;
}

/**
 * Supprime le state
 */
function oauth2_client_state_delete($state) {

	$file = oauth2_client_state_file($state);

	if (file_exists($file)) {
		supprimer_fichier($file);
		spip_log("Oauth2: state delete: $file", 'oauthclient.'._LOG_DEBUG);
	}
}

/**
 * Nettoyage global
 */
function oauth2_client_state_cleanup($ttl = 600) {

	static $last_run = 0;

	// max 1 fois toutes les 60s
	if ($last_run && (time() - $last_run) < 60) {
		return;
	}

	$last_run = time();

	$dir = sous_repertoire(_DIR_TMP, 'oauth2_client');

	foreach (glob($dir . 'oauth2_state_*.json') as $file) {
		if (filemtime($file) < (time() - $ttl)) {
			supprimer_fichier($file);
		}
	}
}