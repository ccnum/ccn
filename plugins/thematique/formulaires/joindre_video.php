<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Formulaire d'ajout d'une vidéo (1 fichier + mot de passe Vimeo optionnel)
 * sur un objet SPIP. L'envoi du fichier passe par bigup (#SAISIE_FICHIER,
 * upload par morceaux) : indispensable pour les grosses vidéos, un upload
 * classique en un seul POST peut saturer la mémoire du serveur (VM
 * redémarrée en OOM avec un simple <input type=file>, cf constat en prod).
 *
 * Le mot de passe est stocké tel quel sur le document (champ extra
 * vimeo_password, cf plugin api_vimeo) : il est appliqué sur Vimeo une fois
 * l'envoi terminé (cf api_vimeo_upload()).
 *
 * @package SPIP\Thematique\Formulaires
 */

define('_VIMEO_EXTENSIONS_AUTORISEES', ['mp4', 'mov', 'avi', 'mkv', 'webm']);

/**
 * Trouve le fichier envoyé dans $_FILES. Avec _bigup_rechercher_fichiers
 * activé (cf charger_dist), bigup réinjecte le fichier uploadé par morceaux
 * dans $_FILES avant verifier()/traiter(), donc ce helper fonctionne à
 * l'identique d'un upload classique.
 *
 * On n'utilise volontairement pas joindre_trouver_fichier_envoye() (qui
 * exige _request('joindre_upload')) : le plugin bigup cache tout bouton
 * submit nommé "joindre_upload" trouvé sur la page (cf bigup.documents.js),
 * ce qui rendait ce formulaire invalidable si on reprenait ce nom.
 *
 * @return string|array
 */
function joindre_video_trouver_fichier() {
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
		if (!is_array(verifier_upload_autorise($file['name']))) {
			return _T('medias:erreur_upload_type_interdit', ['nom' => $file['name']]);
		}
	}

	return $files;
}

function formulaires_joindre_video_charger_dist($id_objet = 0, $objet = '') {
	return [
		'id_objet' => $id_objet,
		'objet' => $objet,
		// active la recherche/réinjection des fichiers uploadés par bigup
		'_bigup_rechercher_fichiers' => true,
	];
}

function formulaires_joindre_video_verifier_dist($id_objet = 0, $objet = '') {
	$erreurs = [];

	include_spip('inc/autoriser');
	if (!autoriser('joindredocument', $objet, $id_objet)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
		return $erreurs;
	}

	$files = joindre_video_trouver_fichier();

	if (is_string($files)) {
		$erreurs['message_erreur'] = $files;
		return $erreurs;
	}

	if (!is_array($files) || !count($files)) {
		$erreurs['message_erreur'] = _T('medias:erreur_aucun_fichier');
		return $erreurs;
	}

	foreach ($files as $file) {
		$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
		if (!in_array($ext, _VIMEO_EXTENSIONS_AUTORISEES)) {
			$erreurs['message_erreur'] = 'Format vidéo non accepté : ' . $ext
				. ' (formats acceptés : ' . implode(', ', _VIMEO_EXTENSIONS_AUTORISEES) . ')';
			return $erreurs;
		}
	}

	return $erreurs;
}

function formulaires_joindre_video_traiter_dist($id_objet = 0, $objet = '') {
	$res = ['editable' => true];

	$files = joindre_video_trouver_fichier();
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

	if ($mot_de_passe = _request('vimeo_password')) {
		foreach ($sel as $id_document) {
			sql_updateq('spip_documents', ['vimeo_password' => $mot_de_passe], 'id_document=' . intval($id_document));
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
