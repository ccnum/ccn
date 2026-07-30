<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_forumv2_charger_dist($id_article) {
    $publication_forum_action = _request('publication_forum_action');

    $affichage = "redaction";
    if($publication_forum_action === "previsualiser") {
        $affichage = "previsualisation";
    }
    return [
        'id_article' => $id_article,
        'id_parent' => intval(_request('id_parent')),
        'titre' => _request('titre'),
        'texte' => _request('texte'),
        'affichage' => $affichage,
    ];
}


function formulaires_forumv2_verifier_dist($id_article) {
    $erreurs = [];

    if (_request('publication_forum_action') === 'previsualiser') {

        if (!trim(_request('titre'))) {
            $erreurs['titre'] = 'Le titre est obligatoire.';
        }

        if (!trim(_request('texte'))) {
            $erreurs['texte'] = 'Le commentaire est obligatoire.';
        }
    }
    return $erreurs;
}

function formulaires_forumv2_traiter_dist($id_article) {
    include_spip('action/editer_forum');


    if (_request('publication_forum_action') === 'previsualiser') {
        return [
            'affichage' => 'previsualisation',
            'titre' => _request('titre'),
            'texte' => _request('texte'),
        ];
    }

    if (_request('publication_forum_action') === 'publier') {

        $id_parent = intval(_request('id_parent'));

        $id_forum = forum_inserer(
            $id_parent,
            [
                'objet' => 'article',
                'id_objet' => $id_article,
                'titre' => _request('titre'),
                'texte' => _request('texte'),
                'statut' => 'publie',
            ]
        );

        $_SESSION['forum_commentaire_succes'] = $id_forum;

        return [
            'redirect' => generer_url_public(
                'article',
                [
                    'id_article' => $id_article,
                    'mode' => 'complet'
                ]
            )
        ];
    }

    return [];
}