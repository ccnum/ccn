<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Initialise le flux d'authentification OAuth2 auprès d'un provider.
 *
 * Cette action est appelée lorsque l'utilisateur clique sur
 * un bouton "Se connecter avec ..." dans le formulaire de login.
 *
 * Elle :
 * 1. récupère le provider demandé
 * 2. vérifie qu'il est configuré et actif
 * 3. prépare la configuration OAuth2
 * 4. délègue l’exécution du flux OAuth2 à l’API oauth2_client_get_access_token()
 *
 * La fonction oauth2_client_get_access_token() :
 * - retourne immédiatement un access_token si déjà valide
 * - ou initialise le flux OAuth2 (state + redirection provider)
 *
 * Le provider redirigera ensuite l'utilisateur vers l'action
 * `login_oauth2_callback`, qui finalise l'authentification.
 *
 * URL appelée :
 *     spip.php?action=login_oauth2&arg=provider=XXX
 *
 * @action login_oauth2
 *
 * @return void
 */
 function action_login_oauth2_dist() {
	spip_log('action_login_oauth2_dist()', 'login_oauth.'._LOG_DEBUG);
	spip_log('Lancement action de login_oauth2', 'login_oauth.'._LOG_DEBUG);

	// Récupération du Provider
	$provider = _request('arg');
	if (!$provider) {
		spip_log("Provider manquant", 'oauthclient._ERREUR');
		return false;
	}
	
	include_spip('inc/login_oauth2_error');
	if (!$provider) {
		login_oauth2_rediriger_erreur('erreur_provider_invalide');
	}

	include_spip('inc/login_oauth2_providers');
	$providers = login_oauth2_lister_providers();

	if (empty($providers[$provider])) {
	login_oauth2_rediriger_erreur('erreur_provider_invalide');
	}

	// Préparer du callback demandé par le plugin Oauth2_client
	$config = $providers[$provider];
	include_spip('inc/login_oauth2_auth');
	$oauth2_params = login_oauth2_preparer_oauth2_callback($provider,$config);

	if (!$oauth2_params) {
		login_oauth2_rediriger_erreur('erreur_authentification');
	}

	// nom application OAuth_client = Provider login_Oauth2 (par simplicité)
	$app = $provider;

	// Lancement du protocole oauth2 via l'API oauth2_client_get_access_token
	include_spip('inc/oauth2_client');
	$access_token=oauth2_client_get_access_token($app,$oauth2_params);

	// On force sur le renvoi sur le callback si oauth2_client_get_access_token ne l'a pas fait
	// car il existe alors un token local valide (cas 1)
	if ($access_token) {
		include_spip('inc/headers');
		$url = generer_url_action('login_oauth2_callback','provider=' . urlencode($provider));
		$url = html_entity_decode($url);
		redirige_par_entete($url);
		exit;
		}

	// On ne doit plus passer là...
	exit;
}