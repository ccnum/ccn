<?php

/**
 * Plugin mesfavoris
 * (c) 2009-2013 Olivier Sallou, Cedric Morin, Gilles Vincent
 * Distribue sous licence GPL
 */

/**
 * Utilisation des pipelines
 *
 * @package SPIP\Mesfavoris\Pipelines
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration de l'index de $tables_principales qui sera utilisé dans les 'spip_'
 *
 * @pipeline declarer_tables_interfaces
 * @param  array $interface Array contenant les infos des tables visibles par recherche sur 'spip_bidule'
 * @return array            Cet Array de description modifié
 */
function mesfavoris_declarer_tables_interfaces($interface) {
	$interface['table_des_tables']['favoris'] = 'favoris';

	return $interface;
}

/**
 * Declaration des tables principales
 *
 * @pipeline declarer_tables_principales
 * @param array $tables_principales Un array de description des tables
 * @return array L'Array de description complété
 */
function mesfavoris_declarer_tables_principales($tables_principales) {
	$spip_favoris = [
		'id_favori' => 'BIGINT NOT NULL',
		'id_auteur' => "BIGINT DEFAULT '0' NOT NULL",
		'id_objet' => "BIGINT DEFAULT '0' NOT NULL",
		'objet' => "VARCHAR(25) DEFAULT '' NOT NULL",
		'categorie' => "VARCHAR(99) DEFAULT '' NOT NULL",
		'date_ajout' => 'DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL', // MySQL>=5.6.5 ; SQLite: not NOW()
		'maj' => 'TIMESTAMP',
	];

	$spip_favoris_key = [
		'PRIMARY KEY' => 'id_favori',
		'KEY auteur_objet' => 'id_auteur,id_objet,objet',
		'KEY id_auteur' => 'id_auteur', // un peu inutile vu qu'il y a l'index auteur_objet
		'KEY id_objet' => 'id_objet',
		'KEY objet' => 'objet',
		'KEY categorie' => 'categorie',
	];

	$tables_principales['spip_favoris'] = [
		'field' => &$spip_favoris,
		'key' => &$spip_favoris_key,
	];

	return $tables_principales;
}

/**
 * Insertion dans le pipeline insert_head_css
 *
 * @pipeline insert_head_css
 * @param string $flux Le contenu CSS du head
 * @return string Le contenu CSS du head modifié
 */
function mesfavoris_insert_head_css($flux) {
	$css = find_in_path('css/mesfavoris.css');
	$flux .= "<link rel='stylesheet' type='text/css' media='all' href='" . timestamp(direction_css($css)) . "' />\n";

	return $flux;
}

/**
 * Insertion dans le pipeline optimiser_base_disparus
 *
 * @pipeline optimiser_base_disparus
 * @param array $flux Données du pipeline
 * @return array $flux Données du pipeline
 */
function mesfavoris_optimiser_base_disparus($flux) {
	// inspiré de lien_optimise()
	$objets = sql_allfetsel('DISTINCT objet', 'spip_favoris');
	$objets = array_filter(array_column($objets, 'objet'));
	foreach ($objets as $type) {
		$spip_table_objet = table_objet_sql($type);
		if (sql_table_exists($spip_table_objet)) {
			$id_table_objet = id_table_objet($type);
			$res = sql_select(
				"F.id_favori AS id",
				"spip_favoris AS F
					LEFT JOIN $spip_table_objet AS O
						ON (O.$id_table_objet=F.id_objet AND F.objet=" . sql_quote($type) . ')',
				'F.objet=' . sql_quote($type) . " AND O.$id_table_objet IS NULL"
			);
			$flux['data'] += optimiser_sansref('spip_favoris', 'id_favori', $res);
		}
	}
	return $flux;
}