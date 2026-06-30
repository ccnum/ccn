<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Liste les providers OAuth2 déclarés dans le pipeline login_oauth2_providers
 */
function login_oauth2_lister_providers() {

	// Déclarations Providers en pipelines
	$declares = pipeline('login_oauth2_providers', []) ?: [];

	if (!$declares) {
		return [];
	}

	// Configuration des Providers en configuration
	$config_all = lire_config('login_oauth2/providers', []);

	// Recherche des Providers valides en configuration -> discovery -> pipelines
	$valides = [];

	foreach ($declares as $key => $provider) {

		$config = $config_all[$key] ?? [];

		if (empty($config['actif'])) {
			continue;
		}

		$mode = $provider['mode'] ?? 'generic';
		// Paramètres OAuth2 par priorité: config (prioritaire) -> discovery (si présent) -> pipeline (defaults)
		$oauth2_config = [
			'client_id' => $config['client_id'] ?? null,
			'client_secret' => $config['client_secret'] ?? null,
			'authorize_endpoint' => $config['authorize_endpoint'] ?? null,
			'token_endpoint' => $config['token_endpoint'] ?? null,
			'scope' => $config['scope'] ?? null,
			'issuer' => $config['issuer'] ?? null,
		];

		$defaults = $provider['oauth2_defaults'] ?? [];
	
	// Configuration prioritaire sur les déclarations par défaut des pipelines
		$oauth2 = array_merge($defaults, array_filter($oauth2_config));

		// Traitement du discovery
		if (!empty($config['discovery'])) {
			include_spip('inc/login_oauth2_oidc');
			$discovery = login_oauth2_oidc_discover($config['discovery']);

		// On force à partir du discovery que si non défini
		if ($discovery) {
				if (empty($oauth2['authorize_endpoint']) && !empty($discovery['authorization_endpoint'])) {
					$oauth2['authorize_endpoint'] = $discovery['authorization_endpoint'];
				}
				if (empty($oauth2['token_endpoint']) && !empty($discovery['token_endpoint'])) {
					$oauth2['token_endpoint'] = $discovery['token_endpoint'];
				}
				if (empty($oauth2['issuer']) && !empty($discovery['issuer'])) {
					$oauth2['issuer'] = $discovery['issuer'];
				}
				// userinfo (hors oauth2)
				if (empty($config['userinfo_endpoint']) && !empty($discovery['userinfo_endpoint'])) {
					$provider['userinfo_endpoint'] = $discovery['userinfo_endpoint'];
				}
			}
		}

		// Sécurité minimale
		if (empty($oauth2['client_id']) || empty($oauth2['client_secret'])) {
			continue;
		}

		// Configuration finale du Provider
		$provider['mode'] = $mode;
		$provider['label'] = $config['label'] ?? $provider['label'];
		$provider['oauth2'] = $oauth2;
		$provider['userinfo_endpoint'] = $config['userinfo_endpoint']?? $provider['userinfo_endpoint']?? null;
		
		$valides[$key] = $provider;
	}

	return $valides;
}