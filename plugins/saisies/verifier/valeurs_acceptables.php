<?php

/**
 * API de vérification : vérification de la validité des valeurs acceptanles
 *
 * @plugin     saisies
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
/**
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options les options de vérification
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_valeurs_acceptables_dist($valeur, $options) {
	include_spip('inc/saisies_verifier');
	$description = $options['_saisie'] ?? [];
	if (!$description) {
		return '';
	}
	$erreur = '';
	$type = $description['saisie'] ?? '';
	$saisie = $description['nom'] ?? '';
	if (include_spip("saisies/$type")) {
		$f = $type . '_valeurs_acceptables';
		if (function_exists($f)) {
			if (!$f($valeur, $description)) {
				$erreur = _T('saisies:erreur_valeur_inacceptable');
				$valeur = json_encode($valeur);
				spip_log("Tentative de poste de valeur innaceptable pour $saisie de type $type. Valeur postée : $valeur", 'saisies' . _LOG_AVERTISSEMENT);
			}
		} else {
			spip_log("Pas de fonction de vérification pour la saisie $saisie de type $type", 'saisies' . _LOG_INFO);
		}
	} else {
		spip_log("Pas de fonction de vérification pour la saisie $saisie de type $type", 'saisies' . _LOG_INFO);
	}

	return $erreur;
}
