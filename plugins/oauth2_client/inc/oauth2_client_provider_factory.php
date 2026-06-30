<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/oauth2_client_loader');
include_spip('inc/oauth2_client_utils');

/**
 * Fabrique et retourne une instance de provider OAuth2.
 *
 * Cette fonction détermine dynamiquement quel provider utiliser
 * en fonction de la configuration fournie :
 *
 * 1) Provider inline :
 *    - Si authorize_endpoint et token_endpoint sont fournis,
 *      un provider Generic est instancié avec ces endpoints.
 *
 * 2) Provider explicite :
 *    - Si 'provider' est défini dans $config,
 *      la classe correspondante est chargée dynamiquement
 *      (ex: 'keycloak' → OAuth2ClientProviderKeycloak).
 *
 * 3) Fallback :
 *    - Si aucun provider spécifique n’est détecté,
 *      le provider Generic est utilisé par défaut.
 *
 * @param string $app    Identifiant logique de l’application OAuth2
 * @param array  $config Configuration du provider
 *
 * @return object        Instance d’un provider OAuth2
 */
function oauth2_client_provider_factory(string $app, array $config = []) {
	spip_log('oauth2_client_provider_factory: Instancie le provider de l\'app ' . $app, 'oauthclient.'._LOG_DEBUG);

	// Provider inline (Generic explicite)
	if (!empty($config['authorize_endpoint']) && !empty($config['token_endpoint'])) {
	spip_log('Provider inline dans la config app= ' . $app, 'oauthclient.'._LOG_DEBUG);
		oauth2_client_load_class('provider', 'Generic');

		return new OAuth2ClientProviderGeneric([
			'authorize_endpoint' => $config['authorize_endpoint'],
			'token_endpoint'	 => $config['token_endpoint'],
		]);
	}

	// Provider explicite dans la config
	$provider = $config['provider'] ?? null;

	if ($provider) {
	spip_log('Provider explicite dans la config app= ' . $app, 'oauthclient.'._LOG_DEBUG);
	$name = oauth2_client_camelize($provider);

		if (oauth2_client_load_class('provider', $name)) {
			$class = "OAuth2ClientProvider{$name}";
			spip_log("Provider explicite chargé : $class", 'oauthclient.'._LOG_DEBUG);
			return new $class($config);
		}
	}

	// Fallback Generic
	oauth2_client_load_class('provider', 'Generic');
	spip_log('Fallback Generic app= ' . $app, 'oauthclient.'._LOG_DEBUG);

	return new OAuth2ClientProviderGeneric();
}
