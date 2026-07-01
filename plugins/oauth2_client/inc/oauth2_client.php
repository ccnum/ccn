<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/oauth2_client_provider_factory');
include_spip('inc/oauth2_client_grant_factory');
include_spip('inc/oauth2_client_token');
include_spip('inc/oauth2_client_error');
include_spip('inc/oauth2_client_state');

/**
 * Construit l'URL d'autorisation OAuth2
 *
 * @param string $app
 * @param array  $config
 * @param array  $options
 * @return string|false
 */
function oauth2_client_get_authorization_url(string $app,array $config = [],array $options = []) {
	spip_log('oauth2_client_get_authorization_url()','oauthclient.'._LOG_DEBUG);

	// sécurité application
	if (!$app || !is_string($app)) {
		spip_log("OAuth2: nom d'application invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	$provider = oauth2_client_provider_factory($app, $config);

	if (!$provider || !method_exists($provider, 'buildAuthorizationUrl')) {
		spip_log("OAuth2: provider impossible de construire l'URL authorize ($app)", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// state obligatoire
	if (empty($options['state'])) {
		try {
			$options['state'] = bin2hex(random_bytes(16));
		}
		catch (\Throwable $e) {
			spip_log('OAuth2: génération du state impossible','oauthclient.'._LOG_ERREUR);
			return false;
		}
	}

	// Nettoyage des éventuels state résiduels renregistrés en local
	include_spip('inc/oauth2_client_state');
	oauth2_client_state_cleanup(600);

	// Par sécurité, vérificaton d'une session active
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	oauth2_client_state_store($options['state'], [
		'session_id' => session_id(),
		'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
		'created_at' => time(),
		'app'   => $app,
		]);

	include_spip('inc/session');
	$oauth2 = session_get('oauth2') ?? [];

	// OIDC nonce
	if (!empty($config['oidc']['enabled'])) {

		try {
			$nonce = bin2hex(random_bytes(16));
		}
		catch (\Throwable $e) {
			spip_log('OAuth2: génération du nonce impossible','oauthclient.'._LOG_ERREUR);
			return false;
		}

		$oauth2[$app][$options['state']]['nonce'] = [
			'value'      => $nonce,
			'created_at' => time(),
		];

		$options['nonce'] = $nonce;
	}

	// PKCE
	if (!empty($config['pkce']['enabled'])) {

		include_spip('inc/oauth2_client_pkce');

		oauth2_client_contexte_cleanup($app);

		$method = $config['pkce']['method'] ?? 'S256';

		$verifier  = OAuth2_PKCE::generate_verifier();
		$challenge = OAuth2_PKCE::generate_challenge($verifier, $method);

		$oauth2[$app][$options['state']]['pkce'] = [
			'verifier'   => $verifier,
			'method'     => $method,
			'created_at' => time(),
		];

		$options['code_challenge'] = $challenge;
		$options['code_challenge_method'] = $method;
	}

	session_set('oauth2', $oauth2);

	// pipeline
	$provider = pipeline('oauth2_client_authorization_provider', [
		'args' => [
			'app'    => $app,
			'config' => $config,
		],
		'data' => $provider,
	]);

	// sécurité pipeline
	if (!$provider || !method_exists($provider, 'buildAuthorizationUrl')) {
		spip_log("OAuth2: provider invalide après pipeline ($app)", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	try {
		return $provider->buildAuthorizationUrl($config, $options);
	}
	catch (\Throwable $e) {
		spip_log("OAuth2: erreur lors de la construction de l'URL authorize ($app) : ".$e->getMessage(),'oauthclient.'._LOG_ERREUR);
		return false;
	}
}

/**
 * Récupère un token OAuth2 depuis le endpoint du provider.
 *
 * Cette fonction orchestre :
 * - La validation des paramètres (app, grant_type)
 * - L’instanciation du provider OAuth2
 * - L’instanciation du grant (authorization_code, refresh_token, client_credentials)
 * - L’exécution de pipelines SPIP pour permettre l’altération du provider et du grant
 * - L’appel au endpoint token avec gestion des exceptions
 * - La normalisation et le stockage du token
 *
 * Grants supportés :
 * - authorization_code
 * - refresh_token
 * - client_credentials
 *
 * @param string      $app     Identifiant de l'application/provider OAuth2
 * @param array       $config  Configuration OAuth2 (grant_type, client_id, secret, code, refresh_token, etc.)
 * @param string|null $mode    Mode de stockage :session(défaut), user ou cron
 * @param int|null    $user_id Identifiant utilisateur SPIP (requis si mode = 'user')
 *
 * @return array|false Token normalisé en cas de succès, false en cas d’erreur
 */
function oauth2_client_get_token($app, array $config = [], $mode = 'session', $user_id = null) {
	spip_log('oauth2_client_get_token()','oauthclient.'._LOG_DEBUG);

	// sécurité application
	if (!$app || !is_string($app)) {
		spip_log("OAuth2: nom d'application invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// grant obligatoire
	if (empty($config['grant_type'])) {
		spip_log('OAuth2: grant_type manquant', 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	$provider = oauth2_client_provider_factory($app, $config);

	if (!$provider || !method_exists($provider, 'requestToken')) {
		spip_log("OAuth2: provider incapable de demander un token ($app)", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// pipeline : modification possible du provider
	$provider = pipeline('oauth2_client_token_provider', [
		'args' => [
			'app'    => $app,
			'config' => $config,
		],
		'data' => $provider,
	]);

	// vérifier que le provider reste valide
	if (!$provider || !method_exists($provider, 'requestToken')) {
		spip_log("OAuth2: provider invalide après pipeline ($app)", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	$grant = oauth2_client_grant_factory($config['grant_type']);

	if (!$grant) {
		spip_log("OAuth2: grant OAuth2 invalide ({$config['grant_type']})", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// pipeline : modification possible du grant
	$grant = pipeline('oauth2_client_grant', [
		'args' => [
			'app'        => $app,
			'grant_type' => $config['grant_type'],
			'config'     => $config,
		],
		'data' => $grant,
	]);

	// appel du provider avec gestion d'exception
	try {
		$data = $provider->requestToken($grant, $config);
	}
	catch (\Throwable $e) {
		spip_log("OAuth2: exception lors de la demande de token ($app) : " . $e->getMessage(), 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	if (!is_array($data)) {
		spip_log("OAuth2: réponse token invalide ($app)", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	return oauth2_client_normalize_and_store_token($app, $data, $config, $mode, $user_id);
}

/**
 * API publique – Orchestrateur du flux OAuth2 (authorization_code)
 *
 * Cette fonction constitue le point d’entrée unique du client OAuth2.
 * Elle pilote l’ensemble du cycle de vie d’un access_token :
 *
 * 1. Retourne un access_token existant s’il est encore valide
 * 2. Gère le retour du provider (callback OAuth2 avec code/state)
 *    - restauration de session si nécessaire (navigateurs type Safari)
 *    - validation PKCE (code_verifier)
 *    - échange authorization_code → access_token
 *    - validation OIDC (id_token, nonce, issuer)
 * 3. Rafraîchit le token via refresh_token si expiré
 * 4. Initialise le flux OAuth2 (génération du state + redirection provider)
 *    si aucune session valide n’existe
 *
 * Flux supporté :
 * - authorization_code uniquement (PKCE optionnel)
 *
 * Fonctionnement :
 * - appelée indifféremment depuis l’action login ou le callback
 * - détecte automatiquement le contexte (initialisation vs callback)
 * - effectue une redirection HTTP uniquement hors callback
 *
 * Sécurité :
 * - protection CSRF via state
 * - stockage serveur du state (fallback Safari / ITP)
 * - validation PKCE (RFC 7636)
 * - validation OIDC (id_token)
 * - nettoyage du contexte après usage
 *
 * @param string      $app     Identifiant de l'application OAuth2
 * @param array       $config  Configuration OAuth2 (client_id, redirect_uri, pkce, oidc…)
 * @param string|null $mode    Mode de stockage : session (défaut), user ou cron
 * @param int|null    $user_id Identifiant utilisateur SPIP (si mode = 'user')
 *
 * @return string|false access_token valide, ou false en cas d’échec
 *                      (une redirection peut être effectuée dans le flux)
 */
 function oauth2_client_get_access_token($app, $config = [], $mode = 'session', $user_id = null) {
	spip_log('oauth2_client_get_access_token()','oauthclient.'._LOG_DEBUG);

	// sécurité application
	if (!$app || !is_string($app)) {
		spip_log("OAuth2: nom d'application invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// cette API ne gère que authorization_code
	if (!empty($config['grant_type']) && $config['grant_type'] !== 'authorization_code') {
		spip_log('OAuth2: get_access_token() ne supporte que authorization_code','oauthclient.' . _LOG_ERREUR);
		return false;
	}

	/*
	 * Cas 1 —  Token valide
	 *
	 */

	// lecture token local
	$local = oauth2_client_get_stored_token($app, $mode, $user_id);

	if ($local && !empty($local['access_token']) && !oauth2_client_is_expired($local)) {
		spip_log('Cas 1: token local valide','oauthclient.'._LOG_DEBUG);
		spip_log('access_token= '.$local['access_token'],'oauthclient.'._LOG_DEBUG);
		return $local['access_token'];
	}
	
	/*
	 * Cas 2 — Retour authorization_code
	 *
	 */

	$code = _request('code')?? null;
	// Réinjection de la session en cas de perte (Safari...)
	$state = _request('state');

	if ($mode === 'session' && $state) {
		$context = oauth2_client_state_load($state);

		if (!$context) {
			spip_log("OAuth2: state intouvable ($app | state=$state): possible perte session",'oauthclient.'._LOG_ERREUR);
		}
	
		// Par sécurité, on vérifie l'existance de la session, sinon on la lance
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		$current_session = session_id();

		// Vérification que la nouvelle session est bien celle sauvegardée sinon on la force à l'ancienne
		// Cas de navigateurs qui perdent la session en multi-sites (Safari...)
		if ($context && !empty($context['session_id'])) {
			$current_session = session_id();
			if ($current_session !== $context['session_id']) {
				if (session_status() === PHP_SESSION_ACTIVE) {
					session_write_close();
				}
				session_id($context['session_id']);
				session_start();
				spip_log("OAuth2: Session restorée via state: old=$current_session new=" . session_id(),'oauthclient.'._LOG_DEBUG);
			}
		}
	}

	if ($mode === 'session' && $code && is_string($code)) {
		spip_log('Cas 2 — Retour authorization_code','oauthclient.'._LOG_DEBUG);

		if (empty($config['redirect_uri'])) {
			spip_log('OAuth2: redirect_uri manquant','oauthclient.'._LOG_ERREUR);
			oauth2_client_error_redirect('redirect_uri_missing', false, $app);
			return false;
		}

		$params = array_merge($config, [
			'app'          => $app,
			'grant_type'   => 'authorization_code',
			'redirect_uri' => $config['redirect_uri'],
			'code'         => $code,
			'state'        => $state,
		]);
	
		// PKCE
		if (!empty($config['pkce']['enabled']) && empty($state)) {
			spip_log('OAuth2: PKCE activé mais state absent','oauthclient.' . _LOG_ERREUR);
			oauth2_client_error_redirect('state_missing', false, $app);
			return false;
		}

		if (!empty($config['pkce']['enabled'])) {

			include_spip('inc/session');
$useragent = $_SERVER['HTTP_USER_AGENT']?? $_SERVER['HTTP_SEC_CH_UA']?? $_SERVER['HTTP_SEC_CH_UA_PLATFORM']?? $_SERVER['HTTP_SEC_CH_UA_MOBILE']??'HTTP_USER_AGENT INCONNU';
spip_log('debug callback HTTP_USER_AGENT: '.$useragent,'oauthclient.'._LOG_DEBUG);
spip_log("debug callback: session_id=" . session_id(), 'oauthclient.'._LOG_DEBUG);
spip_log("debug callback: state=" . $state, 'oauthclient.'._LOG_DEBUG);

			$oauth2 = session_get('oauth2') ?? [];
spip_log("debug callback: session oauth2=" . json_encode($oauth2), 'oauthclient.'._LOG_DEBUG);
			$context = $oauth2[$app][$state]['pkce'] ?? null;

			if (empty($context['verifier'])) {
				spip_log('OAuth2: code_verifier introuvable','oauthclient.' . _LOG_ERREUR);
				oauth2_client_error_redirect('pkce_verifier_missing', false, $app);
				return false;
			}

			$params['code_verifier'] = $context['verifier'];
		}

		$newToken = oauth2_client_get_token($app, $params, $mode, $user_id);
		
		if (!is_array($newToken) || empty($newToken['access_token'])) {
			spip_log('OAuth2: échange token échoué','oauthclient.' . _LOG_ERREUR);
			oauth2_client_error_redirect('token_exchange_failed', false, $app);
			return false;
		}

		// OIDC
		if (!empty($newToken['id_token']) && !empty($config['oidc']['enabled'])) {

			$issuer = $config['issuer'] ?? null;

			if (!$issuer) {
				spip_log('OAuth2: issuer manquant','oauthclient.'._LOG_ERREUR);
				oauth2_client_error_redirect('issuer_missing', false, $app);
				return false;
			}

			if (!oauth2_client_oidc_validate_id_token(
				$newToken['id_token'],
				$config['client_id'],
				$issuer,
				$app,
				$state,
				$newToken['access_token'] ?? null
			)) {
				spip_log("OAuth2: id_token invalide ($app)",'oauthclient.'._LOG_ERREUR);
				oauth2_client_error_redirect('id_token_invalid', true, $app);
				return false;
			}
		}

		// Store token
		oauth2_client_store_token($app, $newToken, $mode, $user_id);
		
		// Store contexte
		include_spip('inc/oauth2_client_context');
		$auth_context = [
			'grant_type' => 'authorization_code',
			'app'   => $app,
			'timestamp'  => time(),
		];

		oauth2_client_store_auth_context($app, $auth_context);

		// nettoyage PKCE
		if (!empty($config['pkce']['enabled']) && !empty($state)) {

			include_spip('inc/session');
			$oauth2 = session_get('oauth2') ?? [];
			unset($oauth2[$app][$state]);
			session_set('oauth2', $oauth2);
		}
		
		// Nettoyage du state de la session 
		oauth2_client_state_delete($state);

		// Retour du token
		spip_log('access_token= '.$newToken['access_token'],'oauthclient.'._LOG_DEBUG);
		return $newToken['access_token'];
	}

	/*
	 * Cas 3 — Refresh token
	 *
	 */

	if ($local && !empty($local['refresh_token'])) {
		spip_log('Cas 3 — refresh token','oauthclient.'._LOG_DEBUG);
		$params = array_merge($config, [
			'grant_type'    => 'refresh_token',
			'refresh_token' => $local['refresh_token'],
		]);
		$newToken = oauth2_client_get_token($app, $params, $mode, $user_id);

		if (is_array($newToken) && !empty($newToken['access_token'])) {

			// Store token
			oauth2_client_store_token($app, $newToken, $mode, $user_id);

			// Store contexte
			include_spip('inc/oauth2_client_context');
			$auth_context = [
				'grant_type' => 'refresh_token',
				'provider'   => $app,
				'timestamp'  => time(),
				];
			oauth2_client_store_auth_context($app, $auth_context);
			spip_log('access_token= '.$newToken['access_token'],'oauthclient.'._LOG_DEBUG);
			return $newToken['access_token'];
		}

		spip_log('Cas 3: refresh_token invalide','oauthclient.'._LOG_DEBUG);
		oauth2_client_delete_token($app, $mode, $user_id);
	}

	/*
	 * Cas 4 — Redirection provider
	 *
	 */

	$is_callback = (_request('code') && _request('state'));

    // redirection autorisée uniquement hors callback
	if ($mode === 'session' && !$is_callback) {
	$url = oauth2_client_get_authorization_url($app, $config);

		if ($url) {
			spip_log('Cas 4 — redirection provider','oauthclient.'._LOG_DEBUG);
			include_spip('inc/headers');
$useragent = $_SERVER['HTTP_USER_AGENT']?? $_SERVER['HTTP_SEC_CH_UA']?? $_SERVER['HTTP_SEC_CH_UA_PLATFORM']?? $_SERVER['HTTP_SEC_CH_UA_MOBILE']??'HTTP_USER_AGENT INCONNU';
spip_log('debug redirect HTTP_USER_AGENT: '.$useragent,'oauthclient.'._LOG_DEBUG);
spip_log("debug redirect: session_id=" . session_id(), 'oauthclient.'._LOG_DEBUG);
			redirige_par_entete($url);
			exit;
		}
	}

	/*
	 * Cas 5 — échec final
	 *
	 */

	spip_log('OAuth2: échec du flux OAuth','oauthclient.' . _LOG_ERREUR);
	oauth2_client_error_redirect('oauth_flow_failed', false, $app);

	return false;
}

/**
 * Nettoie les contextes OAuth2 expirés en session.
 *
 * Supprime les entrées PKCE / nonce associées aux anciens
 * state afin de :
 *  - éviter l’accumulation en session
 *  - limiter les risques de replay attack
 *  - maintenir une surface d’attaque minimale
 *
 * Le nettoyage s’applique à tous les state du provider.
 *
 * @param string $app Nom de l’application OAuth2
 * @param int    $ttl Durée de vie maximale d’un state (en secondes)
 *                    Par défaut : 600s (10 minutes)
 * @return void
 */
function oauth2_client_contexte_cleanup($app, $ttl = 600): void {
	include_spip('inc/session');
	$oauth2 = session_get('oauth2') ?? [];

	$now = time();

	foreach ($oauth2 as $app_key => &$states) {
		foreach ($states as $state => $ctx) {

			$created = $ctx['pkce']['created_at'] ?? 0;

			if (($now - $created) > $ttl) {
				unset($states[$state]);
			}
		}
	}

	session_set('oauth2', $oauth2);
}

/**
 * Nettoie l’URL de callback OAuth2 puis redirige.
 *
 * Supprime les paramètres sensibles transmis par le provider :
 *  - code
 *  - state
 *  - session_state
 *  - iss
 *  - error
 *  - action
 *
 * Objectifs :
 *  - empêcher la réutilisation du authorization_code
 *  - éviter le replay du callback
 *  - retirer les paramètres sensibles de l’URL visible
 *  - améliorer l’UX après authentification
 *
 * Effectue une redirection HTTP immédiate.
 *
 * @return void
 */
function oauth2_client_clean_callback_url(): void {

    include_spip('inc/headers');

    // URL courante
    $url = self();

    // Paramètres à supprimer
    $params_to_clean = [
        'code',
        'state',
        'session_state',
        'iss',
        'error',
        'error_description',
        'action',
        'provider'
    ];

    foreach ($params_to_clean as $param) {
        $url = parametre_url($url, $param, '', '&');
    }

    // Nettoyage éventuel ?& restant
    $url = preg_replace('/\?&+/', '?', $url);
    $url = rtrim($url, '?');

    redirige_par_entete($url);
    exit;
}

/**
 * Retourne l'utilisateur OIDC courant.
 *
 * - Priorise les données déjà extraites et stockées dans le token
 * - Fallback sur décodage du id_token si nécessaire
 *
 * @param string      $app
 * @param string|null $mode
 * @param int|null    $user_id
 *
 * @return array|null Données utilisateur OIDC ou null si indisponible
 */
function oauth2_client_get_user(string $app, $mode = 'user', $user_id = null): ?array {

	$token = oauth2_client_get_stored_token($app, $mode, $user_id);

	if (!$token) {
		return null;
	}

	// Cas normal
	if (!empty($token['user']) && is_array($token['user'])) {
		return $token['user'];
	}

	// Fallback
	if (!empty($token['id_token'])) {
		include_spip('inc/oauth2_client_oidc');
		return oauth2_client_oidc_extract_user($token['id_token']);
	}

	return null;
}