<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tâche de fond (cf plugin ccn, queue_add_job) : recharge le document
 * depuis la base (son fichier a pu être remplacé entre-temps par la
 * compression ffmpeg) puis l'envoie vers Vimeo.
 */
function api_vimeo_upload_job(int $id_document): bool {
	spip_log("Job Vimeo démarré pour le document #$id_document", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

	$doc = sql_fetsel('titre, fichier, extension, vimeo_password', 'spip_documents', 'id_document=' . $id_document);
	if (!$doc) {
		spip_log("Job Vimeo #$id_document : document introuvable en base, abandon", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	if (strtolower($doc['extension']) !== 'mp4') {
		spip_log("Job Vimeo #$id_document : extension '{$doc['extension']}' non mp4, abandon", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	return api_vimeo_upload($id_document, $doc);
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

	$fichier = _DIR_IMG . $doc['fichier'];
	if (!file_exists($fichier)) {
		spip_log("Fichier introuvable : {$doc['fichier']}", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$file_size = filesize($fichier);
	$titre = $doc['titre'] ?: basename($doc['fichier']);
	spip_log("Document #$id_document : fichier '$fichier' trouvé (" . round($file_size / 1024 / 1024, 1) . " Mo), démarrage de la création de session Vimeo", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

	$upload = api_vimeo_creer_upload($titre, $file_size);
	if (!$upload) {
		spip_log("Document #$id_document : échec de la création de la session d'upload Vimeo", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	spip_log("Document #$id_document : session Vimeo créée ({$upload['link']}), démarrage de l'envoi TUS", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

	$success = api_vimeo_tus_upload($fichier, $file_size, $upload['upload_link']);
	if (!$success) {
		spip_log("Document #$id_document : échec de l'envoi TUS vers Vimeo", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	spip_log("Document #$id_document : envoi TUS terminé ({$upload['link']})", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

	if (!empty($doc['vimeo_password'])) {
		api_vimeo_set_password($upload['link'], $doc['vimeo_password']);
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
	spip_log("Vidéo uploadée : {$upload['link']} (document #$id_document)", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

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

	$dossier_site = api_vimeo_trouver_ou_creer_dossier($site);
	if (!$dossier_site) {
		return false;
	}

	$dossier_annee = api_vimeo_trouver_ou_creer_dossier($annee, $dossier_site['uri']);
	if (!$dossier_annee) {
		return false;
	}

	ecrire_config($cle_meta, $dossier_annee['id']);

	return $dossier_annee['id'];
}

/**
 * Cherche un dossier Vimeo par nom (sous un dossier parent le cas échéant)
 * et le crée s'il n'existe pas encore.
 *
 * $uri_parent doit être l'URI Vimeo complète du dossier parent (ex :
 * "/users/12345/projects/6789"), telle que renvoyée par l'API : Vimeo
 * refuse un parent_folder_uri reconstruit à la main (ex : "/folders/6789").
 *
 * @return array{id: string, uri: string}|false
 */
function api_vimeo_trouver_ou_creer_dossier(string $nom, ?string $uri_parent = null): array|false {
	if (!defined('_VIMEO_ACCESS_TOKEN') || !_VIMEO_ACCESS_TOKEN) {
		spip_log('_VIMEO_ACCESS_TOKEN non défini', 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	$id_parent = $uri_parent ? basename($uri_parent) : null;
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
				return ['id' => basename($folder['uri']), 'uri' => $folder['uri']];
			}
		}
	}

	$payload = ['name' => $nom];
	if ($uri_parent) {
		$payload['parent_folder_uri'] = $uri_parent;
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

	return ['id' => basename($data['uri']), 'uri' => $data['uri']];
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

	// Les dossiers renvoyés par api_vimeo_trouver_ou_creer_dossier() sont des
	// URI /users/{id}/projects/{id} (cf son commentaire) : sur ce compte,
	// l'API n'expose ces dossiers que sous /me/projects, pas /me/folders
	// (qui répondait 403, l'id n'y correspondant à aucune ressource).
	$ch = curl_init("https://api.vimeo.com/me/projects/$id_dossier/videos/$video_id");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'PUT',
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
		],
	]);
	$response  = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code !== 204) {
		spip_log("Erreur ajout vidéo $video_id au dossier $id_dossier (HTTP $http_code) : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	spip_log("Vidéo $video_id rangée dans le dossier $id_dossier", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
	return true;
}

/**
 * Supprime une vidéo sur Vimeo.
 *
 * @param string $vimeo_url  URL complète de la vidéo (https://vimeo.com/123456789)
 */
function api_vimeo_supprimer(string $vimeo_url): bool {
	if (!defined('_VIMEO_ACCESS_TOKEN') || !_VIMEO_ACCESS_TOKEN) {
		spip_log('_VIMEO_ACCESS_TOKEN non défini', 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	if (!preg_match('#vimeo\.com/(\d+)#', $vimeo_url, $m)) {
		spip_log("URL Vimeo invalide : $vimeo_url", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}
	$video_id = $m[1];

	$ch = curl_init("https://api.vimeo.com/videos/$video_id");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'DELETE',
		CURLOPT_HTTPHEADER     => [
			'Authorization: bearer ' . _VIMEO_ACCESS_TOKEN,
		],
	]);
	$response  = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	// 404 = déjà supprimée côté Vimeo : pas la peine d'échouer pour autant.
	if ($http_code !== 204 && $http_code !== 404) {
		spip_log("Erreur suppression vidéo $video_id (HTTP $http_code) : $response", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	spip_log("Vidéo $video_id supprimée sur Vimeo", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
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
	spip_log("Vidéo $video_id $action", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
	return true;
}

/**
 * Upload le fichier vers Vimeo via le protocole TUS (chunks de 8 Mo).
 */
function api_vimeo_tus_upload(string $fichier, int $file_size, string $upload_link): bool {
	// Chargé entièrement en mémoire (fread + copie interne de curl dans
	// CURLOPT_POSTFIELDS) : rester très en dessous des ~300 Mo de RAM du
	// serveur (cf commentaire sur _IMG_MAX_WIDTH/_IMG_MAX_HEIGHT dans
	// mes_options.php) pour ne pas déclencher un OOM sur les grosses vidéos.
	$chunk_size = 8 * 1024 * 1024; // 8 Mo

	$fp = fopen($fichier, 'rb');
	if (!$fp) {
		spip_log("Impossible d'ouvrir le fichier : $fichier", 'api_vimeo' . _LOG_ERREUR);
		return false;
	}

	set_time_limit(0);
	ignore_user_abort(true);

	$offset = 0;
	spip_log("Envoi TUS : démarrage (" . round($file_size / 1024 / 1024, 1) . " Mo, chunks de " . round($chunk_size / 1024 / 1024) . " Mo)", 'api_vimeo' . _LOG_INFO_IMPORTANTE);

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

		spip_log("Envoi TUS : $offset / $file_size octets envoyés (" . round($offset / $file_size * 100) . "%)", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
	}

	fclose($fp);
	spip_log("Envoi TUS : terminé ($file_size octets)", 'api_vimeo' . _LOG_INFO_IMPORTANTE);
	return true;
}
