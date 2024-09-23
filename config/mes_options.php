<?php
if (preg_match('/ddev.site/', $_SERVER['HTTP_HOST'])) {
	define('_NO_CACHE', -1);
	define('_INTERDIRE_COMPACTE_HEAD_ECRIRE', true);
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set("display_errors", "On");
	define('SPIP_ERREUR_REPORT', E_ALL);
	$GLOBALS['taille_des_logs'] = 500;
	define('_MAX_LOG', 500000);
	define('_LOG_FILELINE', true);
	define('_LOG_FILTRE_GRAVITE', 8);
	define('_DEBUG_SLOW_QUERIES', true);
	define('_BOUCLE_PROFILER', 5000);
	define('_TEST_EMAIL_DEST', 'pierrekuhn@ik.me');

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
