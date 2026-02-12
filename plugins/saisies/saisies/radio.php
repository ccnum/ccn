<?php

/**
 * Fonctions spécifiques à une valeur
 *
 * @package SPIP\valeurs\radio
 **/


function radio_get_markup(array $saisie): array {
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
function radio_valeurs_acceptables($valeur, $description) {
	include_spip('saisies/selection');
	// Structurellement, une saisie radio ou une saisie select, c'est un choix parmis N options.
	// Donc la vérif des données postées est la même
	return selection_valeurs_acceptables($valeur, $description);
}
