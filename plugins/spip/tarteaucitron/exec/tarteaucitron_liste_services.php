<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne la lise des services avec leur type et leur statut (installe/desinstalle)
 * au format json
 * appelé avec ?exec=tarteaucitron_liste_services
 */
function exec_tarteaucitron_liste_services_dist() {
	include_spip('inc/tarteaucitron');
	include_spip('inc/autoriser');

	if (!autoriser('webmestre')) {
		die('Pas autorise');
	}

	header('Content-Type: application/json');
	$list_services = tarteaucitron_liste_services();

	echo json_encode($list_services);
}
