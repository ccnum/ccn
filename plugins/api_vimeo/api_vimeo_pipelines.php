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
			'restrictions' => [
				'voir'     => ['statut' => ['0minirezo', '1comite']],
				'modifier' => ['statut' => ['0minirezo', '1comite']],
			],
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

	$action      = $flux['args']['action'] ?? '';
	$id_document = intval($flux['args']['id_objet'] ?? 0);
	if (!$id_document) {
		return $flux;
	}

	include_spip('inc/api_vimeo');

	if ($action === 'insert') {
		$doc = sql_fetsel('titre, fichier, extension', 'spip_documents', 'id_document=' . $id_document);
		if ($doc && strtolower($doc['extension']) === 'mp4') {
			api_vimeo_upload($id_document, $doc);
		}
		return $flux;
	}

	if ($action === 'update' && array_key_exists('vimeo_password', $flux['data'] ?? [])) {
		$doc = sql_fetsel('fichier', 'spip_documents', 'id_document=' . $id_document);
		if ($doc && strpos($doc['fichier'], 'vimeo.com') !== false) {
			api_vimeo_set_password($doc['fichier'], $flux['data']['vimeo_password']);
		}
	}

	return $flux;
}
