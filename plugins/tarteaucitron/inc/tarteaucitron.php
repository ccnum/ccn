<?php

/**
 * Fonctions internes du plugin
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
/**
 * Détecter si au moins un service est actif.
 *
 * @return boolean
 */

function tarteaucitron_actif() {
	$actif = false;
	$liste_services = lire_config('tarteaucitron/services', []);

	foreach ($liste_services as $value => $params) {
		if (!empty($value)) {
			$actif = true;
		}
	}
	return $actif;
}

/**
 * Retourne la liste des services TarteAuCitron avec leur statut (actif,inactif)
 * et leur(s) éventuel(s) paramètre(s)
 *
 * @return array
 */

function tarteaucitron_liste_services() {
	$services_actifs = lire_config('tarteaucitron/services', []);
	$list_services = [];
	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);

	foreach ($parsed_json as $service => $prop) {
		$list_services[$service] = [
			'type' => $prop->{'type'},
			'statut' => (array_key_exists($service, $services_actifs)) ? 'actif' : 'inactif',
			'params' => (!empty($prop->{'params'})) ? $prop->{'params'} : [],
		];
	}

	return $list_services;
}

/**
 * Retourne la liste des types des services TarteAuCitron installés
 *
 * @return array
 */

function tarteaucitron_liste_types_actifs() {
	$list_types = [];
	$services = tarteaucitron_liste_services();

	foreach ($services as $service => $prop) {
		if ($prop['statut'] == 'actif') {
			$list_types[$prop['type']][$service] = $prop['params'];
		}
	}

	return $list_types;
}
