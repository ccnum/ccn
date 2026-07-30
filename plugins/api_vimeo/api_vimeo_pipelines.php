<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajoute un champ "Mot de passe Vimeo" sur la fiche document.
 */
function api_vimeo_declarer_champs_extras(array $champs = []): array {
	$champs['spip_documents']['vimeo_password'] = [
		'saisie'  => 'input',
		'options' => [
			'nom'    => 'vimeo_password',
			'label'  => _T('api_vimeo:champ_vimeo_password'),
			'sql'    => "varchar(255) NOT NULL DEFAULT ''",
			'defaut' => '',
			// Masque la valeur (placeholder) et préserve l'existant si le
			// champ est laissé vide à l'enregistrement (comportement natif
			// SPIP des clés secrètes) : cf api_vimeo_post_edition (action
			// 'instituer'), qui répercute tout changement sur Vimeo.
			'cle_secrete' => 'oui',
			'restrictions' => [
				'voir'     => ['statut' => ['0minirezo', '1comite']],
				'modifier' => ['statut' => ['0minirezo', '1comite']],
			],
		],
	];
	// en_attente / envoi / transcodage / disponible / erreur : cf api_vimeo_upload().
	// Sert à afficher la progression côté front et à ne rendre la vidéo
	// visible (oembed) qu'une fois 'disponible'.
	$champs['spip_documents']['vimeo_statut'] = [
		'saisie'  => 'input',
		'options' => [
			'nom'    => 'vimeo_statut',
			'label'  => 'Statut Vimeo',
			'sql'    => "varchar(20) NOT NULL DEFAULT ''",
			'defaut' => '',
		],
	];
	$champs['spip_documents']['vimeo_progression'] = [
		'saisie'  => 'input',
		'options' => [
			'nom'    => 'vimeo_progression',
			'label'  => 'Progression envoi Vimeo',
			'sql'    => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
			'defaut' => 0,
		],
	];
	return $champs;
}

/**
 * - insert MP4 : upload vers Vimeo
 * - update document Vimeo : synchronise le mot de passe
 */
function api_vimeo_post_edition(array $flux): array {
	if (($flux['args']['table'] ?? '') !== 'spip_documents') {
		return $flux;
	}

	spip_log('post_edition document reçu : action=' . ($flux['args']['action'] ?? '?') . ' id_objet=' . ($flux['args']['id_objet'] ?? '?'), 'api_vimeo' . _LOG_INFO_IMPORTANTE);

	$action      = $flux['args']['action'] ?? '';
	$id_document = intval($flux['args']['id_objet'] ?? 0);
	if (!$id_document) {
		return $flux;
	}

	if ($action === 'supprimer_document') {
		// Le document est déjà supprimé en base à ce stade : la ligne complète
		// (avant suppression) est fournie dans $flux['args']['document'].
		$doc = $flux['args']['document'] ?? null;
		if ($doc && !empty($doc['fichier']) && strpos($doc['fichier'], 'vimeo.com') !== false) {
			include_spip('inc/api_vimeo');
			api_vimeo_supprimer($doc['fichier']);
		}
		return $flux;
	}

	if ($action === 'ajouter_document') {
		$doc = sql_fetsel('extension', 'spip_documents', 'id_document=' . $id_document);
		if ($doc && strtolower($doc['extension']) === 'mp4') {
			// Différé en tâche de fond : la compression ffmpeg éventuelle
			// (plugin ccn, priorité 5) et l'upload TUS vers Vimeo peuvent être
			// longs et ne doivent pas bloquer la requête d'ajout du document.
			spip_log("Document #$id_document (mp4) : mise en file d'attente de l'envoi Vimeo", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
			sql_updateq('spip_documents', ['vimeo_statut' => 'en_attente', 'vimeo_progression' => 0], 'id_document=' . $id_document);
			include_spip('inc/queue');
			queue_add_job(
				'api_vimeo_upload_job',
				'Envoi Vimeo document #' . $id_document,
				[$id_document],
				'inc/api_vimeo',
				false,
				0,
				0
			);
		}
		return $flux;
	}

	include_spip('inc/api_vimeo');

	if ($action === 'instituer' && array_key_exists('vimeo_password', $flux['data'] ?? [])) {
		$doc = sql_fetsel('fichier', 'spip_documents', 'id_document=' . $id_document);
		if ($doc && strpos($doc['fichier'], 'vimeo.com') !== false) {
			api_vimeo_set_password($doc['fichier'], $flux['data']['vimeo_password']);
		}
	}

	return $flux;
}
