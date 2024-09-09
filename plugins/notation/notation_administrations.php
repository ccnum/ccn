<?php

/**
 * Plugin Notation v3
 * par JEM (jean-marc.viglino@ign.fr) / b_b / Matthieu Marcillaud
 *
 * Copyright (c) depuis 2008
 * Logiciel libre distribue sous licence GNU/GPL.
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('inc/meta');
include_spip('base/create');

/**
 * @param string $nom_meta_base_version
 * @param string $version_cible
 * @return void
 */
function notation_upgrade($nom_meta_base_version, $version_cible){


	// mettre les metas par defaut
	$config = charger_fonction('config', 'inc');
	include_spip('inc/notation');
	$maj = [];

	$maj['create'] = [
		['maj_tables', ['spip_notations', 'spip_notations_objets', 'spip_articles']],
		[$config],
	];

	$maj['0.5.0'] = [
		['sql_alter', 'TABLE spip_notations_articles RENAME TO spip_notations_objets'],
		['sql_alter', 'TABLE spip_notations_objets CHANGE nb nombre_votes BIGINT(21) NOT NULL'],
		['sql_alter', "TABLE spip_notations_objets MODIFY id_article BIGINT(21) NOT NULL DEFAULT '0'"],
		['sql_alter', "TABLE spip_notations_objets ADD COLUMN id_forum BIGINT(21) NOT NULL DEFAULT '0' AFTER id_article"],
		['sql_alter', 'TABLE spip_notations_objets DROP PRIMARY KEY'],
		['sql_alter', "TABLE spip_notations_objets ADD COLUMN objet varchar(21) DEFAULT '' NOT NULL FIRST"],
		['sql_alter', 'TABLE spip_notations_objets ADD INDEX (objet)'],
		['sql_alter', 'TABLE spip_notations_objets ADD INDEX (id_article)'],
		['sql_alter', 'TABLE spip_notations_objets ADD INDEX (id_forum)'],
		['sql_alter', 'TABLE spip_notations_objets ADD INDEX (nombre_votes)'],
		//modifications de la table notations
		['sql_alter', "TABLE spip_notations ADD COLUMN id_forum BIGINT(21) NOT NULL DEFAULT '0' AFTER id_article"],
		['sql_alter', "TABLE spip_notations ADD COLUMN objet varchar(21) DEFAULT '' NOT NULL AFTER id_notation"],
		['sql_alter', 'TABLE spip_notations ADD INDEX (id_forum)'],
		['sql_alter', 'TABLE spip_notations ADD INDEX (objet)'],
		// insertion de "articles" dans les champs "objet" des deux tables
		// (les donnees presentes avant la maj ne concernent que des articles)
		// change ensuite (0.6) en 'article' (comme le core - cf spip_documents_liens)
		['sql_updateq', 'spip_notations', ['objet' => 'articles']],
		['sql_updateq', 'spip_notations_objets', ['objet' => 'articles']],

		//on vire les metas dans la verison precedente (maintenant on se sert de CFG)
		['sql_delete', 'spip_meta', 'nom =' . sql_quote('notation_acces')],
		['sql_delete', 'spip_meta', 'nom =' . sql_quote('notation_ip')],
		['sql_delete', 'spip_meta', 'nom =' . sql_quote('notation_nb')],
		['sql_delete', 'spip_meta', 'nom =' . sql_quote('notation_ponderation')],
	];

	$maj['0.6.1'] = [
		// ajout des champ id_objet
		['sql_alter', "TABLE spip_notations ADD COLUMN id_objet BIGINT(21) NOT NULL DEFAULT '0' AFTER objet"],
		['sql_alter', "TABLE spip_notations_objets ADD COLUMN id_objet BIGINT(21) NOT NULL DEFAULT '0' AFTER objet"],
		// remplissage des valeurs deja existantes
		['sql_updateq', 'spip_notations', ['id_objet' => 'id_article', 'objet' => sql_quote('article')], 'id_article>' . sql_quote(0)],
		['sql_updateq', 'spip_notations', ['id_objet' => 'id_forum', 'objet' => sql_quote('forum')], 'id_forum>' . sql_quote(0)],
		['sql_updateq', 'spip_notations_objets', ['id_objet' => 'id_article', 'objet' => sql_quote('article')], 'id_article>' . sql_quote(0)],
		['sql_updateq', 'spip_notations_objets', ['id_objet' => 'id_forum', 'objet' => sql_quote('forum')], 'id_forum>' . sql_quote(0)],
		// suppression des index
		['sql_alter', 'TABLE spip_notations DROP INDEX id_article'],
		['sql_alter', 'TABLE spip_notations DROP INDEX id_forum'],
		['sql_alter', 'TABLE spip_notations_objets DROP INDEX id_article'],
		['sql_alter', 'TABLE spip_notations_objets DROP INDEX id_forum'],
		// suppression des vieux champs id_article et id_forum
		['sql_alter', 'TABLE spip_notations DROP COLUMN id_article'],
		['sql_alter', 'TABLE spip_notations DROP COLUMN id_forum'],
		['sql_alter', 'TABLE spip_notations_objets DROP COLUMN id_article'],
		['sql_alter', 'TABLE spip_notations_objets DROP COLUMN id_forum'],
		// recreation d'index sur id_objet
		['sql_alter', 'TABLE spip_notations ADD INDEX (id_objet)'],
		// creation d'une cle primaire multiple sur la table notations_objets
		['sql_alter', 'TABLE spip_notations_objets DROP INDEX objet'],
		['sql_alter', 'TABLE spip_notations_objets ADD PRIMARY KEY (objet, id_objet)'],
		// corriger le 'articles' en 'article' ocazou il en resterait
		['sql_updateq', 'spip_notations', ['objet' => 'article'], 'objet=' . sql_quote('articles')],
		['sql_updateq', 'spip_notations_objets', ['objet' => 'article'], 'objet=' . sql_quote('articles')],
	];

	$maj['0.6.2'] = [
		['maj_tables', ['spip_articles']],
		[$config],
	];

	$maj['0.6.3'] = [
		// Pour ceux qui ont installe une 0.6.2 directement avant la correction creant 'accepter_note'
		['maj_tables', ['spip_articles']],
	];

	$maj['0.7.0'] = [
		['sql_alter', "TABLE spip_notations ADD COLUMN hash VARCHAR(255) NOT NULL DEFAULT '' AFTER ip"],
		['sql_alter', "TABLE spip_notations ADD COLUMN cookie VARCHAR(255) NOT NULL DEFAULT '' AFTER hash"],
	];

	$maj['0.8.0'] = [
		['sql_alter', "TABLE spip_notations CHANGE objet objet VARCHAR(50) DEFAULT '' NOT NULL"],
		['sql_alter', "TABLE spip_notations_objets CHANGE objet objet VARCHAR(50) DEFAULT '' NOT NULL"],
	];

	$ponderation = notation_get_ponderation();
	$maj['1.0.0'] = [
		['notation_recalculer_notes_moyennes', $ponderation],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function notation_vider_tables($nom_meta_base_version) {

	sql_drop_table('spip_notations');
	sql_drop_table('spip_notations_objets');
	sql_alter('TABLE spip_articles DROP COLUMN accepter_note');
	effacer_meta($nom_meta_base_version);
}
