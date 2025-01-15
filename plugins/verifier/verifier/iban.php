<?php

/**
 * API de vérification : vérification de la validité d'un IBAN
 *
 * @plugin     verifier
 * @copyright  2021
 * @author     Mukt
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('lib/php-iban/php-iban');

/**
 * Un numéro IBAN
 * Options :
 * - …
 *
 * @param string|array $valeur
 *   La valeur à vérifier
 * @param array $options
 *   Tableau d'options
 * @param null $valeur_normalisee
 *   Si normalisation a faire, la variable sera remplie par l'IBAN normalisé
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_iban_dist($valeur, $options = [], &$valeur_normalisee = null) {
	$erreur = '';

	if (!verify_iban($valeur)) {
		return _T('verifier:erreur_iban_format');
	}

	if ($options['normaliser'] ?? '') {
		$normaliser = charger_fonction('iban', 'normaliser');
		$valeur_normalisee = $normaliser($valeur, $options, $erreur);
	}

	return $erreur;
}

function normaliser_iban_dist($valeur, $options, &$erreur) {
	return iban_to_machine_format($valeur);
}
