<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function mesfavoris_ccn_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" href="' . find_in_path('css/favoris_ccn.css') . '" />' . "\n";
	return $flux;
}

function mesfavoris_ccn_insert_head($flux) {
	// Ne pas ré-insérer la balise script lors d'un rechargement ajax
	// (ex: $.load()) qui recharge un fragment dans une page déjà initialisée
	// (cf thematique_insert_head, même logique).
	if (_request('mode') === 'ajax' || _request('mode') === 'ajax-detail') {
		return $flux;
	}

	$flux .= "<script src='" . find_in_path('js/favoris_ccn.js') . "' defer></script>\n";

	return $flux;
}
