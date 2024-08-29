<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * ajout feuille de style dans le HEAD_CSS
 * @param string $flux
 * @return string
 */
function socialtags_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" type="text/css" href="' . timestamp(find_in_path('socialtags.css')) . '" media="all" />' . "\n";
	return $flux;
}

/**
 * ajout cookie + js
 * @param  $flux
 * @return string
 */
function socialtags_insert_head($flux) {

	// on a besoin de jquery.cookie
	if (!strpos($flux, 'jquery.cookie.js')) {
		$flux .= "<script type='text/javascript' src='" . timestamp(find_in_path('javascript/js.cookie.js')) . "'></script>\n";
	}

	include_spip('inc/filtres');
	// la ressource produite est timestampee
	$jsFile = produire_fond_statique('socialtags.js');

	$flux .= "<script src='$jsFile' type='text/javascript'></script>\n";
	return $flux;
}


// La liste est stockee en format RSS
function socialtags_liste() {
	$rss = null;
	include_spip('inc/syndic');
	lire_fichier(find_in_path('socialtags.xml'), $rss);
	return analyser_backend($rss);
}
