<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Callback OAuth2 après authentification chez le provider.
 *
 * Cette action est appelée par le provider OAuth2 après que
 * l'utilisateur a validé l'autorisation d'accès.
 *
 * Elle finalise le flux OAuth2 en déléguant le traitement à
 * l’API oauth2_client_get_access_token(), qui :
 *
 * - restaure la session si nécessaire (fallback Safari / SameSite)
 * - valide le state (protection CSRF)
 * - gère le PKCE (code_verifier)
 * - échange le authorization_code contre un access_token
 * - valide le id_token (OIDC)
 *
 * Cette action se concentre ensuite sur :
 *
 * 1. la récupération du profil utilisateur (OIDC ou userinfo)
 * 2. la liaison avec le compte SPIP
 * 3. la connexion utilisateur
 * 4. la redirection finale
 *
 * Providers compatibles :
 * - Google
 * - Facebook
 * - LinkedIn
 * - Keycloak
 * - tout provider OAuth2/OIDC compatible
 *
 * En cas d'erreur, l'utilisateur est redirigé vers la page de login.
 *
 * URL appelée par les providers :
 *     spip.php?action=login_oauth2_callback&provider=XXX
 *
 * @action login_oauth2_callback
 *
 * @return void
 */
function action_login_oauth2_callback_dist() {
	spip_log('action_login_oauth2_callback_dist()', 'login_oauth.'._LOG_DEBUG);
	include_spip('inc/login_oauth2_error');

	$state = _request('state');
	$code  = _request('code');

	if ($code && empty($state)) {
		spip_log("OAuth2: state manquant", 'oauthclient.'._LOG_ERREUR);
		login_oauth2_rediriger_erreur('erreur_authentification');
	}
	
	// Recherche du provider dans le state
	$provider=null;
	if ($state) {
		include_spip('inc/oauth2_client_state');
		$context = oauth2_client_state_load($state);
		$provider = $context['app'] ?? null;
	}

	// Si pas de provider, recherche dans l'url (cas token valide...)
	if (!$provider) {
		$provider = _request('provider');
	}

	if (!$provider || !is_string($provider))  {
		login_oauth2_rediriger_erreur('erreur_provider_invalide');
	}

	include_spip('inc/login_oauth2_providers');
	$providers = login_oauth2_lister_providers();

	if (empty($providers[$provider])) {
		login_oauth2_rediriger_erreur('erreur_provider_invalide');
	}

	// Préparation du callback pour appeler le plugin Oauth2_client
	$config = $providers[$provider];
	include_spip('inc/login_oauth2_auth');
	$oauth2_params = login_oauth2_preparer_oauth2_callback($provider, $config);

	if (!$oauth2_params) {
		login_oauth2_rediriger_erreur('erreur_authentification');
	}

	// nom application OAuth_client = Provider login_Oauth2 (par simplicité)
	$app = $provider;

	// échange code vs access_token
	include_spip('inc/oauth2_client');
	$access_token = oauth2_client_get_access_token($app,$oauth2_params);
	if (!$access_token) {
		login_oauth2_rediriger_erreur('erreur_token_absent');
	}

	// lecture unique du token stocké
	include_spip('inc/oauth2_client_token');
	$stored = oauth2_client_get_stored_token($app, 'session');
	$user = null;

	 // Cas 1 — OIDC (Google / LinkedIn / Azure / Keycloak…)
	if (!empty($stored['user']) && !empty($stored['user']['sub'])) {

		$u = $stored['user'];

		$user = [
			'subject'		=> $u['sub'],
			'email'		  => $u['email'] ?? null,
			'email_verified' => $u['email_verified'] ?? true,
			'provider'	   => $provider
		];
	}

	// Cas 2 — fallback userinfo endpoint
	if (!$user && !empty($config['userinfo_endpoint'])) {

		include_spip('inc/login_oauth2_userinfo');

		$userinfo = login_oauth2_userinfo_oidc(
			$access_token,
			$config['userinfo_endpoint'],
			$provider
		);

		if (!empty($userinfo['subject'])) {
			$user = $userinfo;
		}
	}

	if (!$user) {
		login_oauth2_rediriger_erreur('erreur_authentification');
	}

	// Récupération du contexte du token
	include_spip('inc/oauth2_client_context');
	$context = oauth2_client_get_auth_context($app);
	$user['oauth2_context'] = $context ?? null;

	// Connexion utilisateur
	include_spip('inc/login_oauth2_liaison');
	login_oauth2_connecter_utilisateur($user);

	// Redirection vers la page demandé, publique par défaut
	include_spip('inc/login_oauth2_redirect');
	login_oauth2_rediriger_succes();
}