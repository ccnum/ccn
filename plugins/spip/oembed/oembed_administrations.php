<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function oembed_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];
	$maj['create'] = [
		['sql_alter',"TABLE spip_documents ADD oembed text NOT NULL DEFAULT ''"],
		['sql_alter',"TABLE spip_documents ADD oembed_data text NOT NULL DEFAULT ''"],
	];

	$maj['0.4.1'] = [
		['sql_drop_table', 'spip_oembed_providers'],
		['sql_alter',"TABLE spip_documents ADD oembed_data text NOT NULL DEFAULT ''"],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function oembed_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_oembed_providers');
	effacer_meta($nom_meta_base_version);
}
