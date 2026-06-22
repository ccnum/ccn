<?php
/**
 * Formulaire de pose d'une réaction (CVT)
 *
 * Usage dans un squelette :
 * #FORMULAIRE_REACTION{article, #ID_ARTICLE}
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('reactions_fonctions');


/**
 * Chargement : prépare les variables pour le squelette du formulaire
 *
 * @param string $objet Type d'objet éditorial (ex: 'article')
 * @param int $id_objet Identifiant de l'objet
 * @return array
 */
function formulaires_reaction_charger_dist($objet = 'article', $id_objet = 0) {
    include_spip('inc/config');
    return [
        'objet'          => $objet,
        'id_objet'       => intval($id_objet),
        '_types_actifs' => function_exists('reactions_types_actifs') ? reactions_types_actifs() : [],
        '_compteurs'     => function_exists('reactions_compter') ? reactions_compter($objet, $id_objet) : [],
        '_mes_reactions' => function_exists('reactions_mes_reactions') ? reactions_mes_reactions($objet, $id_objet) : [],
    ];
}

/**
 * Vérification : contrôle que le type de réaction soumis est valide
 *
 * @param string $objet
 * @param int $id_objet
 * @return array Tableau d'erreurs (vide si OK)
 */
function formulaires_reaction_verifier_dist($objet = 'article', $id_objet = 0) {
	$erreurs = [];

	$type_reaction = _request('type_reaction');
	$types_actifs = reactions_types_actifs();

	if (!$type_reaction || !isset($types_actifs[$type_reaction])) {
		$erreurs['message_erreur'] = _T('reactions:erreur_type_invalide');
	}

	include_spip('inc/autoriser');
	if (!autoriser('reactionposer', $objet, $id_objet)) {
		$erreurs['message_erreur'] = _T('reactions:erreur_non_autorise');
	}

	return $erreurs;
}

/**
 * Traitement : pose ou retire la réaction (logique toggle), puis
 * retourne les compteurs à jour pour mise à jour ajax du widget.
 *
 * @param string $objet
 * @param int $id_objet
 * @return array
 */
function formulaires_reaction_traiter_dist($objet = 'article', $id_objet = 0) {
	$type_reaction = _request('type_reaction');

	$deja_pose = reactions_a_deja_reagi($objet, $id_objet, $type_reaction);

	if ($deja_pose) {
		reactions_retirer($objet, $id_objet, $type_reaction);
		$action = 'retire';
	} else {
		$resultat = reactions_poser($objet, $id_objet, $type_reaction);
		if (!$resultat['ok']) {
			return [
				'message_erreur' => _T('reactions:erreur_' . $resultat['raison']) ?: _T('reactions:erreur_generique'),
			];
		}
		$action = 'pose';
	}

	return [
		'message_ok'     => '',
		'editable'       => true,
		'_action'        => $action,
		'_compteurs'     => reactions_compter($objet, $id_objet),
		'_mes_reactions' => reactions_mes_reactions($objet, $id_objet),
	];
}



