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