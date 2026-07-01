<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_login_oauth2_charger() {

	// Liste complète des providers déclarés
	include_spip('inc/login_oauth2_providers');
	$providers = pipeline('login_oauth2_providers', []);
	$valeurs = [];

	// Option globale
	$valeurs['forcer_oauth2'] = lire_config('login_oauth2/forcer_oauth2', 0);
	$valeurs['exiger_email_verifie'] = lire_config('login_oauth2/exiger_email_verifie', 0);

	$valeurs['providers'] = [];
	foreach ($providers as $key => $provider) {
		$mode = $provider['mode'] ?? 'generic';
		$defaults = $provider['oauth2_defaults'] ?? [];
		$userinfo_endpoint = $provider['userinfo_endpoint'] ?? null;

		// Config sauvegardée
		$config_saved = lire_config('login_oauth2/providers/'.$key,[]);

		// Fusion simple (config écrase defaults)
		$conf = array_merge($defaults, $config_saved);

		// User info
		$conf['userinfo_endpoint'] = $config_saved['userinfo_endpoint']	?? $provider['userinfo_endpoint'] ?? null;

		// discovery
		$conf['discovery'] = $config_saved['discovery'] ?? $provider['discovery'] ?? null;

		// Métadonnées
		$conf['mode']  = $mode;
		$conf['label'] = $config_saved['label']	?? $provider['label'] ?? $key;

		// Actif (false par défaut si absent)
		$conf['actif'] = !empty($config_saved['actif']);

		// Affichage en lecture seule si discovery
		if (!empty($conf['discovery'])) {
			include_spip('inc/login_oauth2_oidc');
			$discovery = login_oauth2_oidc_discover($conf['discovery']);
			if ($discovery) {
				$conf['_discovery_values'] = [
					'authorize_endpoint' => $discovery['authorization_endpoint'] ?? '',
					'token_endpoint'     => $discovery['token_endpoint'] ?? '',
					'userinfo_endpoint'  => $discovery['userinfo_endpoint'] ?? '',
					'issuer'             => $discovery['issuer'] ?? '',
				];
			}
		}

		$valeurs['providers'][$key] = $conf;
	}

	return $valeurs;
}

function formulaires_configurer_login_oauth2_traiter() {

	// Liste complète des providers déclarés
	include_spip('inc/login_oauth2_providers');
	$providers = pipeline('login_oauth2_providers', []);

	foreach ($providers as $key => $provider) {
		$prefix = 'provider_' . $key . '_';
		$conf = [];

		// Actif
		$conf['actif'] = _request($prefix . 'actif') ? true : false;

		// Champs texte (avec reset possible)
		if (($val = trim((string)_request($prefix . 'label'))) !== '') {
			$conf['label'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'client_id'))) !== '') {
			$conf['client_id'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'client_secret'))) !== '') {
			$conf['client_secret'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'scope'))) !== '') {
			$conf['scope'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'issuer'))) !== '') {
			$conf['issuer'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'authorize_endpoint'))) !== '') {
			$conf['authorize_endpoint'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'token_endpoint'))) !== '') {
			$conf['token_endpoint'] = $val;
		}

		if (($val = trim((string)_request($prefix . 'userinfo_endpoint'))) !== '') {
			$conf['userinfo_endpoint'] = $val;
		}

		// OIDC discovery
		if (($val = trim((string)_request($prefix . 'discovery'))) !== '') {
			$conf['discovery'] = $val;
		}

		ecrire_config('login_oauth2/providers/'.$key, $conf);
	}

	// Option globale
	ecrire_config('login_oauth2/forcer_oauth2',_request('forcer_oauth2') ? 1 : 0);
	ecrire_config('login_oauth2/exiger_email_verifie',_request('exiger_email_verifie') ? 1 : 0);

	return ['message_ok' => _T('login_oauth2:cfg_enregistre')];
}