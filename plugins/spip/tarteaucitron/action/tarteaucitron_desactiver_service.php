<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Désactive un service
 * appelé avec ?action=tarteaucitron_desactiver_service&service=service
 * autorisé pour les seuls webmestres
 */
function action_tarteaucitron_desactiver_service_dist() {
	include_spip('inc/autoriser');

	if (!autoriser('webmestre')) {
		die('Pas autorise');
	}

	include_spip('inc/tarteaucitron');

	$service = _request('service');
	$list_services = tarteaucitron_liste_services();

	if (!array_key_exists($service, $list_services)) {
		die('Service inexistant');
	}

	if ($list_services[$service]['statut'] == 'inactif') {
		die('Service déjà inactif');
	}

	include_spip('inc/config');
	$services_actifs = lire_config('tarteaucitron/services');

	unset($services_actifs[$service]);

	ecrire_config('tarteaucitron/services', $services_actifs);
}
