<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function favoris_ccn_categories() {
	return [
		'feu'   => ['html' => '&#128293;', 'label' => 'Feu'],
		'coeur' => ['html' => '&#10084;&#65039;', 'label' => 'Cœur'],
		'pouce' => ['html' => '&#128077;', 'label' => 'Pouce'],
	];
}

/** Filtre SPIP : encode une valeur en JSON. */
function favoris_ccn_json($val) {
	return json_encode($val, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
}

/**
 * Retourne un tableau ['feu' => 3, 'coeur' => 1, 'pouce' => 0]
 * pour un objet donné.
 */
function favoris_ccn_compter($objet, $id_objet) {
	$categories = favoris_ccn_categories();
	$compteurs  = array_fill_keys(array_keys($categories), 0);

	$rows = sql_allfetsel(
		'categorie, COUNT(*) as total',
		'spip_favoris',
		[
			'objet = '    . sql_quote($objet),
			'id_objet = ' . intval($id_objet),
			'categorie IN (' . implode(',', array_map('sql_quote', array_keys($categories))) . ')',
		],
		'categorie'
	);

	foreach ($rows as $row) {
		if (isset($compteurs[$row['categorie']])) {
			$compteurs[$row['categorie']] = (int) $row['total'];
		}
	}

	return $compteurs;
}

/**
 * Retourne la catégorie du favori de l'auteur connecté sur cet objet,
 * ou '' s'il n'en a pas.
 */
function favoris_ccn_mon_favori($objet, $id_objet) {
	$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
	if (!$id_auteur) {
		return '';
	}

	$row = sql_fetsel(
		'categorie',
		'spip_favoris',
		[
			'id_auteur = ' . $id_auteur,
			'id_objet = '  . intval($id_objet),
			'objet = '     . sql_quote($objet),
		]
	);

	return $row ? $row['categorie'] : '';
}
