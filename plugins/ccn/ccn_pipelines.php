<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

function ccn_boite_infos($flux) {
	if ($flux['args']['type'] !== 'auteur' or !($id_auteur = intval($flux['args']['id'] ?? 0))) {
		return $flux;
	}

	$source = sql_getfetsel('source', 'spip_auteurs', 'id_auteur=' . $id_auteur);
	if (!$source) {
		return $flux;
	}

	$flux['data'] .= '<p>Source d\'authentification : <code>' . spip_htmlspecialchars($source) . '</code></p>';

	return $flux;
}

function ccn_formulaire_verifier($flux) {
	$erreurs = $flux['data'];
	$args = $flux['args'];

	if ($args[0] !== 'joindre_document' || count($erreurs) || _request('joindre_mediatheque')) {
		return $flux;
	}

	if (empty($_FILES['fichier_upload']['name'])) {
		return $flux;
	}

	$formats = trim($GLOBALS['meta']['formats_documents_forum'] ?? '');
	$extensions_autorisees = $formats
		? array_filter(preg_split(',[^a-zA-Z0-9/+_],', $formats))
		: [];

	foreach ((array) $_FILES['fichier_upload']['name'] as $nom) {
		$ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
		if ($extensions_autorisees && !in_array($ext, $extensions_autorisees)) {
			$erreurs['message_erreur'] = 'Extension non autorisée : .' . $ext
				. '. Formats acceptés : ' . implode(', ', $extensions_autorisees);
			break;
		}
	}

	$flux['data'] = $erreurs;
	return $flux;
}
