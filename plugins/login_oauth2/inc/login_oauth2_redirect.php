<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Redirige après authentification OAuth2 réussie.
 *
 */
function login_oauth2_rediriger_succes(): void {

	include_spip('inc/headers');
	$redirect = _request('redirect') ?: _request('url');

	if ($redirect) {
		redirige_par_entete($redirect);
		exit;
	}

	// fallback
	redirige_par_entete(generer_url_public());
	exit;
}