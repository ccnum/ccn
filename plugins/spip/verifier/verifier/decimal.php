<?php

/**
 * API de vérification : vérification de la validité d'un nombre décimal
 *
 * @plugin     verifier
 * @copyright  2018
 * @author     Les Développements Durables
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie qu'un nombre décimal cohérent peut être extrait de la valeur
 * Options :
 * - min : valeur minimale acceptée
 * - max : valeur maximale acceptée
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 *   Si ce tableau associatif contient une valeur pour 'min' ou 'max', un contrôle supplémentaire sera effectué.
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_decimal_dist($valeur, $options = [], &$valeur_normalisee = null) {
	$erreur = _T('verifier:erreur_decimal');

	// Si on teste une chaine, on regarde si on doit changer son formatage
	if (is_string($valeur)) {
		// Permettre aux gens de se tromper en insérant des espaces en trop
		$valeur = trim($valeur);

		// Si on demande à normaliser, on le fait avant les autres tests
		if ($options['normaliser'] ?? '') {
			// Dans tous les cas on enlève les espaces (1 300,5 / 15 300 200)
			$valeur = str_replace(' ', '', $valeur);

			// S'il y a une virgule, c'est le séparateur (1300,5 / 1.300,5)
			// ou s'il y a plusieurs points à la fois (15.300.200)
			if (
				strpos($valeur, ',') !== false
				|| substr_count($valeur, '.') > 1
			) {
				// On supprime les éventuels points
				$valeur = str_replace('.', '', $valeur);

				// Et on remplace la virgule éventuelle par un point
				$valeur = str_replace(',', '.', $valeur);
			}

			$valeur_normalisee = $valeur;
		}

		if (($options['separateur'] ?? '') != '') {
			$valeur = str_replace($options['separateur'], '.', $valeur);
		}
	}

	// Pas de tableau ni d'objet
	if (is_numeric($valeur) && $valeur == floatval($valeur)) {
		// Si c'est une chaine on convertit en flottant
		$valeur = floatval($valeur);
		$ok = true;
		$erreur = '';

		if (isset($options['min'])) {
			$min = floatval(str_replace(',', '.', $options['min']));
			$ok = ($ok && ($valeur >= $min));
		}

		if (isset($options['max'])) {
			$max = floatval(str_replace(',', '.', $options['max']));
			$ok = ($ok && ($valeur <= $max));
		}

		if (!$ok) {
			if (isset($options['min']) && isset($options['max'])) {
				$erreur = _T('verifier:erreur_entier_entre', $options);
			} elseif (isset($options['max'])) {
				$erreur = _T('verifier:erreur_entier_max', $options);
			} else {
				$erreur = _T('verifier:erreur_entier_min', $options);
			}
		}

		// On vérifie le nombre de décimales après la virgule
		if (
			isset($options['nb_decimales'])
			&& ($nb_decimales = $options['nb_decimales'])
			&& round($valeur, $nb_decimales) != $valeur
		) {
			$erreur = _T('verifier:erreur_decimal_nb_decimales', ['nb_decimales' => $nb_decimales]);
		}
	}

	return $erreur;
}
