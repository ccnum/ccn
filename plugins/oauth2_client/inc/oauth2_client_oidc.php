<?php

/**
 * Récupère les clés publiques JWKS du provider OIDC
 *
 * - Utilise un cache local (1h)
 * - Télécharge depuis {issuer}/protocol/openid-connect/certs
 *
 * @param string $issuer
 * @return array|null
 */
function oauth2_client_oidc_get_jwks(string $issuer): ?array {
	spip_log('oauth2_client_oidc_get_jwks()','oauthclient.'._LOG_DEBUG);

	$meta = lire_config('oauth2_client_oidc/'.$issuer, []);

	// cache 1h
	if (!empty($meta['jwks']['keys'])
		&& !empty($meta['fetched_at'])
		&& $meta['fetched_at'] > (time() - 3600)
	) {
		return $meta['jwks']['keys'];
	}

	include_spip('inc/distant');

	// récupération configuration OIDC
	$well_known = rtrim($issuer,'/') . '/.well-known/openid-configuration';

	$res = recuperer_url($well_known);

	if (empty($res['page'])) {
		spip_log("OIDC: impossible de récupérer openid-configuration",'oauthclient.'._LOG_ERREUR);
		return null;
	}

	$config = json_decode($res['page'], true);

	if (empty($config['jwks_uri'])) {
		spip_log("OIDC: jwks_uri absent",'oauthclient.'._LOG_ERREUR);
		return null;
	}

	$res = recuperer_url($config['jwks_uri']);

	if (empty($res['page'])) {
		spip_log("OIDC: impossible de récupérer JWKS",'oauthclient.'._LOG_ERREUR);
		return null;
	}

	$jwks = json_decode($res['page'], true);

	if (empty($jwks['keys'])) {
		spip_log("OIDC: JWKS vide",'oauthclient.'._LOG_ERREUR);
		return null;
	}

	ecrire_config('oauth2_client_oidc/'.$issuer, [
		'jwks' => $jwks,
		'fetched_at' => time()
	]);

	return $jwks['keys'];
}

/**
 * Décode le header d’un JWT (Base64URL → JSON)
 *
 * @param string $jwt
 * @return array|null
 */
function oauth2_client_oidc_decode_header(string $jwt): ?array {

	$parts = explode('.', $jwt);
	if (count($parts) !== 3) {
		return null;
	}

	$header = json_decode(
		base64_decode(strtr($parts[0], '-_', '+/')),
		true
	);

	return $header ?: null;
}

/**
 * Recherche une clé JWKS correspondant à un kid donné
 *
 * @param array  $jwks
 * @param string $kid
 * @return array|null
 */
function oauth2_client_oidc_find_key(array $jwks, string $kid): ?array {

	foreach ($jwks as $key) {

		if (!empty($key['kid']) && $key['kid'] === $kid) {
			return $key;
		}

	}

	spip_log(
		"OIDC: clé JWKS introuvable (kid=$kid)",
		'oauthclient.'._LOG_ERREUR
	);

	return null;
}

/**
 * Vérifie la signature RSA (RS256) d’un JWT
 *
 * - Convertit la JWK en PEM
 * - Utilise openssl_verify
 *
 * @param string $jwt
 * @param array  $jwk
 * @return bool
 */
function oauth2_client_oidc_verify_signature(string $jwt, array $jwk): bool {

	[$header64, $payload64, $signature64] = explode('.', $jwt);

	$data = $header64 . '.' . $payload64;

	$signature = base64_decode(strtr($signature64, '-_', '+/'));

	// Convertir JWK → PEM
	$pem = oauth2_client_oidc_jwk_to_pem($jwk);

	if (!$pem) {
		return false;
	}

	$ok = openssl_verify(
		$data,
		$signature,
		$pem,
		OPENSSL_ALGO_SHA256
	);

	return $ok === 1;
}

/**
 * Valide complètement un id_token OpenID Connect.
 *
 * Vérifications effectuées :
 *  - Présence du header (kid, alg RS256)
 *  - Récupération de la clé publique JWKS
 *  - Vérification cryptographique de la signature
 *  - Décodage du payload JWT
 *  - Vérification issuer (iss)
 *  - Vérification audience (aud)
 *  - Vérification expiration (exp) avec tolérance
 *  - Vérification nonce (anti-replay)
 *  - Vérification at_hash si présent (anti-substitution)
 *
 * Nettoie le contexte session (nonce / PKCE) en cas de succès.
 *
 * @param string	  $id_token	  JWT OIDC reçu
 * @param string	  $client_id	 Client ID OAuth2
 * @param string	  $issuer		Issuer attendu
 * @param string	  $app		   Code application
 * @param string	  $state		 State OAuth2 courant
 * @param string|null $access_token  Access token associé (optionnel)
 *
 * @return bool true si validation complète réussie
 */
function oauth2_client_oidc_validate_id_token(string $id_token,string $client_id,string $issuer,string $app,string $state, ?string $access_token = null): bool {
spip_log("oauth2_client_oidc_validate_id_token", 'oauthclient.'._LOG_DEBUG);

	$header = oauth2_client_oidc_decode_header($id_token);

	if (empty($header['kid']) || $header['alg'] !== 'RS256') {
	   return false;
	}

	$jwks = oauth2_client_oidc_get_jwks($issuer);
	if (!$jwks) {
	   return false;
	}

	$key = oauth2_client_oidc_find_key($jwks, $header['kid']);
	if (!$key) {
		spip_log("OIDC: Pas de clé JWKS avec le kid", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	if (!oauth2_client_oidc_verify_signature($id_token, $key)) {
		spip_log("OIDC: Signature invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	$payload = json_decode(
		base64_decode(strtr(explode('.', $id_token)[1], '-_', '+/')),
		true
	);

	if (!$payload) {
		return false;
	}

	// Vérifications standard OIDC

	if (($payload['iss'] ?? null) !== $issuer) {
		spip_log("OIDC: issuer invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	if (
		($payload['aud'] ?? null) !== $client_id &&
		!in_array($client_id, (array)$payload['aud'], true)
	) {
		spip_log("OIDC: audience invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// tolérance 60s
	$now = time();
	if (($payload['exp'] ?? 0) < ($now - 60)) {
		spip_log("OIDC: token expiré", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// Vérification NONCE
	include_spip('inc/session');
	$oauth2 = session_get('oauth2') ?? [];
	$expected_nonce = $oauth2[$app][$state]['nonce']['value'] ?? null;
	$payload_nonce = $payload['nonce'] ?? null;

	if ($expected_nonce && $payload_nonce !== $expected_nonce){
		spip_log("OIDC: nonce invalide", 'oauthclient.'._LOG_ERREUR);
		return false;
	}

	// Vérification at_hash si présent
	if (!empty($payload['at_hash']) && !empty($access_token)) {
		$expected = oauth2_client_oidc_compute_at_hash($access_token);

		if (!hash_equals($payload['at_hash'], $expected)) {
			spip_log("OIDC: at_hash invalide", 'oauthclient.'._LOG_ERREUR);
			return false;
		}
}


	// Nettoyage nonce & PKCE

	unset($oauth2[$app][$state]);
	session_set('oauth2', $oauth2);

	return true;
}

/**
 * Décode le payload d’un JWT
 *
 * @param string $jwt
 * @return array|null
 */
function oauth2_client_oidc_decode_payload(string $jwt): ?array {

	$parts = explode('.', $jwt);
	if (count($parts) !== 3) {
		return null;
	}

	return json_decode(
		base64_decode(strtr($parts[1], '-_', '+/')),
		true
	);
}

/**
 * Décode le payload d’un JWT
 *
 * @param string $jwt
 * @return array|null
 */
function oauth2_client_oidc_extract_user(string $id_token): array {

	$payload = oauth2_client_oidc_decode_payload($id_token);
	if (!$payload) {
		return [];
	}

	return [
		'sub'		=> $payload['sub'] ?? null,
		'email'	  => $payload['email'] ?? null,
		'username'   => $payload['preferred_username'] ?? null,
		'given_name' => $payload['given_name'] ?? null,
		'family_name'=> $payload['family_name'] ?? null,
		'name'	   => $payload['name'] ?? null,
	];
}

/**
 * Extrait les informations utilisateur standard depuis un id_token
 *
 * @param string $id_token
 * @return array
 */
function oauth2_client_oidc_discovery(string $issuer): ?array {

	$url = rtrim($issuer, '/') . '/.well-known/openid-configuration';

	include_spip('inc/distant');
	$res = recuperer_url($url);

	if (empty($res['page'])) {
		return null;
	}

	return json_decode($res['page'], true);
}

/**
 * Convertit une clé JWK RSA (n, e) en clé publique PEM
 *
 * Permet la vérification openssl_verify().
 *
 * @param array $jwk
 * @return string|null
 */
function oauth2_client_oidc_jwk_to_pem(array $jwk): ?string {

	if (($jwk['kty'] ?? null) !== 'RSA') {
		return null;
	}

	$n = $jwk['n'] ?? null;
	$e = $jwk['e'] ?? null;

	if (!$n || !$e) {
		return null;
	}

	$modulus = base64_decode(strtr($n, '-_', '+/'));
	$exponent = base64_decode(strtr($e, '-_', '+/'));

	// ASN.1 RSA PUBLIC KEY
	$modulus = "\x02" . oauth2_client_asn1_encode_length(strlen($modulus)) . $modulus;
	$exponent = "\x02" . oauth2_client_asn1_encode_length(strlen($exponent)) . $exponent;

	$rsa = "\x30" .
		oauth2_client_asn1_encode_length(strlen($modulus . $exponent)) .
		$modulus . $exponent;

	// SubjectPublicKeyInfo wrapper
	$algo = hex2bin('300d06092a864886f70d0101010500');
	$bitstring = "\x03" .
		oauth2_client_asn1_encode_length(strlen($rsa) + 1) .
		"\x00" . $rsa;

	$sequence = "\x30" .
		oauth2_client_asn1_encode_length(strlen($algo . $bitstring)) .
		$algo . $bitstring;

	return "-----BEGIN PUBLIC KEY-----\n" .
		chunk_split(base64_encode($sequence), 64, "\n") .
		"-----END PUBLIC KEY-----\n";
}

/**
 * Encode une longueur ASN.1 (DER)
 *
 * Utilisé pour construire dynamiquement une clé RSA PEM.
 *
 * @param int $length
 * @return string
 */
function oauth2_client_asn1_encode_length(int $length): string {

	if ($length <= 0x7F) {
		return chr($length);
	}

	$temp = ltrim(pack('N', $length), "\x00");

	return chr(0x80 | strlen($temp)) . $temp;
}

/**
 * Calcule la valeur at_hash selon la spécification OpenID Connect.
 *
 * Utilisé pour vérifier l’intégrité de l’access_token associé
 * à un id_token (OIDC Authorization Code Flow).
 *
 * Algorithme (RS256) :
 *  - SHA256(access_token)
 *  - Prendre les 128 premiers bits (16 octets)
 *  - Encoder en Base64URL sans padding
 *
 * @param string $access_token Access token brut reçu du provider
 * @return string Valeur at_hash encodée Base64URL
 */
function oauth2_client_oidc_compute_at_hash(string $access_token): string {

	$hash = hash('sha256', $access_token, true);

	// 128 premiers bits (16 octets)
	$left = substr($hash, 0, 16);

	return rtrim(strtr(base64_encode($left), '+/', '-_'), '=');
}
