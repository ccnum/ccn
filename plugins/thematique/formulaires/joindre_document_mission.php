<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Formulaire d'ajout de document(s) sur une mission, restreint à une liste
 * d'extensions. L'envoi passe par bigup (#SAISIE_FICHIER, upload par
 * morceaux), comme joindre_video.php, pour les mêmes raisons (gros fichiers).
 * Logique commune aux deux formulaires factorisée dans
 * inc/thematique_joindre.php.
 *
 * @package SPIP\Thematique\Formulaires
 */

// _THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION et les filtres
// thematique_extensions_document_mission_accept/_liste sont définis dans
// thematique_fonctions.php (chargé pour toute compilation de squelette du
// plugin), pas ici — cf le commentaire à leur définition pour le pourquoi.

/**
 * Trouve le ou les fichiers envoyés dans $_FILES, restreints aux extensions
 * autorisées pour une mission.
 *
 * @return string|array
 */
function joindre_document_mission_trouver_fichier() {
	include_spip('inc/thematique_joindre');

	$files = thematique_joindre_trouver_fichiers();
	if (is_string($files)) {
		return $files;
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

	include_spip('inc/thematique_joindre');
	if ($erreur = thematique_joindre_verifier_autorisation($objet, $id_objet)) {
		$erreurs['message_erreur'] = $erreur;
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
	$files = joindre_document_mission_trouver_fichier();
	if (is_string($files)) {
		return ['editable' => true, 'message_erreur' => $files];
	}

	include_spip('inc/thematique_joindre');

	return thematique_joindre_ajouter_documents($files, $objet, $id_objet);
}
