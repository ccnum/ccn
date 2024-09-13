<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/meta');
/**
 * Fonction d'installation, mise a jour de la base
 *
 * @param unknown_type $nom_meta_base_version
 * @param unknown_type $version_cible
 */
function accesrestreint_upgrade($nom_meta_base_version, $version_cible) {

	// le prefixe est passe des majuscules aux minuscules :
	if (
		isset($GLOBALS['meta']['AccesRestreint_base_version'])
		and !isset($GLOBALS['meta'][$nom_meta_base_version])
	) {
		$GLOBALS['meta'][$nom_meta_base_version] = $GLOBALS['meta']['AccesRestreint_base_version'];
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables',['spip_zones','spip_zones_liens']],
	];

	$maj['0.1.0'] = [
		['maj_tables',['spip_zones']], // publique, privee
	];
	$maj['0.2.0'] = [
		['maj_tables',['spip_zones']], // publique, privee
	];
	$maj['0.3.0'] = [
		['sql_alter', 'TABLE spip_zones_auteurs DROP INDEX id_zone'],
		['sql_alter', 'TABLE spip_zones_auteurs ADD PRIMARY KEY ( id_zone , id_auteur )'],
		['sql_alter', 'TABLE spip_zones_rubriques DROP INDEX id_zone'],
		['sql_alter', 'TABLE spip_zones_rubriques ADD PRIMARY KEY ( id_zone , id_rubrique )'],
	];
	$maj['0.3.1'] = [
		['sql_alter',"TABLE spip_zones ALTER titre SET DEFAULT ''"],
		['sql_alter',"TABLE spip_zones ALTER descriptif SET DEFAULT ''"],
	];

	include_spip('maj/legacy/svn10000');
	$maj['0.4.0'] = [
		['maj_liens', 'zone'], // creer la table zones_liens
		['maj_liens','zone', 'auteur'],
		['sql_drop_table', 'spip_zones_auteurs'],
		['maj_liens','zone', 'rubrique'],
		['sql_drop_table', 'spip_zones_rubriques'],
	];
	$maj['0.4.1'] = [
		['sql_alter',"TABLE spip_zones CHANGE publique publique char(3) DEFAULT 'oui' NOT NULL"],
		['sql_alter',"TABLE spip_zones CHANGE privee privee char(3) DEFAULT 'oui' NOT NULL"],
	];
	$maj['0.4.2'] = [
		['accesrestreint_upgrade_protection_documents'],
	];
	// autoriser_si_connexion
	$maj['0.5.0'] = [
		['maj_tables', ['spip_zones']],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function accesrestreint_upgrade_protection_documents() {
	if (
		isset($GLOBALS['meta']['creer_htaccess'])
		and $GLOBALS['meta']['creer_htaccess'] == 'oui'
		and !isset($GLOBALS['meta']['accesrestreint_proteger_documents'])
	) {
		ecrire_meta('accesrestreint_proteger_documents', 'oui');
		include_spip('inc/accesrestreint_documents');
		accesrestreint_gerer_htaccess(true);
	}
}

/**
 * Fonction de desinstallation
 *
 * @param unknown_type $nom_meta_base_version
 */
function accesrestreint_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_zones');
	sql_drop_table('spip_zones_liens');
	effacer_meta('accesrestreint_proteger_documents');
	include_spip('inc/accesrestreint_documents');
	accesrestreint_gerer_htaccess(false);
	effacer_meta($nom_meta_base_version);
}
