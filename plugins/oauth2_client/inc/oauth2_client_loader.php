<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/oauth2_client_utils');

/**
 * Charge une classe OAuth2 Client selon sa catégorie
 *
 * @param string $type   provider | grant | token
 * @param string $name   Nom CamelCase du composant
 * @return bool
 */
function oauth2_client_load_class(string $type, string $name): bool {
	spip_log('oauth2_client_load_class: Charge une classe OAuth2 type= '.$type.' name= '.$name,'oauthclient.'  ._LOG_DEBUG);

	// Normalisation les noms de path et de classe
	$file_type = 'oauth_'.strtolower($type);
	$file_name = oauth2_client_camelize($name);
	$class_type = oauth2_client_camelize($type);
	$class_name = oauth2_client_camelize($name);
	
	$file = "class/$file_type/$file_name";
	spip_log('file= '.$file,'oauthclient.'  ._LOG_DEBUG);
	include_spip($file);
		
	$class = "OAuth2Client{$class_type}{$class_name}";
	return class_exists($class);
}
