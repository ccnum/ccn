<?php

/**
 * API de vérification : vérification de la validité d'un nombre entier
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
 * Vérifie qu'un entier cohérent peut être extrait de la valeur
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
function verifier_entier_dist($valeur, $options = []) {
	$erreur = _T('verifier:erreur_entier');
	// Permettre aux gens de se tromper en insérant des espaces en trop...
	if (is_string($valeur)) {
		$valeur = trim($valeur);
	}
	// Pas de tableau ni d'objet
	if (is_numeric($valeur) && $valeur == intval($valeur)) {
		// Si c'est une chaine on convertit en entier
		$valeur = intval($valeur);
		$ok = true;
		$erreur = '';

		if (isset($options['min'])) {
			$ok = ($ok && ($valeur >= $options['min']));
		}
		if (isset($options['max'])) {
			$ok = ($ok && ($valeur <= $options['max']));
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
	}

	return $erreur;
}
