<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Discovery avec cache fichier (SPIP 4 compatible)
 */
function login_oauth2_oidc_discover($url) {

	if (!$url) {
		return false;
	}

	include_spip('inc/flock');
	$fichier = _DIR_TMP . 'cache_oidc_' . md5($url) . '.txt';
	$ttl = 3600 * 24; // 24h

	// Lecture cache	
	$cache = null;
	if (file_exists($fichier)) {

		$lire = lire_fichier($fichier, $contenu);

		if ($lire && $contenu) {
			$cache = @unserialize($contenu);
		}

		if ($cache && !empty($cache['time']) && !empty($cache['data'])) {

			if (time() - $cache['time'] < $ttl) {
				spip_log("OIDC cache HIT: $url", 'login_oauth2.'._LOG_DEBUG);
				return $cache['data'];
			}
		}
	}
	spip_log("OIDC cache miss: $url", 'login_oauth2.'._LOG_DEBUG);

	// Appel distant
	$data = login_oauth2_oidc_discover_fetch($url);

	if ($data) {
		$cache = [
			'time' => time(),
			'data' => $data
		];
		ecrire_fichier($fichier, serialize($cache));
		return $data;
	}

	// Fallback ancien cache
	if (!empty($cache['data'])) {
		spip_log("OIDC fallback cache: $url", 'login_oauth2.'._LOG_DEBUG);
		return $cache['data'];
	}

	spip_log("OIDC discovery failed: $url", 'login_oauth2.'._LOG_ERREUR);

	return false;
}

/**
 * Appel HTTP du endpoint OIDC
 */
function login_oauth2_oidc_discover_fetch($url) {

	include_spip('inc/distant');
	$res = recuperer_url($url, [
		'timeout' => 5,
	]);

	if (!$res || empty($res['page'])) {
		return false;
	}
	$data = json_decode($res['page'], true);

	if (!$data || empty($data['authorization_endpoint'])) {
		return false;
	}

	return $data;
}