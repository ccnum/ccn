<?php
/**
 * Déclaration de la base de données du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Base
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclare la table spip_reactions
 *
 * Le couple (objet, id_objet) rend la table générique : on peut réagir
 * à un article aujourd'hui, à un autre objet éditorial demain, sans
 * modifier le schéma.
 *
 * L'unicité "un visiteur ne pose qu'une fois le même smiley sur le même
 * objet" N'EST PAS gérée par une contrainte SQL ici (un visiteur anonyme
 * a id_auteur = 0, ce qui rendrait une UNIQUE KEY inopérante pour
 * distinguer deux anonymes). Cette unicité est vérifiée en PHP,
 * voir reactions_fonctions.php::reactions_a_deja_reagi().
 *
 * @param array $tables_principales
 * @return array
 */
function reactions_declarer_tables_principales($tables_principales) {

	$tables_principales['spip_reactions'] = [
		'field' => [
			'id_reaction'   => 'bigint(21) NOT NULL AUTO_INCREMENT',
			'objet'         => "varchar(25) NOT NULL DEFAULT 'article'",
			'id_objet'      => 'bigint(21) NOT NULL DEFAULT 0',
			'id_auteur'     => 'bigint(21) NOT NULL DEFAULT 0',
			'session_id'    => "varchar(255) NOT NULL DEFAULT ''",
			'type_reaction' => "varchar(32) NOT NULL DEFAULT ''",
			'date_reaction' => 'datetime DEFAULT NULL',
		],
		'key' => [
			'PRIMARY KEY'        => 'id_reaction',
			'KEY idx_objet'      => 'objet, id_objet',
			'KEY idx_type'       => 'objet, id_objet, type_reaction',
			'KEY idx_auteur'     => 'id_auteur',
			'KEY idx_session'    => 'session_id',
		],
	];

	return $tables_principales;
}

/**
 * Déclare les tables auxiliaires (aucune pour ce plugin, mais le
 * pipeline doit exister si on veut un jour ajouter spip_reactions_types
 * en table dédiée plutôt qu'en meta).
 *
 * @param array $tables_auxiliaires
 * @return array
 */
function reactions_declarer_tables_auxiliaires($tables_auxiliaires) {
	return $tables_auxiliaires;
}