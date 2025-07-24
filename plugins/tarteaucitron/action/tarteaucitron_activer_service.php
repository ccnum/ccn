<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Active un service
 * appelé avec ?action=tarteaucitron_activer_service&service=service
 * autorisé pour les seuls webmestres
 */
function action_tarteaucitron_activer_service_dist() {
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

	if ($list_services[$service]['statut'] == 'actif') {
		die('Service déjà actif');
	}

	include_spip('inc/config');

	$services_actifs = lire_config('tarteaucitron/services');
	$services_actifs[$service] = [];

	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);
	$params = $parsed_json->{$service}->{'params'};

	foreach ($params as $param) {
		$services_actifs[$service][$param] = substr($param, 5);
	}

	ecrire_config('tarteaucitron/services/', $services_actifs);
}
