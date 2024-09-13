<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('base/abstract_sql');

//  acces aux documents joints securise
//  est appelee avec arg comme parametre CGI
//  mais peu aussi etre appele avec le parametre file directement
//  il verifie soit que le demandeur est authentifie
// soit que le fichier est joint a au moins 1 article, breve ou rubrique publie

// https://code.spip.net/@action_autoriser_dist
function action_autoriser_dist() {
	$arg = intval(_request('arg'));

	if (
		!autoriser('voir', 'document', $arg, null, ['htaccess' => true])
		or !($row = sql_fetsel('fichier', 'spip_documents', 'id_document=' . intval($arg)))
		or !($file = $row['fichier'])
		or !(file_exists($file))
	) {
		spip_log('Acces refuse (restreint) au document ' . $arg . ': ' . $file, 'accesrestreint');
		redirige_par_entete('./?page=404');
	} else {

		include_spip('inc/livrer_fichier');
		spip_livrer_fichier($file, mime_content_type($file), ['attachment' => true]);
	}
}
