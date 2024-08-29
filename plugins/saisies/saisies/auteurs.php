<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\auteurs
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Vérifie que la valeur postée
 * correspond aux valeurs proposées lors de la config de valeur
 * @param string $valeur la valeur postée
 * @param array $description la description de la saisie
 * @return bool true si valeur ok, false sinon,
 **/
function auteurs_valeurs_acceptables($valeur, $description) {
	$type_saisie = (empty($description['multiple']) ? 'selection' : 'selection_multiple');
	include_spip("saisies/$type_saisie");
	$valeurs_acceptables = "{$type_saisie}_valeurs_acceptables";
	return $valeurs_acceptables($valeur, $description);
}
