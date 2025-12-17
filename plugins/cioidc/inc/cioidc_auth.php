<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

// Compatibilité avec SPIP 4.4
if (file_exists(__DIR__ . '/../../../vendor/spip-league/kernel/boot.php')) {
        require_once __DIR__ . '/../../../vendor/autoload.php';
}

// ou est l'espace prive ?
if (!defined('_DIR_RESTREINT_ABS')) {
	define('_DIR_RESTREINT_ABS', 'ecrire/');
}
include_once _DIR_RESTREINT_ABS . 'inc_version.php';

include_spip('inc/cioidc_commun');

require_once __DIR__ . '/../vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

// configuration OIDC

$ci_id_serveur_auth = 0;

// Cas avec des serveurs additionnels
$ci_nbre_serveurs_additionnels = cioidc_nombre_serveurs_additionnels();
if ($ci_nbre_serveurs_additionnels >= 1) {
	// authentification demandee par un clic sur le lien
	if (isset($_COOKIE['cioidc_choix']) && intval(isset($_COOKIE['cioidc_choix'])) >= 1) {
		$ci_id_serveur_auth = intval($_COOKIE['cioidc_choix']);
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
		$oidc->authenticate();

		$cioidc_id_token = $oidc->getIdToken();
		spip_log($cioidc_id_token, 'cioidc');

		$user_info = $oidc->requestUserInfo();
		spip_log($user_info, 'cioidc');

		$attribute = $config_oidc['uid_claim'];
		$ci_oidc_userid = $user_info->$attribute;
		spip_log($attribute, 'cioidc');
		spip_log($ci_oidc_userid, 'cioidc');

		$cioidc_tableau_pipeline = ['args' => [$attribute => $ci_oidc_userid], 'data' => (array) $user_info];
		spip_log($cioidc_tableau_pipeline, 'cioidc');

		pipeline('cioidc_userinfo', $cioidc_tableau_pipeline);

		include_spip('inc/cioidc_session');
		$ciredirect = cioidc_session($ci_oidc_userid, $cioidc_id_token, $ci_id_serveur_auth, $cioidc_tableau_pipeline);
	} catch(Exception $e){
		spip_log($e, 'cioidc');

		$ciredirect = generer_url_public('cioidc_erreur4');
		include_spip('inc/headers');
		redirige_par_entete($ciredirect);
	}
}
