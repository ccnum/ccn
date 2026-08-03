<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Crée la colonne "vimeo_password" sur spip_documents, déclarée en champ
 * extra (cextras) par api_vimeo_declarer_champs_extras() mais jamais créée
 * en base : les SELECT sur cette colonne échouaient (erreur SQL 1054).
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function api_vimeo_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	$maj['create'] = [
		['sql_alter', "TABLE spip_documents ADD vimeo_password varchar(255) NOT NULL DEFAULT ''"],
	];

	// vimeo_statut/vimeo_progression (cf api_vimeo_declarer_champs_extras) :
	// suivi de l'envoi Vimeo affiché côté front (noisettes/inc/ajouter_document.html).
	$maj['1.0.2'] = [
		['sql_alter', "TABLE spip_documents ADD vimeo_statut varchar(20) NOT NULL DEFAULT ''"],
		['sql_alter', "TABLE spip_documents ADD vimeo_progression tinyint(3) unsigned NOT NULL DEFAULT '0'"],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Retire les colonnes ajoutées par ce plugin à la désinstallation.
 *
 * @param string $nom_meta_base_version
 */
function api_vimeo_vider_tables($nom_meta_base_version) {
	sql_alter('TABLE spip_documents DROP COLUMN vimeo_password');
	sql_alter('TABLE spip_documents DROP COLUMN vimeo_statut');
	sql_alter('TABLE spip_documents DROP COLUMN vimeo_progression');
	effacer_meta($nom_meta_base_version);
}
