<?php

/**
 * Fonctions spécifiques à une valeur
 *
 * @package SPIP\valeurs\selection
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
function selection_valeurs_acceptables($valeur, array $description): bool {
	$options = $description['options'];
	if (($options['multiple'] ?? '')) {
		include_spip('saisies/selection_multiple');
		return selection_multiple_valeurs_acceptables($valeur, $description);
	}
	$ancienne_valeur = $description['options']['ancienne_valeur'] ?? '';
	if ($valeur == '' && !($options['obligatoire'] ?? '')) {
		return true;
	}
	if ($options['choix_alternatif'] ?? false) {
		return true;
	}
	if (saisies_saisie_est_gelee($description) && isset($options['defaut'])) {
		return $valeur == $options['defaut'];
	} else {
		$data = saisies_trouver_data($description, true);
		$data = saisies_aplatir_tableau($data);
		$data = array_keys($data);

		// Le problème des depubliés : il faut pouvoir autoriser les précédentes valeurs, mais c'est tout
		$depublie_choix = saisies_normaliser_liste_choix($options['depublie_choix'] ?? []);
		$data = array_diff($data, $depublie_choix);
		if ($ancienne_valeur === $valeur && in_array($valeur, $depublie_choix)) {
			$data[] = $valeur;
		}

		// Et maintenant on vérifie
		return (in_array($valeur, $data));
	}
	$data = saisies_trouver_data($description, true);
	$data = saisies_aplatir_tableau($data);
	$data = array_keys($data);
	if (isset($options['disable_choix'])) {
		include_spip('inc/saisies');
		$disable_choix = saisies_normaliser_disable_choix($options['disable_choix']);
		$data = array_diff($data, $disable_choix);
	}
	return (in_array($valeur, $data));
}
