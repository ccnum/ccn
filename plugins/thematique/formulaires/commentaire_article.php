<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_commentaire_article_charger_dist($id_article) {

    $etape = _request('etape');

    if (_request('commentaire_action') === 'previsualiser') {
        $etape = 'previsualisation';
    }

    $id_parent = intval(_request('repondre_a'));

    if ($id_parent) {
        $etape = 'redaction';
    }

    $valeurs = [
        'id_article' => $id_article,
        'id_parent' => $id_parent,
        'titre' => _request('titre'),
        'texte' => _request('texte'),
        'etape' => $etape,
        'etat_affichage' => 'normal',
        'id_forum' => ''
    ];
    spip_log(print_r($valeurs, true), 'forum');
    return $valeurs;
}


function formulaires_commentaire_article_verifier_dist($id_article) {
    spip_log([
        'verifier',
        '_request(etape)' => _request('etape'),
        '_request(commentaire_action)' => _request('commentaire_action'),
    ], 'forum');
    $erreurs = [];

    // Validation uniquement lors de la demande de prévisualisation
    if (_request('commentaire_action') === 'previsualiser') {

        if (!trim(_request('titre'))) {
            $erreurs['titre'] = 'Le titre est obligatoire.';
        }

        if (!trim(_request('texte'))) {
            $erreurs['texte'] = 'Le commentaire est obligatoire.';
        }
    }

    return $erreurs;
}

function formulaires_commentaire_article_traiter_dist2($id_article) {

    die('TRAITER');

}


function formulaires_commentaire_article_traiter_dist($id_article) {

    spip_log([
        'traiter',
        '_request(etape)' => _request('etape'),
        '_request(commentaire_action)' => _request('commentaire_action'),
    ], 'forum');

    include_spip('action/editer_forum');

    // Annulation
    if (_request('commentaire_action') === 'annuler') {

        return [
            'redirect' => self()
        ];
    }

    // Passage en prévisualisation
    if (_request('commentaire_action') === 'previsualiser') {

        
        return [
            'editable' => true,
            'etape' => 'previsualisation',
            'titre' => _request('titre'),
            'texte' => _request('texte'),
            'id_parent' => intval(_request('id_parent')),
        ];
    }

    if (_request('commentaire_action') === 'publier') {

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