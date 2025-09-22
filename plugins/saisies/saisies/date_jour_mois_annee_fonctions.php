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
function saisies_lister_date_noms_mois(?string $locale): array {
	$locale = $locale ?: $GLOBALS['spip_lang'] ?? '';
	$locale = strstr($locale, '_', true) ?: $locale; // fr_tu → fr
	$formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, 'LLLL');
	$mois = [];
	for ($i = 1; $i <= 12; $i++) {
		$m = (string) $i;
		$key = str_pad($m, 2, 0, STR_PAD_LEFT);
		$date = DateTime::createFromFormat('!m', $i); // ! to reset Y/M/D
		$mois[$key] = $formatter->format($date);
	}

	return $mois;
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