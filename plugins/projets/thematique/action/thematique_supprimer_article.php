<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function action_thematique_supprimer_article_dist($arg = null) {
    if (is_null($arg)) {
        $securiser_action = charger_fonction('securiser_action', 'inc');
        $arg = $securiser_action();
    }
    $id_article = intval($arg);
    if (!$id_article || !autoriser('supprimer', 'article', $id_article)) {
        http_response_code(403);
        exit;
    }
    include_spip('action/editer_objet');
    objet_instituer('article', $id_article, ['statut' => 'poubelle']);

    http_response_code(200);
    exit;
}