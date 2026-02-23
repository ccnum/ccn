<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\oui_non
 **/


function oui_non_get_markup(array $saisie): array {
	return [
		'conteneur_tag' => 'fieldset',
		'conteneur_label' => 'legend',
	];
}

/**
 * Vérifie que la valeur postée
 * correspond aux valeurs proposées lors de la config de valeur
 * @param string $valeur la valeur postée
 * @param array $description la description de la saisie
 * @return bool true si valeur ok, false sinon,
 **/
function oui_non_valeurs_acceptables($valeur, $description) {
	include_spip('saisies/case');
	return case_valeurs_acceptables($valeur, $description);
}
