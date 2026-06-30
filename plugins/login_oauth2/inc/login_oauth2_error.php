<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Gère les erreurs en mettant en session le code transmis pour affichage formulaire
 */
function login_oauth2_rediriger_erreur(string $code) {

	include_spip('inc/session');
	include_spip('inc/headers');

	session_set('login_oauth2_error', $code);

	$url = generer_url_public('login');

	redirige_par_entete($url);
	exit;
}