<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\case
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Vérifie que la valeur postée
 * correspond aux valeurs proposées lors de la config de valeur
 * @param array $valeur la valeur postée
 * @param array $description la description de la saisie
 * @return bool true si valeur ok, false sinon,
 **/
function choix_grille_valeurs_acceptables(array $valeur, array $description): bool {
	$options = $description['options'];
	$rows = array_keys(saisies_chaine2tableau($description['options']['data_rows'] ?? []));
	$cols = array_keys(saisies_chaine2tableau($description['options']['data_cols'] ?? []));
	foreach ($rows as $row) {
		if (!in_array($valeur[$row], $cols)) {
			return false;
		}
	}

	return true;
}

