<?php

/**
 * Fonctions spécifiques à la saisie date, chargées avec le squelette.
 *
 * @package SPIP\Saisies\date
 **/

/**
 * Liste les noms de mois dans la langue courante, et au format date en clé.
 *
 * @return array<
 * 	string, // numéro du mois au format date, sur 2 chiffres : 01, 02, etc.
 * 	string  // nom du mois dans la langue courante : janvier, février, etc.
 * >
 */
function saisies_lister_date_noms_mois(): array {
	include_spip('inc/filtres_dates');

	$noms_mois = [];
	for ($i = 1; $i <= 12; $i++) {
		// Nb : on pourrait faire directement _T('date_mois_$i'),
		// mais mieux vaut utiliser la fonction façade.
		$mois = str_pad((string) $i, 2, 0, STR_PAD_LEFT);
		$fake_date = "1970-$mois-01";
		$noms_mois[$mois] = affdate($fake_date, 'nom_mois');
	}

	return $noms_mois;
}

/**
 * Liste les jours d'un mois, au format date en clé.
 *
 * @return array<
 * 	string, // numéro du jour au format date, sur 2 chiffres : 01, 02, etc.
 * 	string  // numéro du jour sur 1 chiffre : 1, 2, etc.
 * >
 */
function saisies_lister_date_jours(): array {
	$jours = array_combine(range(0, 31), range(1, 32));
	$jours = array_map(fn($d) => str_pad($d, 2, '0', STR_PAD_LEFT), array_keys($jours));
	$jours = array_combine($jours, array_keys($jours));

	return $jours;
}