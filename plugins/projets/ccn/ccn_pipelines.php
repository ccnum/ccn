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
	// Édition d'un auteur dans /ecrire : le cœur
	// SPIP ne génère le champ « votre mot de passe » (confirmation exigée pour créer
	// ou promouvoir un admin) que si l'auteur est déjà admin. À la création, ou pour
	// passer un rédacteur admin, l'enregistrement échouait toujours une première
	// fois. Il est désormais généré, masqué, et déplié par le JS du formulaire (cf
	// ccn_formulaire_fond) au choix du statut admin ou à la saisie d'un mot de passe.
	if (($flux['args']['form'] ?? '') === 'editer_auteur'
		&& isset($flux['data']['config'])
		&& is_array($flux['data']['config'])
	) {
		$id_auteur = intval($flux['args']['args'][0] ?? 0);
		include_spip('inc/autoriser');
		if (!$id_auteur || autoriser('modifier', 'auteur', $id_auteur, null, ['statut' => '0minirezo'])) {
			$flux['data']['config']['maybe_confirm_pass'] = ' ';
		}
	}
	return $flux;
}

function ccn_formulaire_fond($flux) {
	// Envoi de fichiers suspendu (cf _CCN_UPLOADS_SUSPENDUS) : Apache refuse alors
	// toute requête multipart, on remplace donc chaque formulaire qui en envoie
	// (cœur, plugins, site public) par un message plutôt que de le laisser échouer.
	if (_CCN_UPLOADS_SUSPENDUS && preg_match('/<form\b[^>]*enctype\s*=\s*["\']multipart\/form-data/i', $flux['data'])) {
		$flux['data'] = '<div class="formulaire_spip formulaire_' . $flux['args']['form'] . '">'
			. '<p class="reponse_formulaire reponse_formulaire_erreur" role="alert">' . _T('ccn:uploads_suspendus') . '</p>'
			. '</div>';
		return $flux;
	}

	// cf ccn_formulaire_charger : le cœur ne déplie le champ de confirmation qu'à la
	// saisie d'un nouveau mot de passe, pas au choix du statut admin ou webmestre.
	if (($flux['args']['form'] ?? '') === 'editer_auteur') {
		$flux['data'] .= "<script>
	jQuery('select#statut, input#webmestre').on('change', function() {
		if (jQuery('select#statut').val() === '0minirezo' || jQuery('input#webmestre').is(':checked')) {
			jQuery('.confirm-current-password:hidden').show('fast');
		}
	});
</script>";
	}
	return $flux;
}
