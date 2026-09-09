<?php

/**
 * Extensions de fichiers acceptées pour tout champ "fichier_upload" du
 * site (ccn_verifier_uploads() ci-dessous en fait la vérification
 * serveur), à partir de la méta 'formats_documents_forum'. Utilisée pour
 * générer l'attribut HTML accept="" des inputs file correspondants (cf
 * plugins/thematique/formulaires/forum0.html/forum1.html et
 * formulaires/methodes_upload/upload.html), afin que le sélecteur de
 * fichiers du navigateur ne propose pas d'emblée des formats qui seront de
 * toute façon rejetés côté serveur.
 *
 * @return string[] Extensions sans le point (ex: ['gif','jpg','pdf'])
 */
function ccn_extensions_documents_acceptees() {
    $formats = trim($GLOBALS['meta']['formats_documents_forum'] ?? '');

    return $formats
        ? array_filter(preg_split(',[^a-zA-Z0-9/+_],', $formats))
        : [];
}

/**
 * Valeur prête à l'emploi pour l'attribut HTML accept="" d'un input file
 * (cf ccn_extensions_documents_acceptees ci-dessus). Vide si aucune
 * restriction n'est configurée (l'attribut accept ne doit alors pas être
 * posé, un accept="" viderait le sélecteur de fichiers).
 *
 * @return string Ex: ".gif,.jpg,.pdf" ou "" si pas de restriction
 */
function ccn_attribut_accept_documents() {
    $extensions = ccn_extensions_documents_acceptees();

    return $extensions ? '.' . implode(',.', $extensions) : '';
}

function ccn_verifier_uploads() {
    $erreurs = [];

    if (empty($_FILES['fichier_upload']['name'])) {
        return $erreurs;
    }

    $extensions_autorisees = ccn_extensions_documents_acceptees();

    $taille_max = _CCN_UPLOAD_TAILLE_MAX_MO * 1024 * 1024;

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
        // Si le plugin api_vimeo est désactivé, la vidéo resterait en local :
        // on lui applique alors la même limite qu'aux autres documents.
        $mp4_sans_limite = $ext === 'mp4' && defined('_DIR_PLUGIN_API_VIMEO');
        if (!$mp4_sans_limite && $taille > $taille_max) {
            $erreurs['message_erreur'] = _T(
                'ccn:ccn_fichier_trop_volumineux',
                ['nom' => $nom, 'taille_max' => _CCN_UPLOAD_TAILLE_MAX_MO]
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
