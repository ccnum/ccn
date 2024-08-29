<?php

/**
 * Fonctions spécifiques à une saisie
 *
 * @package SPIP\Saisies\selection_multiple
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
function selection_multiple_valeurs_acceptables($valeur, $description) {
	if (!is_array($valeur)) {
		if ($valeur) {
			$valeur = explode(' ', $valeur);
		} else {
			$valeur = [];
		}
	}
	$data = saisies_aplatir_tableau(saisies_trouver_data($description, true));

	$depublie = saisies_normaliser_liste_choix($description['options']['depublie_choix'] ?? '');
	// ... sauf si c'était une valeur précédement enregistré en base
	$ancienne_valeur = $description['options']['ancienne_valeur'] ?? [];
	if ($ancienne_valeur) {//Potentiellement '' lors de la première soumission du formulaire
		$depublie = array_diff($depublie, $ancienne_valeur);
	}
	$choix_possibles = array_keys($data, true);
	$choix_possibles = array_diff($choix_possibles, $depublie);// Ce qui a été dépublié n'est pas acceptable
	if (
		isset($valeur['choix_alternatif'])
		&& ($description['options']['choix_alternatif'] ?? '')
	) {
		unset($valeur['choix_alternatif']);
	}
	if (
		saisies_saisie_est_gelee($description)
		&& isset($description['options']['defaut'])
	) {
		// Si valeur gelée, on vérifie qu'il n'y ni plus ni moins dans ce qui a été postée
		$defaut = saisies_valeur2tableau($description['options']['defaut']);
		$intersection = array_intersect($defaut, $valeur);
		// L'intersection doit avoir le même nombre de valeur que le défaut. S'il a moins, c'est qu'on supprimé des valeurs, ou renommé
		// L'intersection doit avoir le même nombre de valeur que posté. S'il y en a moins, c'est qu'on a posté de nouvelle valeur
		// Sinon c'est bon
		if (count($intersection) != count($defaut)) {
			return false;
		} elseif (count($intersection) != count($valeur)) {
			return false;
		} else {
			return true;
		}
	}

	if (isset($description['options']['disable_choix'])) {
		include_spip('inc/saisies');
		$disable_choix = saisies_normaliser_liste_choix($description['options']['disable_choix']);
		$choix_possibles = array_diff($choix_possibles, $disable_choix);
	}
	$diff = array_diff($valeur, $choix_possibles);
	if (count($diff)) {
		return false;
	}
	return true;
}
