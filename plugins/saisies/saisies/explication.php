<?php

/**
 * Obtenir des informations sur la saisie champ
 *
 * @return SPIP\Saisies\Listes
 **/


// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Une explication, ca n'est pas un champ
 * @param array $saisie
 * @return bool false
**/
function explication_est_champ(array $saisie): bool {
	// Type de retour à changer en false lorsqu'on sera dans version de PHP qui l'autorise
	return false;
}


/**
 * Retourne le label de la saisie `explication`
 * Par ordre de priorité le `titre`
 * sinon le `texte`
 * @param array $saisie
 * @return string
**/
function explication_get_label(array $saisie): string {
	$titre = $saisie['options']['titre'] ?? '';
	if ($titre) {
		return $titre;
	} else {
		return $saisie['options']['texte'] ?? '';
	}
}
