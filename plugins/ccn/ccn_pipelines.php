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

function ccn_post_edition($flux) {
	if (
		($flux['args']['type'] ?? '') !== 'document'
		or ($flux['args']['action'] ?? '') !== 'ajouter_document'
		or empty($flux['data']['fichier'])
	) {
		return $flux;
	}

	include_spip('inc/getdocument');
	$fichier = _DIR_RACINE . get_spip_doc($flux['data']['fichier']);
	if (!file_exists($fichier)) {
		return $flux;
	}

	$ext = strtolower($flux['data']['extension'] ?? pathinfo($fichier, PATHINFO_EXTENSION));
	$id_document = intval($flux['args']['id_objet']);

	include_spip('inc/uploads');

	if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
		ccn_compresser_image($fichier, $ext);
		if (file_exists($fichier)) {
			sql_updateq('spip_documents', ['taille' => filesize($fichier)], 'id_document=' . $id_document);
		}
	} elseif (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
		// Vidéo potentiellement volumineuse : la compression ffmpeg (et l'envoi
		// vers Vimeo qui s'enchaîne derrière, cf plugin api_vimeo) est différée
		// en tâche de fond pour ne pas bloquer la requête d'upload sur
		// max_execution_time.
		include_spip('inc/queue');
		queue_add_job(
			'ccn_compresser_video_job',
			'Compression vidéo document #' . $id_document,
			[$id_document, $fichier],
			'inc/uploads',
			false,
			0,
			5
		);
	}

	return $flux;
}

function ccn_formulaire_verifier($flux) {
    $erreurs = $flux['data'];

    if (count($erreurs) || _request('joindre_mediatheque')) {
        return $flux;
    }

	include_spip('inc/uploads');

    $erreurs = array_merge(
        $erreurs,
        ccn_verifier_uploads()
    );

    $flux['data'] = $erreurs;

    return $flux;
}

function ccn_formulaire_charger($flux) {
    return $flux;
}
