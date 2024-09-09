<?php

/**
 * Obtenir des informations sur la saisie conteneur_inline
 *
 * @return SPIP\Saisies\Listes
 **/


// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un conteneur_inline, c'est une saisie contenante
 * @param array $saisie
 * @return bool true
**/
function conteneur_inline_est_avec_sous_saisies(array $saisie): bool {
	// Type de retour à changer en true lorsqu'on sera dans version de PHP qui l'autorise
	return true;
}


/**
 * Un conteneur_inline, ca n'a pas de label
 * @param array $saisie
 * @return bool false
**/
function conteneur_inline_est_labelisable(array $saisie): bool {
	// Type de retour à changer en false lorsqu'on sera dans version de PHP qui l'autorise
	return false;
}

/**
 * Un conteneur_inline, ca n'est pas un champ
 * @param array $saisie
 * @return bool false
**/
function conteneur_inline_est_champ(array $saisie): bool {
	// Type de retour à changer en false lorsqu'on sera dans version de PHP qui l'autorise
	return false;
}
