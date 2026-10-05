<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Helpers communs aux formulaires d'envoi de fichier(s) sur un objet SPIP
 * basés sur bigup (#SAISIE_FICHIER, upload par morceaux) et sur le même
 * squelette charger/vérifier/traiter : formulaires/ccn_joindre_document
 * (ici), thematique/formulaires/joindre_video.
 *
 * @package SPIP\Ccn\Inc
 */

/**
 * Récupère les fichiers envoyés dans $_FILES via bigup.
 *
 * Avec _bigup_rechercher_fichiers activé (cf charger() des formulaires), bigup
 * réinjecte les fichiers uploadés par morceaux dans $_FILES avant
 * verifier()/traiter(), comme un upload classique.
 *
 * joindre_trouver_http_post_files() plutôt que joindre_trouver_fichier_envoye()
 * (plugin medias) : ce dernier exige _request('joindre_upload'), or bigup
 * cache tout bouton submit nommé "joindre_upload" trouvé sur la page (cf
 * bigup.documents.js), ce qui rendait le formulaire invalidable.
 * Ne valide pas les extensions : à la charge de l'appelant, les formats
 * autorisés différant selon le formulaire (cf ccn_joindre_filtrer_extensions()).
 *
 * @return string|array Message d'erreur, ou tableau de fichiers ($_FILES-like)
 */
function ccn_joindre_trouver_fichiers() {
	include_spip('inc/joindre_document');
	include_spip('action/ajouter_documents');

	$files = joindre_trouver_http_post_files();
	if (is_string($files)) {
		return $files;
	}
	if (!count($files)) {
		return _T('medias:erreur_indiquez_un_fichier');
	}

	return $files;
}

/**
 * Refuse les fichiers dont l'extension n'est pas dans $extensions.
 *
 * @param string|array $files Retour de ccn_joindre_trouver_fichiers()
 * @param string[] $extensions Extensions autorisées, sans le point ; vide = pas de restriction
 * @return string|array Message d'erreur, ou $files inchangé
 */
function ccn_joindre_filtrer_extensions($files, $extensions) {
	if (is_string($files) || !$extensions) {
		return $files;
	}
	foreach ($files as $file) {
		$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
		if (!in_array($ext, $extensions)) {
			return _T('ccn:erreur_extension_document', [
				'extension' => $ext,
				'extensions' => implode(', ', $extensions),
			]);
		}
	}

	return $files;
}

/**
 * Vérifie l'autorisation à joindre un document sur $objet/$id_objet.
 *
 * @return string|null Message d'erreur, ou null si autorisé
 */
function ccn_joindre_verifier_autorisation($objet, $id_objet) {
	include_spip('inc/autoriser');
	if (!autoriser('joindredocument', $objet, $id_objet)) {
		return _T('info_acces_interdit');
	}

	return null;
}

/**
 * Ajoute les fichiers déjà validés comme documents liés à $objet/$id_objet,
 * et construit la réponse standard du CVT (message_ok/message_erreur/ids/
 * redirect).
 *
 * En cas de succès, recharge le bloc ajax "documents" (inclusion
 * {ajax=documents} listant les documents de l'objet, à la charge du
 * squelette appelant) : même mécanisme que le formulaire natif du plugin
 * medias (cf joindre_document.php).
 *
 * @param array $files Fichiers validés (retour de ccn_joindre_trouver_fichiers())
 * @param string $objet
 * @param int $id_objet
 * @return array Réponse CVT
 */
function ccn_joindre_ajouter_documents($files, $objet, $id_objet) {
	$res = ['editable' => true];

	$ajouter_documents = charger_fonction('ajouter_documents', 'action');
	$nouveaux_doc = $ajouter_documents('new', $files, $objet, $id_objet, 'document');

	$messages_erreur = [];
	$sel = [];
	$ancre = '';
	foreach ($nouveaux_doc as $doc) {
		if (!is_numeric($doc)) {
			$messages_erreur[] = $doc;
		} elseif (!$doc) {
			$messages_erreur[] = _T('medias:erreur_insertion_document_base', ['fichier' => '<em>???</em>']);
		} else {
			if (!$ancre) {
				$ancre = $doc;
			}
			$sel[] = $doc;
		}
	}

	if (count($messages_erreur)) {
		$res['message_erreur'] = implode('<br />', $messages_erreur);
	}
	if ($sel) {
		$res['message_ok'] = singulier_ou_pluriel(
			count($sel),
			'medias:document_installe_succes',
			'medias:nb_documents_installe_succes'
		);
		$res['ids'] = $sel;
		$sel_js = '#doc' . implode(',#doc', $sel);
		$js = "if (window.jQuery) jQuery(function(){ajaxReload('documents',{callback:function(){ jQuery('$sel_js').animateAppend(); }});});";
		$res['message_ok'] .= "<script type='text/javascript'>$js</script>";
	}
	if ($ancre) {
		$res['redirect'] = "#doc$ancre";
	}

	return $res;
}
