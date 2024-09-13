<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Generer l'url d'un document dans l'espace public,
 * fonction du statut du document
 *
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $public
 * @param string $connect
 * @return string
 *
 * https://code.spip.net/@generer_url_ecrire_document
 */
function urls_generer_url_document_dist($id, $args = '', $ancre = '', $public = null, $connect = '') {
	include_spip('inc/autoriser');
	include_spip('inc/documents');

	// si on a pas le droit de voir le document, meme via le htaccess
	if (!autoriser('voir', 'document', $id, null, ['htaccess' => true])) {
		return '';
	}

	$res = sql_fetsel('fichier,distant,extension', 'spip_documents', 'id_document=' . intval($id));

	if (!$res) {
		return '';
	}

	$f = $res['fichier'];

	if ($res['distant'] == 'oui') {
		return $f;
	}

	// Si droit de voir tous les docs, sans htaccess, pas seulement celui-ci
	// il est inutilement couteux de rajouter une protection
	$r = autoriser('voir', 'document');
	// SPIP < 4.2.6 peut retourner 'htaccess'
	if ($r && $r !== 'htaccess') {
		return get_spip_doc($f);
	}

	// cette url doit etre publique : generer un hash qui signe l'acces autorise au document
	$cle = accesrestreint_calculer_cle_document($id, $f);

	// renvoyer une url plus ou moins jolie
	// @see accesrestreint_gerer_htaccess()
	if (isset($GLOBALS['meta']['creer_htaccess']) and $GLOBALS['meta']['creer_htaccess']) {
		$url = url_absolue(_DIR_RACINE . "docrestreint.api/$id/$cle/$f");
	} else {
		$url = get_spip_doc($f) . "?$id/$cle";
	}

	// En absolue afin que les filtres d'image puissent agir sur les documents
	// dû au paramètre d'URL ou au manque d'extension
	if (in_array($res['extension'], ['jpg','png','gif'])) {
		$url = url_absolue($url);
	}
	return $url;
}
