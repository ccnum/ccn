<?php

/**************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_public_publier_article_charger_dist(
    $id_rubrique,
    $id_consigne = 0
) {
    include_spip('inc/editer');
    include_spip('prive/formulaires/editer_article');

    $valeurs = formulaires_editer_objet_charger(
        'article',
        'new',
        $id_rubrique
    );

    // Variables utiles au squelette
    $valeurs['id_rubrique'] = $id_rubrique;
    $valeurs['id_parent'] = $id_rubrique;
    $valeurs['id_consigne'] = $id_consigne;

    return $valeurs;
}

function formulaires_public_publier_article_verifier_dist(
    $id_rubrique,
    $id_consigne = 0
) {
    include_spip('inc/editer');
    include_spip('prive/formulaires/editer_article');
    include_spip('inc/uploads');

    $erreurs = formulaires_editer_objet_verifier(
        'article',
        'new',
        [
            'titre',
            'texte'
        ]
    );
    $max_caracteres = 50;
    if (empty($erreurs['titre']) && strlen(_request('titre')) > $max_caracteres) {
		$erreurs['titre'] = 'Le titre ne peut pas dépasser' . $max_caracteres . 'caractères.';
	}    
    $erreurs = array_merge(
        $erreurs,
        ccn_verifier_uploads()
    );
    return $erreurs;
}

function formulaires_public_publier_article_traiter_dist(
    $id_rubrique,
    $id_consigne = 0
) {
    include_spip('inc/editer');
    include_spip('prive/formulaires/editer_article');

    $res = formulaires_editer_objet_traiter(
        'article',
        'new',
        $id_rubrique
    );

    if (empty($res['erreurs']) && !empty($res['id_article'])) {
        include_spip('inc/joindre_document');
        $files = joindre_trouver_http_post_files('fichier_upload');
        if ($files) {
            $ajouter_documents = charger_fonction(
                'ajouter_documents',
                'action'
            );
            $ajouter_documents(
                'new',
                $files,
                'article',
                $res['id_article'],
                'document'
            );
        }

        if ($id_consigne) {
            include_spip('action/editer_objet');
            objet_modifier(
                'article',
                $res['id_article'],
                [
                    'id_consigne' => $id_consigne
                ]
            );
        } 
        article_instituer(
            $res['id_article'],
            [
                'statut' => 'publie'
            ]
        );
        $res['message_ok'] = "Article publié !";
        $res['redirect'] = generer_url_public(
            'article',
            'id_article=' . $res['id_article'] . '&mode=complet'
        );

        if (_request('attendre_livrable') === 'oui') {
            include_spip('action/editer_liens');

            if ($id_mot_livrable = sql_getfetsel('id_mot', 'spip_mots', "titre='livrable'")) {
                objet_associer(
                    ['mots' => $id_mot_livrable],
                    ['articles' => $res['id_article']]
                );
            }
        }
    }
    return $res;
}
