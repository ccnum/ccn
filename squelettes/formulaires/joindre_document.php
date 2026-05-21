<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

define('_EXTENSIONS_UPLOAD_AUTORISEES', 'pdf,jpg,jpeg,png,docx');

include_once _DIR_PLUGIN_MEDIAS . 'formulaires/joindre_document.php';

function formulaires_joindre_document_verifier(
    $id_document = 'new',
    $id_objet = 0,
    $objet = '',
    $mode = 'auto',
    $galerie = false,
    $proposer_media = true,
    $proposer_ftp = true
) {
    $erreurs = formulaires_joindre_document_verifier_dist(
        $id_document, $id_objet, $objet, $mode, $galerie, $proposer_media, $proposer_ftp
    );

    if (!count($erreurs) && !_request('joindre_mediatheque')) {
        $extensions_autorisees = explode(',', _EXTENSIONS_UPLOAD_AUTORISEES);
        if (!empty($_FILES['fichier_upload']['name'])) {
            $noms = (array) $_FILES['fichier_upload']['name'];
            foreach ($noms as $nom) {
                $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
                if (!in_array($ext, $extensions_autorisees)) {
                    $erreurs['message_erreur'] = 'Extension non autorisée : .' . $ext
                        . '. Formats acceptés : ' . implode(', ', $extensions_autorisees);
                    break;
                }
            }
        }
    }

    return $erreurs;
}