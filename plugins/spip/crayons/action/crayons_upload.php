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

	$crayon_type = preg_replace('/\W+/', '', (string) _request('type'));
	$id = (int) _request('id');

	[$distant, $table, $type] = distant_table($crayon_type);

	include_spip('inc/autoriser');
	include_spip('inc/crayons');
	include_spip('inc/config');
	if (
		// ne pas se fatiguer si le visiteur n'a aucun droit
		!(function_exists('analyse_droits_rapide') ? analyse_droits_rapide() : analyse_droits_rapide_dist())
		|| lire_config('crayons/upload', '') !== 'on'
		/** @uses autoriser_crayonner_dist() */
		|| !crayons_get_table($crayon_type, $table_sql)
		|| !autoriser('crayonner', $type, $id, null, ['crayon_type' => $crayon_type, 'type' => $type, 'distant' => $distant, 'modele' => 'joindredocument'])
		|| !autoriser('joindredocument', $type, $id)
	) {
		echo 'Erreur: upload interdit';
		return false;
	}

	// on n'accepte qu'un seul document à la fois, dans la variable 'upss'
	if (($file = $_FILES['upss']) && $file['error'] == 0) {
		// et cela doit être une extension autorisée
		if (!crayons_verifier_format_document_upload($file, $type, $id)) {
			echo 'Erreur: upload interdit';
			return false;
		}
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');
		/** @uses action_ajouter_documents_dist() $id */
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


function crayons_verifier_format_document_upload($file, $type, $id) {
	$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	include_spip('inc/joindre_document');
	include_spip('action/ajouter_documents');
	[$extension, $file['name']] = fixer_extension_document($file);

	$extensions_interdites = ['html'];
	if (in_array($extension, $extensions_interdites)) {
		return false;
	}

	if (!crayons_accepte_fichier_upload($file['name'])) {
		return false;
	}

	return true;
}


/**
 * Vérifier que l'extension est connue
 *
 * @param sring $f
 * @return bool|int
 */
function crayons_accepte_fichier_upload($f) {
	if (
		!preg_match(',.*__MACOSX/,', $f)
		and !preg_match(',^\.,', basename($f))
	) {
		include_spip('action/ajouter_documents');
		$ext = corriger_extension((strtolower(substr(strrchr($f, '.'), 1))));

		return sql_countsel(
			'spip_types_documents',
			'extension=' . sql_quote($ext) . " AND upload='oui'"
		);
	}
}
