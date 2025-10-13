<?php
/**
 * Plugin Adminer pour Spip
 * Licence GPL 3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Commentez cette ligne si vous voulez donner un acces eventuel aux autres bases masquees
// cette option est non fonctionnelle en SQLite, on la desactive par defaut
#define('_ADMINER_VERROUILLER_DB',true);

// aiguiller sur adminer si les bonnes conditions
if (
	isset($_SERVER['REQUEST_URI']) 
	&& strpos($_SERVER['REQUEST_URI'],'prive.php') !== false 
	&& !_DIR_RESTREINT
) {
	$f = _request('file');
	$adminer_file_ok = $f && in_array($f, ['default.css', 'functions.js', 'favicon.ico', 'jush.js'], true) && _request('version');
	$adminer_cookie_ok = isset($_COOKIE['spip_adminer']) && $_COOKIE['spip_adminer'];
	
	if ($adminer_file_ok || $adminer_cookie_ok) {
		if (
			!_request('page') 
			|| (
				(_request('username') || _request('db'))
				&& (_request('server') || _request('sqlite'))
			)
		) {
			$GLOBALS['fond'] = 'adminer';
		}
	}

	unset($f, $adminer_file_ok, $adminer_cookie_ok);
}

function autoriser_adminer_menu_dist($faire,$quoi,$id,$qui,$options){
	return autoriser('adminer','',$id,$qui,$options);
}

function autoriser_adminer_dist($faire,$quoi,$id,$qui,$options){
	return autoriser('webmestre','',$id,$qui,$options);
}
