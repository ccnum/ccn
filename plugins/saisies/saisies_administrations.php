<?php

/**
 * Fichier gérant l'installation et désinstallation du plugin
 *
 * @package SPIP\Saisies\Installation
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
/**
 * Installation/maj des config de saisies
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
 */
function saisies_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];
	$maj['create'] = [
		['saisies_corriger_assests']
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Corrige une honteuse coquille
 **/
function saisies_corriger_assests() {
	$config = lire_config('saisies/assests_global');
	if ($config) {
		effacer_config('saisies/assests_global');
		ecrire_config('saisies/assets_global', $config);
	}
}

function saisies_vider_tables($nom_meta_base_version) {
	effacer_config('saisies');
	effacer_meta($nom_meta_base_version);
}
