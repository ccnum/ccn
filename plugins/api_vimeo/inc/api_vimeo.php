<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Orchestre l'upload d'un document SPIP vers Vimeo.
 *
 * @param int   $id_document
 * @param array $doc  Ligne spip_documents (titre, fichier, extension)
 */
function api_vimeo_upload(int $id_document, array $doc): bool {
	if (!defined('_VIMEO_ACCESS_TOKEN') || !_VIMEO_ACCESS_TOKEN) {
		spip_log('_VIMEO_ACCESS_TOKEN non défini dans mes_options.php', 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$fichier = _DIR_RACINE . $doc['fichier'];
	if (!file_exists($fichier)) {
		spip_log("Fichier introuvable : {$doc['fichier']}", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$file_size = filesize($fichier);
	$titre = $doc['titre'] ?: basename($doc['fichier']);

	$upload = api_vimeo_creer_upload($titre, $file_size);
	if (!$upload) {
		return false;
	}

	$success = api_vimeo_tus_upload($fichier, $file_size, $upload['upload_link']);
	if (!$success) {
		return false;
	}

	$site  = strtolower(str_replace(' ', '', lire_config('nom_site')));
	$annee = (string) intval(constant('_ANNEE_SCOLAIRE'));
	$id_dossier = api_vimeo_dossier_id($site, $annee);
	if ($id_dossier) {
		api_vimeo_ranger_dans_dossier($upload['link'], $id_dossier);
	}

	sql_updateq('spip_documents', [
		'fichier'  => $upload['link'],
		'distant'  => 'oui',
	], 'id_document=' . $id_document);
	spip_log("Vidéo uploadée : {$upload['link']} (document #$id_document)", 'api_vimeo' . _LOG_INFO);

	return true;
}

/**
 * Crée une session d'upload sur Vimeo (approche TUS).
 *
 * @return array{upload_link: string, link: string}|false
 */
function api_vimeo_creer_upload(string $titre, int $file_size): array|false {
	$payload = json_encode([
		'upload' => [
			'approach' => 'tus',
			'size'     => $file_size,
		],
		'name' => $titre,
	]);

	$ch = curl_init('https://api.vimeo.com/me/videos');
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => $payload,
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
			'Content-Type: application/json',
			'Accept: application/vnd.vimeo.*+json;version=3.4',
		],
	]);

	$response  = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code !== 200) {
		spip_log("Erreur création upload Vimeo (HTTP $http_code) : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$data = json_decode($response, true);

	if (empty($data['upload']['upload_link'])) {
		spip_log("Réponse Vimeo invalide : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	return [
		'upload_link' => $data['upload']['upload_link'],
		'link'        => $data['link'] ?? ('https://vimeo.com' . ltrim($data['uri'] ?? '', '/')),
	];
}

/**
 * Résout l'ID du dossier Vimeo "site/année" (le crée si besoin) et met en cache
 * le résultat dans spip_meta pour ne faire les appels de résolution qu'une fois.
 */
function api_vimeo_dossier_id(string $site, string $annee): string|false {
	$cle_meta = 'api_vimeo/dossier_' . $site . '_' . $annee;

	$id_cache = lire_config($cle_meta);
	if ($id_cache) {
		return $id_cache;
	}

	$id_site = api_vimeo_trouver_ou_creer_dossier($site);
	if (!$id_site) {
		return false;
	}

	$id_annee = api_vimeo_trouver_ou_creer_dossier($annee, $id_site);
	if (!$id_annee) {
		return false;
	}

	ecrire_config($cle_meta, $id_annee);

	return $id_annee;
}

/**
 * Cherche un dossier Vimeo par nom (sous un dossier parent le cas échéant)
 * et le crée s'il n'existe pas encore.
 */
function api_vimeo_trouver_ou_creer_dossier(string $nom, ?string $id_parent = null): string|false {
	if (!defined('_VIMEO_ACCESS_TOKEN') || !_VIMEO_ACCESS_TOKEN) {
		spip_log('_VIMEO_ACCESS_TOKEN non défini', 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$url_liste = $id_parent
		? "https://api.vimeo.com/me/folders/$id_parent/items?type=folder&per_page=100"
		: 'https://api.vimeo.com/me/folders?per_page=100';

	$ch = curl_init($url_liste);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
			'Accept: application/vnd.vimeo.*+json;version=3.4',
		],
	]);
	$response  = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code === 200) {
		$data = json_decode($response, true);
		foreach ($data['data'] ?? [] as $item) {
			$folder = $item['folder'] ?? $item;
			if (isset($folder['name'], $folder['uri']) && strcasecmp($folder['name'], $nom) === 0) {
				return basename($folder['uri']);
			}
		}
	}

	$payload = ['name' => $nom];
	if ($id_parent) {
		$payload['parent_folder_uri'] = "/folders/$id_parent";
	}

	$ch = curl_init('https://api.vimeo.com/me/folders');
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload),
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
			'Content-Type: application/json',
			'Accept: application/vnd.vimeo.*+json;version=3.4',
		],
	]);
	$response  = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code !== 201) {
		spip_log("Erreur création dossier Vimeo '$nom' (HTTP $http_code) : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$data = json_decode($response, true);
	if (empty($data['uri'])) {
		spip_log("Réponse Vimeo invalide (création dossier) : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	return basename($data['uri']);
}

/**
 * Ajoute une vidéo Vimeo (par son URL complète) à un dossier donné.
 */
function api_vimeo_ranger_dans_dossier(string $vimeo_url, string $id_dossier): bool {
	if (!preg_match('#vimeo\.com/(\d+)#', $vimeo_url, $m)) {
		spip_log("URL Vimeo invalide : $vimeo_url", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	$video_id = $m[1];

	$ch = curl_init("https://api.vimeo.com/me/folders/$id_dossier/videos/$video_id");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'PUT',
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
		],
	]);
	curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code !== 204) {
		spip_log("Erreur ajout vidéo $video_id au dossier $id_dossier (HTTP $http_code)", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	spip_log("Vidéo $video_id rangée dans le dossier $id_dossier", 'api_vimeo' . _LOG_INFO);
	return true;
}

/**
 * Met à jour la privacy d'une vidéo Vimeo.
 * Si $password est vide, la vidéo devient publique.
 *
 * @param string $vimeo_url  URL complète de la vidéo (https://vimeo.com/123456789)
 * @param string $password
 */
function api_vimeo_set_password(string $vimeo_url, string $password): bool {
	if (!defined('_VIMEO_ACCESS_TOKEN') || !_VIMEO_ACCESS_TOKEN) {
		spip_log('_VIMEO_ACCESS_TOKEN non défini', 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	if (!preg_match('#vimeo\.com/(\d+)#', $vimeo_url, $m)) {
		spip_log("URL Vimeo invalide : $vimeo_url", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	$video_id = $m[1];

	$privacy = $password
		? ['view' => 'password', 'password' => $password]
		: ['view' => 'anybody'];

	$payload = json_encode(['privacy' => $privacy]);

	$ch = curl_init("https://api.vimeo.com/videos/$video_id");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'PATCH',
		CURLOPT_POSTFIELDS     => $payload,
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
			'Content-Type: application/json',
			'Accept: application/vnd.vimeo.*+json;version=3.4',
		],
	]);

	curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code !== 200) {
		spip_log("Erreur set_password Vimeo (HTTP $http_code) vidéo $video_id", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$action = $password ? "protégée par mot de passe" : "rendue publique";
	spip_log("Vidéo $video_id $action", 'api_vimeo' . _LOG_INFO);
	return true;
}

/**
 * Upload le fichier vers Vimeo via le protocole TUS (chunks de 128 Mo).
 */
function api_vimeo_tus_upload(string $fichier, int $file_size, string $upload_link): bool {
	$chunk_size = 128 * 1024 * 1024; // 128 Mo

	$fp = fopen($fichier, 'rb');
	if (!$fp) {
		spip_log("Impossible d'ouvrir le fichier : $fichier", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	set_time_limit(0);
	ignore_user_abort(true);

	$offset = 0;

	while ($offset < $file_size) {
		$chunk        = fread($fp, min($chunk_size, $file_size - $offset));
		$chunk_length = strlen($chunk);

		$response_headers = [];

		$ch = curl_init($upload_link);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'PATCH',
			CURLOPT_POSTFIELDS     => $chunk,
			CURLOPT_HTTPHEADER     => [
				'Tus-Resumable: 1.0.0',
				'Upload-Offset: ' . $offset,
				'Content-Type: application/offset+octet-stream',
				'Content-Length: ' . $chunk_length,
			],
			CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$response_headers) {
				$parts = explode(':', $header, 2);
				if (count($parts) === 2) {
					$response_headers[strtolower(trim($parts[0]))] = trim($parts[1]);
				}
				return strlen($header);
			},
		]);

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code !== 204) {
			fclose($fp);
			spip_log("Erreur TUS PATCH (HTTP $http_code) à l'offset $offset", 'api_vimeo' . _LOG_ERREUR);
			return false;
		}

		$offset = isset($response_headers['upload-offset'])
			? (int) $response_headers['upload-offset']
			: $offset + $chunk_length;
	}

	fclose($fp);
	return true;
}
