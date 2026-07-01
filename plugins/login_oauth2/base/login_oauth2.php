<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration des objets SQL
 */
function login_oauth2_declarer_tables_objets_sql($tables) {

	// Déclaration de la table auteurs_oauth2 comme table auxiliaire
	$tables['spip_auteurs_oauth2'] = [
		'type' => 'auteurs_oauth2',
		'principale' => 'non',
		'table_objet' => 'auteurs_oauth2',
		'field'=> [
			'id_auteur'	=> 'bigint(21) NOT NULL',
			'provider'	 => "varchar(64) NOT NULL DEFAULT ''",
			'subject'	  => "varchar(255) NOT NULL DEFAULT ''",
			'email'		=> "varchar(255) DEFAULT NULL",
			'date_liaison' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'grant_type'     => "varchar(50) DEFAULT NULL"
		],
		'key' => [
			'PRIMARY KEY'   => 'provider,subject',
			'KEY id_auteur' => 'id_auteur'
		],
		'date' => 'date_liaison'
	];

	// Jointure automatique avec auteurs
	$tables['spip_auteurs']['tables_jointures'][] = 'auteurs_oauth2';

	return $tables;
}


/**
 * Déclaration des tables auxiliaires
 */
function login_oauth2_declarer_tables_auxiliaires($tables_auxiliaires) {

	$tables_auxiliaires['spip_auteurs_oauth2'] = [
		'field' => [
			'id_auteur'	=> 'bigint(21) NOT NULL',
			'provider'	 => "varchar(64) NOT NULL DEFAULT ''",
			'subject'	  => "varchar(255) NOT NULL DEFAULT ''",
			'email'		=> "varchar(255) DEFAULT NULL",
			'date_liaison' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'grant_type'     => "varchar(50) DEFAULT NULL"		],
		'key' => [
			'PRIMARY KEY'   => 'provider,subject',
			'KEY id_auteur' => 'id_auteur'
		]
	];

	return $tables_auxiliaires;
}

/**
 * Déclare les interfaces SQL utilisées par le plugin login_oauth2.
 */
function login_oauth2_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['auteurs_oauth2'] = 'auteurs_oauth2';
	$interfaces['table_date']['auteurs_oauth2'] = 'date_liaison';

	return $interfaces;
}

