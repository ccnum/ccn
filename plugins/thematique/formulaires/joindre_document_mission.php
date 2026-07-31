<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Formulaire d'ajout de document(s) sur une mission, restreint à une liste
 * d'extensions. L'envoi passe par bigup (#SAISIE_FICHIER, upload par
 * morceaux), comme joindre_video.php, pour les mêmes raisons (gros fichiers).
 *
 * @package SPIP\Thematique\Formulaires
 */

define('_THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION', ['gif', 'jpg', 'jpeg', 'png', 'mp3', 'pdf']);

/**
 * Trouve le ou les fichiers envoyés dans $_FILES (cf joindre_video_trouver_fichier
 * pour le détail du choix de joindre_trouver_http_post_files() plutôt que
 * joindre_trouver_fichier_envoye()).
 *
 * @return string|array
 */
function joindre_document_mission_trouver_fichier() {
	include_spip('inc/joindre_document');
	include_spip('action/ajouter_documents');

	$files = joindre_trouver_http_post_files();
	if (is_string($files)) {
		return $files;
	}
	if (!count($files)) {
		return _T('medias:erreur_indiquez_un_fichier');
	}
	foreach ($files as $file) {
		$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
		if (!in_array($ext, _THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION)) {
			return _T('thematique:erreur_extension_document_mission', [
				'extension' => $ext,
				'extensions' => implode(', ', _THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION),
			]);
		}
	}

	return $files;
}

function formulaires_joindre_document_mission_charger_dist($id_objet = 0, $objet = '') {
	return [
		'id_objet' => $id_objet,
		'objet' => $objet,
		// active la recherche/réinjection des fichiers uploadés par bigup
		'_bigup_rechercher_fichiers' => true,
	];
}

function formulaires_joindre_document_mission_verifier_dist($id_objet = 0, $objet = '') {
	$erreurs = [];

	include_spip('inc/autoriser');
	if (!autoriser('joindredocument', $objet, $id_objet)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
		return $erreurs;
	}

	$files = joindre_document_mission_trouver_fichier();

	if (is_string($files)) {
		$erreurs['message_erreur'] = $files;
		return $erreurs;
	}

	if (!is_array($files) || !count($files)) {
		$erreurs['message_erreur'] = _T('medias:erreur_aucun_fichier');
	}

	return $erreurs;
}

function formulaires_joindre_document_mission_traiter_dist($id_objet = 0, $objet = '') {
	$res = ['editable' => true];

	$files = joindre_document_mission_trouver_fichier();
	if (is_string($files)) {
		$res['message_erreur'] = $files;
		return $res;
	}

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
