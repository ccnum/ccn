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

    $valeurs['id_rubrique'] = $id_rubrique;
    $valeurs['id_consigne'] = $id_consigne;

    return $valeurs;
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
            
        $retour = article_instituer(
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
    }

    return $res;
}




function formulaires_public_publier_article_verifier_dist(
    $id_rubrique,
    $id_consigne = 0
) {
    include_spip('inc/editer');
    include_spip('prive/formulaires/editer_article');

    return formulaires_editer_objet_verifier(
        'article',
        'new',
        [
            'titre',
            'texte'
        ]
    );
}