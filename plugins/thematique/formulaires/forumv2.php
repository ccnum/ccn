<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function forumv2_texte_est_valide() {
    return trim(_request('texte')) !== '';
}

function formulaires_forumv2_charger_dist($id_article) {
   $publication_forum_action = _request('publication_forum_action');

    $affichage = 'redaction';
    if ($publication_forum_action === 'previsualiser' && forumv2_texte_est_valide()) {
        $affichage = 'previsualisation';
    }

    return [
        'id_article' => $id_article,
        'id_parent'  => intval(_request('id_parent')),
        'texte'      => _request('texte'),
        'affichage'  => $affichage,
    ];
}


function formulaires_forumv2_verifier_dist($id_article) {
    $erreurs = [];

    if (_request('publication_forum_action') === 'previsualiser' && !forumv2_texte_est_valide()) {
        $erreurs['texte'] = 'Le texte est obligatoire.';
    }
    return $erreurs;
}

function formulaires_forumv2_traiter_dist($id_article) {
    include_spip('action/editer_forum');


    if (_request('publication_forum_action') === 'previsualiser') {
        return [
            'affichage' => 'previsualisation',
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