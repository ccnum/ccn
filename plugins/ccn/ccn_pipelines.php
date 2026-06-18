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

	if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
		ccn_compresser_image($fichier, $ext);
	} elseif (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
		ccn_compresser_video($fichier);
	} else {
		return $flux;
	}

	if (file_exists($fichier)) {
		sql_updateq('spip_documents', ['taille' => filesize($fichier)], 'id_document=' . $id_document);
	}

	return $flux;
}

function ccn_compresser_image($fichier, $ext) {
	if (!function_exists('imagecreatefromjpeg')) {
		return;
	}
	switch ($ext) {
		case 'jpg':
		case 'jpeg':
			if ($img = @imagecreatefromjpeg($fichier)) {
				imagejpeg($img, $fichier, 85);
				imagedestroy($img);
			}
			break;
		case 'png':
			if ($img = @imagecreatefrompng($fichier)) {
				imagesavealpha($img, true);
				imagepng($img, $fichier, 7);
				imagedestroy($img);
			}
			break;
		case 'webp':
			if ($img = @imagecreatefromwebp($fichier)) {
				imagewebp($img, $fichier, 85);
				imagedestroy($img);
			}
			break;
	}
}

function ccn_compresser_video($fichier) {
	if (!function_exists('exec')) {
		return;
	}
	$ffmpeg = trim((string) shell_exec('which ffmpeg 2>/dev/null'));
	if (!$ffmpeg) {
		return;
	}
	$tmp = $fichier . '.ccn_tmp.mp4';
	exec(
		$ffmpeg . ' -y -i ' . escapeshellarg($fichier)
		. ' -vcodec libx264 -crf 28 -acodec aac '
		. escapeshellarg($tmp) . ' 2>/dev/null',
		$out,
		$code
	);
	if ($code === 0 and file_exists($tmp)) {
		if (filesize($tmp) < filesize($fichier)) {
			rename($tmp, $fichier);
		} else {
			unlink($tmp);
		}
	} elseif (file_exists($tmp)) {
		unlink($tmp);
	}
}

function ccn_formulaire_verifier($flux) {
	$erreurs = $flux['data'];
	$args = $flux['args'];

	if ($args[0] !== 'joindre_document' || count($erreurs) || _request('joindre_mediatheque')) {
		return $flux;
	}

	// Détecter si PHP a rejeté le fichier en amont (trop lourd)
	$errors = (array) ($_FILES['fichier_upload']['error'] ?? []);
	foreach ($errors as $err) {
		if (in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
				$erreurs['message_erreur'] = 'Le fichier dépasse la taille maximale autorisée (100 Mo).';
				$flux['data'] = $erreurs;
				return $flux;
		}
	}

	if (empty($_FILES['fichier_upload']['name'])) {
			return $flux;
	}

	// Vérifier la taille (100 Mo max)
	$taille_max = 100 * 1024 * 1024;
	foreach ((array) $_FILES['fichier_upload']['size'] as $taille) {
		if ($taille > $taille_max) {
				$erreurs['message_erreur'] = 'Le fichier dépasse la taille maximale autorisée (100 Mo).';
				$flux['data'] = $erreurs;
				return $flux;
		}
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

function ccn_formulaire_charger($flux) {
    if (($flux['args']['form'] ?? '') !== 'forum') {
        return $flux;
    }
    
    $max = 100 * 1024 * 1024;
    $content_length = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
    
    if ($content_length > $max) {
        $flux['data']['message_erreur'] = 'Le fichier dépasse la taille maximale autorisée (100 Mo).';
    }
    
    return $flux;
}
