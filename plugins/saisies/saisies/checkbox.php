<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\checkbox
 **/



function checkbox_get_markup(array $saisie): array {
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
function checkbox_valeurs_acceptables($valeur, $description) {
	include_spip('saisies/selection_multiple');
	return selection_multiple_valeurs_acceptables($valeur, $description);
}
