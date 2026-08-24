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
 * Logique commune à joindre_document_mission.php factorisée dans
 * inc/thematique_joindre.php.
 *
 * Le mot de passe est stocké tel quel sur le document (champ extra
 * vimeo_password, cf plugin api_vimeo) : il est appliqué sur Vimeo une fois
 * l'envoi terminé (cf api_vimeo_upload()).
 *
 * @package SPIP\Thematique\Formulaires
 */

define('_VIMEO_EXTENSIONS_AUTORISEES', ['mp4', 'mov', 'avi', 'mkv', 'webm']);

/**
 * Trouve le fichier envoyé dans $_FILES, restreint aux extensions vidéo
 * autorisées. Avec _bigup_rechercher_fichiers activé (cf charger_dist),
 * bigup réinjecte le fichier uploadé par morceaux dans $_FILES avant
 * verifier()/traiter(), donc ce helper fonctionne à l'identique d'un
 * upload classique.
 *
 * On n'utilise volontairement pas joindre_trouver_fichier_envoye() (qui
 * exige _request('joindre_upload')) : le plugin bigup cache tout bouton
 * submit nommé "joindre_upload" trouvé sur la page (cf bigup.documents.js),
 * ce qui rendait ce formulaire invalidable si on reprenait ce nom.
 *
 * @return string|array
 */
function joindre_video_trouver_fichier() {
	include_spip('inc/thematique_joindre');

	$files = thematique_joindre_trouver_fichiers();
	if (is_string($files)) {
		return $files;
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

	include_spip('inc/thematique_joindre');
	if ($erreur = thematique_joindre_verifier_autorisation($objet, $id_objet)) {
		$erreurs['message_erreur'] = $erreur;
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
			$erreurs['message_erreur'] = _T('thematique:format_video_non_accepte', [
				'extension' => $ext,
				'extensions' => implode(', ', _VIMEO_EXTENSIONS_AUTORISEES),
			]);
			return $erreurs;
		}
	}

	return $erreurs;
}

function formulaires_joindre_video_traiter_dist($id_objet = 0, $objet = '') {
	$files = joindre_video_trouver_fichier();
	if (is_string($files)) {
		return ['editable' => true, 'message_erreur' => $files];
	}

	include_spip('inc/thematique_joindre');
	$res = thematique_joindre_ajouter_documents($files, $objet, $id_objet);

	if (!empty($res['ids']) && ($mot_de_passe = _request('vimeo_password'))) {
		foreach ($res['ids'] as $id_document) {
			sql_updateq('spip_documents', ['vimeo_password' => $mot_de_passe], 'id_document=' . intval($id_document));
		}
	}

	return $res;
}
