<?php

/**
 * Obtenir des informations sur la saisie fieldset
 *
 * @return SPIP\Saisies\Listes
 **/


// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un fieldset, c'est une saisie contenante
 * @param array $saisie
 * @return bool true
**/
function fieldset_est_avec_sous_saisies(array $saisie): bool {
	// Type de retour à changer en true lorsqu'on sera dans version de PHP qui l'autorise
	return true;
}


/**
 * Un fieldset, ca n'est pas un champ
 * @param array $saisie
 * @return bool false
**/
function fieldset_est_champ(array $saisie): bool {
	// Type de retour à changer en false lorsqu'on sera dans version de PHP qui l'autorise
	return false;
}
