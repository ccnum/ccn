<?php

/**
 * Crayons
 * plugin for spip
 * (c) Fil, toggg 2006-2013
 * licence GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Upload de documents
 *
 * Cette action recoit des fichiers ($_FILES)
 * et les affecte a l'objet courant ;
 * puis renvoie la liste des documents joints
 */
function action_crayons_upload() {

	$type = preg_replace('/\W+/', '', (string) _request('type'));
	$id = (int) _request('id');

	// check securite :-)
	include_spip('inc/autoriser');
	if (!autoriser('joindredocument', $type, $id)) {
		echo 'Erreur: upload interdit';
		return false;
	}

	// on n'accepte qu'un seul document à la fois, dans la variable 'upss'
	if (($file = $_FILES['upss']) && $file['error'] == 0) {
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');
		$id = $ajouter_documents('new', [$file], $type, $id, 'document');
		if ($id) {
			$id = reset($id);
		}
	}

	if (!$id) {
		$erreur = 'erreur !';
	}

	$a = recuperer_fond('modeles/uploader_item', ['id_document' => $id, 'erreur' => $erreur]);

	echo $a;
}
