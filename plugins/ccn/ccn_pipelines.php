<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

define('_CCN_EXTENSIONS_UPLOAD', 'pdf,jpg,jpeg,png,gif,webp');

function ccn_formulaire_verifier($erreurs, $args) {
	if ($args[0] !== 'joindre_document' || count($erreurs) || _request('joindre_mediatheque')) {
		return $erreurs;
	}

	if (empty($_FILES['fichier_upload']['name'])) {
		return $erreurs;
	}

	$extensions_autorisees = explode(',', _CCN_EXTENSIONS_UPLOAD);
	foreach ((array) $_FILES['fichier_upload']['name'] as $nom) {
		$ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
		if (!in_array($ext, $extensions_autorisees)) {
			$erreurs['message_erreur'] = 'Extension non autorisée : .' . $ext
				. '. Formats acceptés : ' . implode(', ', $extensions_autorisees);
			break;
		}
	}

	return $erreurs;
}
