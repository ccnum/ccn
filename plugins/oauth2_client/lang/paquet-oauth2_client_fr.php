<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// C
    'configuration_oauth2_client' => 'OAuth2 Client',

    // B
    'oauth2_client_nom' => 'OAuth2 Client',
    'oauth2_client_slogan' => 'Client OAuth2 générique pour SPIP',
    'oauth2_client_description' =>
        'Fournit une API OAuth2 native pour SPIP prenant en charge authorization_code, refresh_token et client_credentials, avec gestion du cycle de vie des tokens et providers extensibles.',
];
