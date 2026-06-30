<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function mesfavoris_ccn_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" href="' . find_in_path('css/favoris_ccn.css') . '" />' . "\n";
	return $flux;
}
