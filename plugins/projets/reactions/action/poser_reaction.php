<?php
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function action_poser_reaction_dist() {
    // 1. Sécurisation de l'action par SPIP
    $securiser_action = charger_fonction('securiser_action', 'inc');
    $arg = $securiser_action(); 

    // Découpage de l'argument : "article-204-like"
    list($objet, $id_objet, $type_reaction) = explode('-', $arg);
    $id_objet = intval($id_objet);
    
    $retour = ['ok' => false];

    include_spip('inc/autoriser');
    if ($objet && $id_objet && $type_reaction && autoriser('reactionposer', $objet, $id_objet)) {
        
        include_spip('formulaires/reaction'); 
        include_spip('reactions_fonctions'); // 🚀 Crucial pour avoir reactions_types_actifs()
        
        $types_actifs = function_exists('reactions_types_actifs') ? reactions_types_actifs() : [];
        if (in_array($type_reaction, $types_actifs)) {
            
            // Logique de bascule (Toggle)
            $deja_pose = reactions_a_deja_reagi($objet, $id_objet, $type_reaction);

            if ($deja_pose) {
                // L'utilisateur avait déjà cliqué : on retire (décrémente)
                reactions_retirer($objet, $id_objet, $type_reaction);
                $retour['action'] = 'retire';
            } else {
                // L'utilisateur n'avait pas cliqué : on ajoute (incrémente)
                reactions_poser($objet, $id_objet, $type_reaction);
                $retour['action'] = 'pose';
            }

            // On récupère TOUS les compteurs mis à jour (ex: ['like' => 14, 'love' => 3])
            $retour['ok'] = true;
            $retour['compteurs'] = reactions_compter($objet, $id_objet);
        } else {
            $retour['erreur'] = 'Reaction non activee dans la configuration';
            spip_log("Tentative de réaction non active : " . $type_reaction, "reactions." . _LOG_ERREUR);
        }
    } else {
        $retour['erreur'] = 'Action non autorisee';
    }

    // Envoi de la réponse brute en JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($retour, JSON_UNESCAPED_UNICODE);
    exit;
}