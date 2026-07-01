<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function oauth2_client_store_auth_context($app, $context) {
	include_spip('inc/session');
	$key = 'oauth2_auth_context_' . $app . '_' . session_id();
	session_set($key, $context);

}

function oauth2_client_get_auth_context($app) {
	include_spip('inc/session');
	$key = 'oauth2_auth_context_' . $app . '_' . session_id();
	return session_get($key);
}