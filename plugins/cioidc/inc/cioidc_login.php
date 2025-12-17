<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Declenchement de l'authentification OIDC puis redirection
 */
include_spip('inc/headers');
include_spip('inc/session');
include_spip('inc/cookie');
include_spip('inc/texte');
include_spip('base/abstract_sql');
include_spip('inc/headers');

include_spip('inc/cioidc_commun');

// include the class autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

// memoriser l'adresse de redirection
include_spip('inc/cookie');
// SPIP 3.2 n'accepte pas l'option 'httponly'
if ($GLOBALS['spip_version_branche'] >= 4.2) {
	spip_setcookie('cioidc_redirect', cioidc_self(), ['httponly' => true]);
} else {
	spip_setcookie('cioidc_redirect', cioidc_self());
}


// pour la solution hybride :
// poser un cookie si l'utilisateur a cliqué sur le lien "Utiliser l'authentification centralisée"
$request_cioidc = _request('cioidc');
if ($request_cioidc) {
	if ($request_cioidc == 'oui' || intval($request_cioidc) >= 1) {
		if (!isset($_COOKIE['cioidc_sso'])) {
			$ci_id_random = mt_rand(1, 999999);

			// SPIP 3.2 n'accepte pas l'option 'httponly'
			if ($GLOBALS['spip_version_branche'] >= 4.2) {
				spip_setcookie('cioidc_sso', $ci_id_random, ['httponly' => true]);
			} else {
				spip_setcookie('cioidc_sso', $ci_id_random);
			}
		}
		if (!isset($_COOKIE['cioidc_choix'])) {
			// SPIP 3.2 n'accepte pas l'option 'httponly'
			if ($GLOBALS['spip_version_branche'] >= 4.2) {
				spip_setcookie('cioidc_choix', intval($request_cioidc), ['httponly' => true]);
			} else {
				spip_setcookie('cioidc_choix', intval($request_cioidc));
			}
		}
	}
}


// configuration OIDC

$ci_id_serveur_auth = 0;

// Cas avec des serveurs additionnels
$ci_nbre_serveurs_additionnels = cioidc_nombre_serveurs_additionnels();
if ($ci_nbre_serveurs_additionnels >= 1) {
	// authentification demandee par un clic sur le lien
	if ($request_cioidc && intval($request_cioidc) >= 1) {
		$ci_id_serveur_auth = intval($request_cioidc);
	}
}

$config_oidc = cioidc_configuration_serveur_oidc($ci_id_serveur_auth);

if ($config_oidc) {
	$oidc = new OpenIDConnectClient(
		$config_oidc['url_serveur'],
		$config_oidc['client_nom'],
		$config_oidc['client_secret']
	);

	// Well Known Config (pour éviter d'interroger à chaque fois le serveur d'authentification)
	$well_known_config = cioidc_well_known_config($ci_id_serveur_auth);
	if ($well_known_config) {
		foreach ($well_known_config as $key => $value) {
			$oidc->providerConfigParam([$key => $value]);
		}
	}

	// Ne pas utiliser client_secret_basic
	$oidc->providerConfigParam(['token_endpoint_auth_methods_supported' => []]);

	// Register the post-login callback URL
	if (isset($config_oidc['redirect_url_avec_pi'])) {
		$redirect_url_avec_pi = $config_oidc['redirect_url_avec_pi'];
	} else {
		$redirect_url_avec_pi = 'oui';
	}
	$oidc->setRedirectURL(cioidc_redirect_url($redirect_url_avec_pi));

	// Scopes additionnels
	if (isset($config_oidc['scopes']) && $config_oidc['scopes']) {
		$oidc->addScope($config_oidc['scopes']);
	}

	// acr
	if (isset($config_oidc['acr']) && $config_oidc['acr']) {
		$oidc->addAuthParam(['acr_values' => $config_oidc['acr']]);
	}

	// Http Proxy
	if (isset($config_oidc['http_proxy']) && $config_oidc['http_proxy'] == 'oui') {
		include_spip('inc/distant');
		$http_proxy = need_proxy($config_oidc['url_serveur']);
		if ($http_proxy) {
			$oidc->setHttpProxy($http_proxy);
		}
	}

	try {
		// forcer l'authentication
		$oidc->authenticate();

		// recuperer userinfo
		$user_info = $oidc->requestUserInfo();
		$attribute = $config_oidc['uid_claim'];
		$ci_oidc_userid = $user_info->$attribute;
	} catch(Exception $e){
		spip_log($e, 'cioidc');

		$ciredirect = generer_url_public('cioidc_erreur4');
		include_spip('inc/headers');
		redirige_par_entete($ciredirect);
	}

}
