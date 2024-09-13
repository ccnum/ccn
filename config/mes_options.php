<?php
if (preg_match('/ddev.site/', $_SERVER['HTTP_HOST'])) {
	$rep = 'sites/';
	$site = $_SERVER['HTTP_HOST'];
	$path = _DIR_RACINE . $rep . $site . '/';

	// ordre de recherche des chemins
	define(
		'_SPIP_PATH',
		$path . ':' .
			_DIR_RACINE . ':' .
			_DIR_RACINE . 'squelettes-dist/:' .
			_DIR_RACINE . 'prive/:' .
			_DIR_RESTREINT
	);

	// demarrage du site
	spip_initialisation(
		($path . _NOM_PERMANENTS_INACCESSIBLES),
		($path . _NOM_PERMANENTS_ACCESSIBLES),
		($path . _NOM_TEMPORAIRES_INACCESSIBLES),
		($path . _NOM_TEMPORAIRES_ACCESSIBLES)
	);
}