<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/oauth2_client_loader');
include_spip('inc/oauth2_client_utils');

/**
 * Fabrique et retourne une instance de Grant OAuth2.
 *
 * Cette fonction instancie dynamiquement la classe correspondant
 * au grant_type fourni.
 *
 * Exemple :
 *   - authorization_code  → OAuth2ClientGrantAuthorizationCode
 *   - refresh_token       → OAuth2ClientGrantRefreshToken
 *   - client_credentials  → OAuth2ClientGrantClientCredentials
 *
 * Le nom de classe est construit automatiquement via camelize().
 *
 * @param string $grant_type  Type de grant OAuth2 (RFC 6749)
 *
 * @return object|null        Instance du grant ou null si inconnu
 */
function oauth2_client_grant_factory(string $grant_type) {
	spip_log('oauth2_client_grant_factory: Instancie le grant '.$grant_type, 'oauthclient.'   ._LOG_DEBUG);

	// authorization_code → AuthorizationCode	
	$name = oauth2_client_camelize($grant_type);

	if (oauth2_client_load_class('grant', $name)) {
		$class = "OAuth2ClientGrant{$name}";
		return new $class();
	}

	spip_log("Grant OAuth2 inconnu: $grant_type", 'oauth2_client' . _LOG_ERREUR);
	return null;
}
