<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Crée les colonnes du plugin sur spip_documents : vimeo_password (champ
 * extra, cf api_vimeo_declarer_champs_extras) et vimeo_statut/
 * vimeo_progression (suivi de l'envoi Vimeo affiché côté front,
 * noisettes/inc/ajouter_document.html de thematique).
 *
 * Schéma 1.2.0 : vimeo_statut/vimeo_progression n'étaient ajoutées que par
 * l'étape de mise à jour, jamais par "create" — or une première
 * installation n'exécute que "create" (cf maj_plugin). Un site où le plugin
 * a été activé directement en 1.1.0 n'a donc jamais eu ces colonnes (erreur
 * SQL 1054 "Unknown column 'vimeo_statut'"). Les deux étapes passent
 * désormais par api_vimeo_ajouter_colonnes(), qui n'ajoute que ce qui
 * manque.
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function api_vimeo_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	$maj['create'] = [['api_vimeo_ajouter_colonnes']];
	$maj['1.2.0'] = [['api_vimeo_ajouter_colonnes']];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Ajoute à spip_documents les colonnes du plugin qui n'y sont pas encore.
 */
function api_vimeo_ajouter_colonnes() {
	$colonnes = [
		'vimeo_password' => "varchar(255) NOT NULL DEFAULT ''",
		'vimeo_statut' => "varchar(20) NOT NULL DEFAULT ''",
		'vimeo_progression' => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
	];
	$table = sql_showtable('spip_documents', true);
	foreach ($colonnes as $nom => $definition) {
		if (!isset($table['field'][$nom])) {
			sql_alter("TABLE spip_documents ADD {$nom} {$definition}");
		}
	}
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
