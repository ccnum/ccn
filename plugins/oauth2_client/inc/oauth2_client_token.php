<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/config');

/**
 * Stocke un token OAuth2 pour une application donnée.
 *
 * Cette fonction :
 * - Génère une clé de stockage selon le contexte (session, user, cron)
 * - Écrase le token existant pour cette clé
 * - Persiste les données via la configuration SPIP (meta)
 *
 * Limitation actuelle :
 * Le stockage en meta SPIP est global, non isolé par session réelle
 * et non optimisé pour des accès concurrents (CRON, multi-utilisateurs).
 *
 * @param string      $app     Identifiant de l'application OAuth2
 * @param array       $token   Token OAuth2 normalisé (access_token, refresh_token, etc.)
 * @param string|null $mode    Mode de stockage :
 *                            - 'session' (défaut)
 *                            - 'user'
 *                            - 'cron'
 * @param int|null    $user_id Identifiant utilisateur SPIP (requis si mode = 'user')
 *
 * @return void
 */
function oauth2_client_store_token(	$app, $token, $mode = 'session', $user_id = null): void {
	spip_log("oauth2_client_store_token()", 'oauthclient.'._LOG_DEBUG);

	$key = oauth2_client_get_storage_key($app, $mode, $user_id);
	$meta = lire_config('oauth2_client', []);

	// Nettoyage tokens expirés stockés
	$meta = oauth2_client_clean_meta($meta);

	// Nouveau token
	$meta[$key] = [
		'access_token'  => $token['access_token'] ?? null,
		'id_token'      => $token['id_token'] ?? null,
		'refresh_token' => $token['refresh_token'] ?? null,
		'token_type'    => $token['token_type'] ?? 'bearer',
		'expires_at'    => $token['expires_at'] ?? null,
		'user'          => $token['user'] ?? null,
	];

	ecrire_config('oauth2_client', $meta);
}

/**
 * Supprime les tokens associés à une application et un contexte.
 *
 * - Permet suppression ciblée (session / user / cron)
 *
 * @param string      $app
 * @param string|null $mode
 * @param int|null    $user_id
 * @return void
 */
function oauth2_client_delete_token($app, $mode = 'session', $user_id = null ): void {
	spip_log("oauth2_client_delete_token()", 'oauthclient.'._LOG_DEBUG);

	$key = oauth2_client_get_storage_key($app, $mode, $user_id);
	$meta = lire_config('oauth2_client', []);

	if (isset($meta[$key])) {
		unset($meta[$key]);
		ecrire_config('oauth2_client', $meta);
	}
}

/**
 * Retourne le token stocké pour une application et un contexte.
 *
 * @param string      $app
 * @param string|null $mode
 * @param int|null    $user_id
 * @return array|false
 */
function oauth2_client_get_stored_token($app, $mode = 'session', $user_id = null): array|false {
	spip_log("oauth2_client_get_stored_token()", 'oauthclient.'._LOG_DEBUG);

	$key = oauth2_client_get_storage_key($app, $mode, $user_id);
	$meta = lire_config('oauth2_client', []);

	if (empty($meta[$key]['access_token'])) {
		return false;
	}

	return [
		'access_token' 	=> $meta[$key]['access_token'],
		'id_token'      => $meta[$key]['id_token'] ?? null,
		'refresh_token' => $meta[$key]['refresh_token'] ?? null,
		'expires_at'   	=> $meta[$key]['expires_at'] ?? null,
		'expired'       => oauth2_client_is_expired($meta[$key]['expires_at'] ?? null),
		'user' 			=> $meta[$key]['user'] ?? null,
	];
}

/**
 * Vérifie si un token est expiré.
 *
 * - Gère les anciens formats
 * - Retourne true si invalide ou non numérique
 *
 * @param mixed $expires_at
 * @return bool
 */
function oauth2_client_is_expired($expires_at): bool {
	
	if (empty($expires_at)) {
		return true;
	}

	// Si jamais ancien format tableau
	if (is_array($expires_at)) {
		$expires_at = $expires_at['expires_at'] ?? null;
	}

	if (!is_numeric($expires_at)) {
		return true;
	}

	return time() >= (int)$expires_at;
}

/**
 * Normalise une réponse OAuth2 et enregistre le token.
 *
 * Cette fonction :
 * - Vérifie la présence du access_token
 * - Calcule la date d'expiration (expires_at)
 * - Normalise les champs du token (type, refresh, id_token)
 * - Extrait les informations utilisateur depuis un id_token (OIDC)
 * - Valide le nonce en mode OpenID Connect (authorization_code)
 * - Stocke le token selon le mode (session, user ou cron)
 *
 * @param string      $app     Identifiant de l'application OAuth2 configurée
 * @param array       $data    Données retournées par le serveur OAuth2
 * @param array       $config  Configuration de l'application (optionnelle)
 * @param string|null $mode    Mode de stockage :
 *                            - 'session' (par défaut)
 *                            - 'user'
 *                            - 'cron'
 * @param int|null    $user_id Identifiant utilisateur (requis si mode = 'user')
 *
 * @return array|false Tableau du token normalisé en cas de succès, false en cas d'erreur
 */
 function oauth2_client_normalize_and_store_token(
	string $app,
	array $data,
	array $config = [],
	?string $mode = 'session',
	?int $user_id = null
): array|false {

	if (empty($data['access_token'])) {
		spip_log("OAuth2: access_token manquant ($app)", 'oauthclient.' . _LOG_ERREUR);
		return false;
	}

	$expires_at = null;

	if (!empty($data['expires_in'])) {
		$expires_at = time() + max(0, (int)$data['expires_in'] - 60);
	}

	$token = [
		'access_token'  => $data['access_token'],
		'id_token'      => $data['id_token'] ?? null,
		'refresh_token' => $data['refresh_token'] ?? null,
		'token_type'    => strtolower($data['token_type'] ?? 'bearer'),
		'expires_at'    => $expires_at,
	];

	// Extraction user depuis id_token
	if (!empty($token['id_token'])) {
		include_spip('inc/oauth2_client_oidc');
		$token['user'] = oauth2_client_oidc_extract_user($token['id_token']);
	}

	// Validation OIDC (uniquement login)
	if (
		!empty($config['oidc']['enabled']) &&
		!empty($token['id_token']) &&
		($config['grant_type'] ?? '') === 'authorization_code' &&
		$mode === 'session'
	) {
		include_spip('inc/session');

		$oauth2 = session_get('oauth2') ?? [];
		$state  = _request('state');
		$stored_nonce = $oauth2[$app][$state]['nonce']['value'] ?? null;

		$payload = oauth2_client_oidc_decode_payload($token['id_token']);

		if (empty($payload['nonce']) || $payload['nonce'] !== $stored_nonce) {
			spip_log("OIDC: nonce invalide ($app)", 'oauthclient.'._LOG_ERREUR);
			return false;
		}
	}

	// Enregistrement
	oauth2_client_store_token($app, $token, $mode, $user_id);

	return $token;
}

/**
 * Retourne une clé de stockage unique selon le contexte.
 *
 * Modes supportés :
 * - session : utilisateur en cours de login (PKCE, state)
 * - user    : utilisateur SPIP identifié (id_auteur)
 * - cron    : application (tâche technique, sans session)
 *
 * @param string      $app
 * @param string|null $mode     session|user|cron (default: session)
 * @param int|null    $user_id  requis si mode=user
 * @return string
 */
function oauth2_client_get_storage_key(string $app, $mode = 'session', $user_id = null): string {

	switch ($mode) {

		case 'cron':
			// Token technique global
			return $app . '_cron';

		case 'user':
			// Token lié à un utilisateur SPIP
			if (!$user_id) {
				spip_log("OAUTH: user_id manquant en mode user ($app)", 'oauthclient.' . _LOG_ERREUR);
				return $app . '_invalid_user';
			}
			return $app . '_' . $user_id;

		case 'session':
		default:
			// Token temporaire lié à la session (login OAuth)
			include_spip('inc/session');

			if (!session_id()) {
				session_start();
			}

			return $app . '_' . session_id();
	}
}

/**
 * Nettoie et normalise le stockage des tokens OAuth2 en meta SPIP.
 *
 * Cette fonction :
 * - Supprime les entrées invalides (non tableau)
 * - Supprime les tokens expirés
 * - Limite le nombre d'entrées pour éviter une croissance non contrôlée
 *
 * @param mixed $meta Valeur brute issue de lire_config()
 * @return array Structure nettoyée et normalisée
 */
function oauth2_client_clean_meta($meta): array {

	// Meta corrompue alors reset
    if (!is_array($meta)) {
        spip_log("oauth2_client_clean_meta: meta invalide, reset", 'oauthclient.'._LOG_ERREUR);
        $meta = [];
	}

	// Nettoyage des tokens expirés
    $now = time();
    foreach ($meta as $k => $v) {
        if (!is_array($v)) {
            unset($meta[$k]);
            continue;
        }
        if (!empty($v['expires_at']) && $v['expires_at'] < $now) {
            unset($meta[$k]);
        }
    }

    // Sécurité de 20 tokens stockés max
    $max = 20;
    if (count($meta) > $max) {
        uasort($meta, function ($a, $b) {
            return ($a['expires_at'] ?? 0) <=> ($b['expires_at'] ?? 0);
        });
        $meta = array_slice($meta, -$max, null, true);
    }
    return $meta;
}