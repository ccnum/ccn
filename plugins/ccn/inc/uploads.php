<?php

function ccn_verifier_uploads() {
    $erreurs = [];

    if (empty($_FILES['fichier_upload']['name'])) {
        return $erreurs;
    }

    $formats = trim($GLOBALS['meta']['formats_documents_forum'] ?? '');
    $extensions_autorisees = $formats
        ? array_filter(preg_split(',[^a-zA-Z0-9/+_],', $formats))
        : [];

    $taille_max = 100 * 1024 * 1024;

    foreach ((array) $_FILES['fichier_upload']['name'] as $cle => $nom) {

        $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));

        if ($extensions_autorisees && !in_array($ext, $extensions_autorisees)) {
            $erreurs['message_erreur'] = _T(
                'ccn:ccn_extension_non_autorisee',
                [
                    'ext' => $ext,
                    'formats' => implode(', ', $extensions_autorisees),
                ]
            );
            break;
        }

        $taille = $_FILES['fichier_upload']['size'][$cle] ?? 0;

        // Pas de limite de taille pour les MP4 : ils sont poussés vers Vimeo après upload.
        if ($ext !== 'mp4' && $taille > $taille_max) {
            $erreurs['message_erreur'] = _T(
                'ccn:ccn_fichier_trop_volumineux',
                ['nom' => $nom]
            );
            break;
        }
    }

    return $erreurs;
}

/**
 * Tâche de fond : compresse la vidéo via ffmpeg puis met à jour sa taille.
 * Priorité haute pour s'exécuter avant l'envoi Vimeo (cf api_vimeo_upload_job).
 */
function ccn_compresser_video_job(int $id_document, string $fichier) {
    ccn_compresser_video($fichier);

    if (file_exists($fichier)) {
        sql_updateq('spip_documents', ['taille' => filesize($fichier)], 'id_document=' . $id_document);
    }
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
