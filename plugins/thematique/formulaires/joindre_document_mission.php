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

define('_THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION', ['gif', 'jpg', 'jpeg', 'png', 'mp3', 'pdf']);

/**
 * Valeur de l'attribut HTML accept pour le champ fichier de ce formulaire
 * (".gif,.jpg,..."), à partir de _THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION.
 *
 * Ne peut pas être écrite en dur dans le squelette (`accept=.gif,.jpg,...`) :
 * le compilateur SPIP découpe les arguments d'un `#SAISIE_XXX{...}` sur
 * chaque virgule *avant* toute prise en compte des guillemets (ce n'est pas
 * le tokenizer standard des filtres), donc une valeur avec virgules littérales
 * y est systématiquement tronquée à son premier fragment (`.gif` seul,
 * cf issue #407) — y compris entre guillemets. Passer par une balise
 * calculée (`#GET{...}`) contourne le problème : elle ne contient aucune
 * virgule dans le squelette source, seulement à l'exécution.
 *
 * Appelée comme filtre : `#VAL{1}|thematique_extensions_document_mission_accept}`
 * (le premier paramètre n'est qu'un porteur, SPIP exige toujours une valeur pipée).
 *
 * @param mixed $valeur_ignoree Non utilisé, cf. remarque d'appel ci-dessus
 * @return string
 */
function thematique_extensions_document_mission_accept($valeur_ignoree = null) {
	static $accept = null;
	if ($accept === null) {
		$accept = implode(',', array_map(fn ($ext) => ".$ext", _THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION));
	}

	return $accept;
}

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
