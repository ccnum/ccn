<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\hidden
 **/




/**
 * Vérifie que la valeur postée
 * correspond aux valeurs proposées lors de la config de valeur
 * @param string $valeur la valeur postée
 * @param array $description la description de la saisie
 * @return bool true si valeur ok, false sinon,
 **/
function hidden_valeurs_acceptables($valeur, $description) {
	if (isset($description['options']['defaut']) && $description['options']['defaut'] != $valeur) {
		return false;
	} else {
		return true;
	}
}
