<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function thematique_declarer_tables_principales($tables_principales) {

	//-- Ajout des champs extras ----------------------------------
	// id_consigne/id_rubrique_lien doivent avoir EXACTEMENT le même type SQL
	// ici et dans thematique_cextras.php (qui déclare les mêmes champs comme
	// champs extras) : les deux mécanismes créent/maintiennent la même
	// colonne réelle. Un mismatch (ex: bigint ici, text ou int(5) côté
	// cextras) casse l'ADD INDEX ci-dessous, MySQL interdisant un préfixe de
	// longueur sur une colonne numérique (issue #369, provoqué par un
	// correctif précédent — cf 6f4bcaf0 — qui masquait ce mismatch avec un
	// préfixe de longueur "id_consigne(20)" au lieu de le supprimer).
	$tables_principales['spip_articles']['field']['id_consigne'] = 'bigint(21) NOT NULL DEFAULT 0';
	$tables_principales['spip_articles']['field']['X'] = 'float NOT NULL DEFAULT 0';
	$tables_principales['spip_articles']['field']['Y'] = 'float NOT NULL DEFAULT 0';
	$tables_principales['spip_articles']['key']['id_consigne'] = 'id_consigne';
	$tables_principales['spip_syndic_articles']['field']['X'] = 'float NOT NULL DEFAULT 0';
	$tables_principales['spip_syndic_articles']['field']['Y'] = 'float NOT NULL DEFAULT 0';
	$tables_principales['spip_rubriques']['field']['id_rubrique_lien'] = 'bigint(21) NOT NULL DEFAULT 0';
	$tables_principales['spip_rubriques']['key']['id_rubrique_lien'] = 'id_rubrique_lien';

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

function thematique_declarer_tables_interfaces($interface) {

	return $interface;
}
