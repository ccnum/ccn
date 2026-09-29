<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\case
 **/


/**
 * Le markup de la saisie case est variable.
 * Si on a à la fois label + label_case, alors on on est en fieldset + legend
 * sinon on est en div + label
**/
function case_get_markup(array $saisie): array {
	if (
		($saisie['options']['label_case'] ?? '')
		&& ($saisie['options']['label'] ?? '')
	) {
		return [
			'conteneur_tag' => 'fieldset',
			'conteneur_label' => 'legend',
		];
	} else {
		return [
			'conteneur_tag' => 'div',
			'conteneur_label' => 'label',
		];
	}
}

/**
 * Vérifie que la valeur postée
 * correspond aux valeurs proposées lors de la config de valeur
 * @param string $valeur la valeur postée
 * @param array $description la description de la saisie
 * @return bool true si valeur ok, false sinon,
 **/
function case_valeurs_acceptables($valeur, $description) {
	$options = $description['options'];
	$valeur_oui = isset($options['valeur_oui']) ? $options['valeur_oui'] : 'on';
	$valeur_non = isset($options['valeur_non']) ? $options['valeur_non'] : '';
	if (saisies_saisie_est_gelee($description)) {
		if (isset($options['defaut'])) {
			$defaut = $valeur_oui;
		} else {
			$defaut = $valeur_non;
		}
		return $valeur == $defaut;
	} else {
		return ($valeur == $valeur_oui || $valeur == $valeur_non);
	}
}

/**
 * Retourne le label de la saisie `case`
 * Par ordre de priorité le `label_case`
 * sinon le `label`
 * @param array $saisie
 * @return string
**/
function case_get_label(array $saisie): string {
	$label_case = $saisie['options']['label_case'] ?? '';
	if ($label_case) {
		return $label_case;
	} else {
		return $saisie['options']['label'] ?? '';
	}
}
