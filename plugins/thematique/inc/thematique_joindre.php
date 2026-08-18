<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Helpers communs aux formulaires d'envoi de fichier(s) sur un objet SPIP
 * (joindre_document_mission, joindre_video), tous deux basés sur bigup
 * (#SAISIE_FICHIER, upload par morceaux) et sur le même squelette
 * charger/vérifier/traiter.
 *
 * @package SPIP\Thematique\Inc
 */

/**
 * Récupère les fichiers envoyés dans $_FILES via bigup (cf le docblog de
 * joindre_video_trouver_fichier() pour le détail du choix de
 * joindre_trouver_http_post_files() plutôt que joindre_trouver_fichier_envoye()).
 * Ne valide pas les extensions : à la charge de l'appelant, les formats
 * autorisés différant selon le formulaire.
 *
 * @return string|array Message d'erreur, ou tableau de fichiers ($_FILES-like)
 */
function thematique_joindre_trouver_fichiers() {
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
 * Vérifie l'autorisation à joindre un document sur $objet/$id_objet.
 *
 * @return string|null Message d'erreur, ou null si autorisé
 */
function thematique_joindre_verifier_autorisation($objet, $id_objet) {
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
 * @param array $files Fichiers validés (retour de thematique_joindre_trouver_fichiers())
 * @param string $objet
 * @param int $id_objet
 * @return array Réponse CVT (cf formulaires_joindre_video_traiter_dist)
 */
function thematique_joindre_ajouter_documents($files, $objet, $id_objet) {
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
