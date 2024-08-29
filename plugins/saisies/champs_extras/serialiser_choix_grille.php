<?php

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Sérialise les réponses à un champ extra de type `choix_grille` pour encodage en base.
 * @param array $extra
 *     La valeur reçue en POST
 * @param array $saisie
 *     La description de la saisie
 * @return string
 *		 Forme serialisé, en l'occurence avec saisies_tableau2chaine
 **/
function champs_extras_serialiser_choix_grille($extra, $saisie) {
	include_spip('inc/saisies');
	return saisies_tableau2chaine($extra);
}
