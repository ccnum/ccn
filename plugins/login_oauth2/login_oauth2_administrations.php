<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation et mises à jour de la base du plugin login_oauth2.
 *
 * Crée les tables nécessaires au plugin lors de l'installation
 * et applique les éventuelles migrations de schéma.
 *
 */
function login_oauth2_upgrade($nom_meta_base_version, $version_cible) {

	$maj = [];

	// Version initiale
	$maj['create'] = [
		['maj_tables', ['spip_auteurs_oauth2']]
	];

	//Ajout du champ grant_type
	$maj['0.2.0'] = [
		['maj_tables', ['spip_auteurs_oauth2']]
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Désinstallation du plugin login_oauth2.
 *
 * Supprime les tables et les metas créées par le plugin
 * lors de sa désinstallation.
 *
 */
function login_oauth2_vider_tables($nom_meta_base_version) {

	include_spip('base/abstract_sql');

	// Supprimer table
	sql_drop_table('spip_auteurs_oauth2');

	// Supprimer config
	effacer_meta('login_oauth2');
	effacer_meta($nom_meta_base_version);
}