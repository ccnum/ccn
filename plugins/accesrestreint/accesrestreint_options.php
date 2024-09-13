<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


if (isset($GLOBALS['meta']['accesrestreint_base_version'])) {
	// Si on n'est pas connecte, aucune autorisation n'est disponible
	// pas la peine de sortir la grosse artillerie
	if (!isset($GLOBALS['visiteur_session']['id_auteur'])) {
		$GLOBALS['accesrestreint_zones_autorisees'] = '';
	} else {
		// Pipeline : calculer les zones autorisees, sous la forme '1,2,3'
		// TODO : avec un petit cache pour eviter de solliciter la base de donnees
		$GLOBALS['accesrestreint_zones_autorisees'] =
			pipeline('accesrestreint_liste_zones_autorisees', '');
	}

	// Ajouter un marqueur de cache pour le differencier selon les autorisations
	if (!isset($GLOBALS['marqueur'])) {
		$GLOBALS['marqueur'] = '';
	}
	$GLOBALS['marqueur'] .= ':accesrestreint_zones_autorisees='
		. $GLOBALS['accesrestreint_zones_autorisees'];
}

/**
 * Calcul unifié et centralisé du hash associé à un document
 * @param int $id_document
 * @param string $fichier
 * @return string
 */
function accesrestreint_calculer_cle_document($id_document, $fichier) {
	if (!function_exists('calculer_cle_action')) {
		include_spip('inc/securiser_action');
	}

	$sign = [$id_document, $fichier];

	// si _ACCESRESTREINT_SECRET_SIGNATURE_DOCUMENTS est definie, on l'ajoute : cela permet d'invalider les cles dans la nature est d'en regenerer de nouvelles
	// ie on peut avoir des urls temporaires en liant ce secret a la date (mais cache a gerer, car il n'y a pas de recouvrement, le changement est brutal)
	if (defined('_ACCESRESTREINT_SECRET_SIGNATURE_DOCUMENTS') and _ACCESRESTREINT_SECRET_SIGNATURE_DOCUMENTS) {
		$sign[] = _ACCESRESTREINT_SECRET_SIGNATURE_DOCUMENTS;
	}

	// cette url doit etre publique !
	$cle = calculer_cle_action(implode(',', $sign));

	return $cle;
}
