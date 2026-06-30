<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Prépare l'url de callback OAuth2 pour oauth2_client
 */
function login_oauth2_preparer_oauth2_callback($provider, $config) {

	if (empty($config['oauth2']) || !is_array($config['oauth2'])) {
		return false;
	}
	$oauth2 = $config['oauth2'];

	// Injecter redirect_uri
	if (!$provider) {
    return false;
	}
	
	$oauth2['redirect_uri'] = generer_url_action('login_oauth2_callback','provider='.$provider,true);

	return $oauth2;
}
