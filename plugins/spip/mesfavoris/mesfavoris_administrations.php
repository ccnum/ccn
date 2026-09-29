<?php

/**
 * Plugin mesfavoris
 * (c) 2009-2013 Olivier Sallou, Cedric Morin, Gilles Vincent
 * Distribue sous licence GPL
 */

/**
 * Fichier gérant l'installation et désinstallation du plugin
 *
 * @package SPIP\Mesfavoris\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation / Mise à jour des tables des favoris
 *
 * Crée les tables SQL du plugin (spip_favoris)
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 */
function mesfavoris_upgrade($nom_meta_base_version, $version_cible) {
	include_spip('inc/meta');

	$maj = [];
	$maj['create'] = [['maj_tables', ['spip_favoris']]];

	$maj['1.0.0'] = [['mesfavoris_upgrade_from_old']];

	$maj['1.1.0'] = [
		['sql_alter', 'TABLE spip_favoris ADD INDEX objet (objet)'],
		['sql_alter', 'TABLE spip_favoris ADD INDEX id_objet (id_objet)'],
	];

	$maj['1.2.0'] = [
		['sql_alter', 'TABLE spip_favoris ADD COLUMN categorie VARCHAR(99) DEFAULT \'\' NOT NULL'],
		['sql_alter', 'TABLE spip_favoris ADD INDEX categorie (categorie)'],
	];

	$maj['1.3.0'] = [
		['sql_alter', 'TABLE spip_favoris ADD COLUMN date_ajout DATETIME NOT NULL'],
		['sql_update', 'spip_favoris', ['date_ajout' => 'maj']],
	];

	$maj['1.4.0'] = [['mesfavoris_migre_config']];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function mesfavoris_migre_config() {
	include_spip('inc/config');
	$style = lire_config('mesfavoris/style_formulaire');
	if ($style == '24') {
		$style = 'bookmark';
	} else {
		$style = 'coeur';
	}
	ecrire_config('mesfavoris/style_formulaire', $style);
}

function mesfavoris_upgrade_from_old() {
	include_spip('base/create');
	include_spip('base/abstract_sql');
	include_spip('base/objets');
	lister_tables_objets_sql();

	creer_ou_upgrader_table('spip_favoris', $GLOBALS['tables_principales']['spip_favoris'], true);

	// recuperer l'ancienne base si possible (hum)
	$trouver_table = charger_fonction('trouver_table', 'base');
	$trouver_table(''); // vider le cache

	if ($desc = $trouver_table('spip_favtextes')) {
		$res = sql_select('*', 'spip_favtextes');

		while ($row = sql_fetch($res)) {
			sql_insertq('spip_favoris', ['id_auteur' => $row['id_auth'], 'id_objet' => $row['id_texte'], 'objet' => 'article']);
			sql_delete('spip_favtextes', 'id_favtxt=' . $row['id_favtxt']);
		}

		sql_drop_table('spip_favtextes');
	}
}

/**
 * Désinstallation du plugin
 *
 * Supprime les tables SQL du plugin (spip_favoris)
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 */
function mesfavoris_vider_tables($nom_meta_base_version) {
	include_spip('inc/meta');
	include_spip('base/abstract_sql');
	sql_drop_table('spip_favoris');
	effacer_meta($nom_meta_base_version);
}
