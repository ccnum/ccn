<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Types SQL des colonnes déclarées à la fois ici (vraie colonne de table) et
 * comme champs extras (thematique_cextras.php) : les deux mécanismes
 * créent/maintiennent la même colonne, ils doivent donc partager le même
 * type, sous peine de casser l'ajout des index à l'installation.
 *
 * Les noms X/Y sont en majuscules ici et en minuscules côté champs extras :
 * MySQL ne distingue pas la casse des colonnes, mais SPIP compare les noms
 * en PHP — ne pas les harmoniser sans migration.
 *
 * @return array Type SQL par table puis par nom de colonne
 */
function thematique_champs_sql() {
	$id = 'bigint(21) NOT NULL DEFAULT 0';
	$position = 'float NOT NULL DEFAULT 0';

	return [
		'spip_articles' => [
			'id_consigne' => $id,
			'X' => $position,
			'Y' => $position,
		],
		'spip_syndic_articles' => [
			'X' => $position,
			'Y' => $position,
		],
		'spip_rubriques' => [
			'id_rubrique_lien' => $id,
		],
	];
}

/**
 * Pipeline declarer_tables_principales : champs ajoutés aux tables SPIP.
 *
 * @param array $tables_principales Description des tables principales
 * @return array
 */
function thematique_declarer_tables_principales($tables_principales) {

	//-- Ajout des champs extras ----------------------------------
	foreach (thematique_champs_sql() as $table => $champs) {
		foreach ($champs as $champ => $sql) {
			$tables_principales[$table]['field'][$champ] = $sql;
		}
	}
	$tables_principales['spip_articles']['key']['id_consigne'] = 'id_consigne';
	$tables_principales['spip_rubriques']['key']['id_rubrique_lien'] = 'id_rubrique_lien';
	// Classe (rubrique) au nom de laquelle un commentaire forumv2 a été écrit :
	// la classe active de son auteur au moment de l'écriture (un prof peut en
	// avoir plusieurs, cf thematique_id_rubrique_classe()). 0 = inconnue
	// (commentaire antérieur, ou auteur sans classe).
	$tables_principales['spip_forum']['field']['id_classe'] = 'bigint(21) NOT NULL DEFAULT 0';

	$nom = $GLOBALS['meta']['nom_site'];
	if ((strpos($nom, 'design') !== false) || (strpos($nom, 'zerogaspi') !== false)) {
		$tables_principales['spip_articles']['field']['champ1'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ2'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ3'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ4'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ5'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ6'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ7'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ8'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ9'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ10'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ11'] = 'longtext NOT NULL';
		$tables_principales['spip_articles']['field']['champ12'] = 'longtext NOT NULL';
	}

	return $tables_principales;
}

/**
 * Pipeline declarer_tables_interfaces.
 *
 * @param array $interface Interfaces des tables
 * @return array
 */
function thematique_declarer_tables_interfaces($interface) {

	return $interface;
}
