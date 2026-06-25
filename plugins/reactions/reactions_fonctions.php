<?php
/**
 * Fonctions métier du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/session');
include_spip('base/abstract_sql');




function reactions_types_actifs() {
    include_spip('inc/config');
    $types = lire_config('reactions/types_actifs');
    if (!is_array($types) || empty($types)) {
        include_spip('formulaires/configurer_reactions');
        if (function_exists('reactions_catalogue_smileys')) {
            return ['amour', 'pouce', 'feu'];
        }
        return ['coeur', 'pouce', 'feu'];
    }
    return $types;
}

/**
 * Les visiteurs anonymes sont-ils autorisés à réagir ?
 *
 * @return bool
 */
function reactions_anonymes_autorises() {
	return ($GLOBALS['meta']['reactions/anonymes_autorises'] ?? 'oui') === 'oui';
}

/**
 * Un même visiteur peut-il poser plusieurs types de réactions
 * différents sur le même objet ?
 *
 * @return bool
 */
function reactions_multi_reactions_autorisees() {
	return ($GLOBALS['meta']['reactions/multi_reactions'] ?? 'oui') === 'oui';
}

/**
 * Calcule l'identité du visiteur courant pour la table spip_reactions
 *
 * - Si connecté : id_auteur renseigné, session_id vide.
 * - Si anonyme : id_auteur = 0, session_id = identifiant stable stocké
 *   en cookie dédié (pas le PHPSESSID natif, trop volatile et déjà
 *   utilisé à d'autres fins par SPIP).
 *
 * @return array{id_auteur:int, session_id:string}
 */
function reactions_identite_visiteur() {
	$id_auteur = (int) ($GLOBALS['visiteur_session']['id_auteur'] ?? 0);

	if ($id_auteur) {
		return ['id_auteur' => $id_auteur, 'session_id' => ''];
	}

	return ['id_auteur' => 0, 'session_id' => reactions_session_anonyme()];
}

/**
 * Génère (ou récupère) un identifiant anonyme stable, posé en cookie
 * de longue durée, pour distinguer les visiteurs non connectés.
 *
 * @return string
 */
function reactions_session_anonyme() {
	$nom_cookie = 'spip_reactions_anonyme';

	if (!empty($_COOKIE[$nom_cookie])) {
		return preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[$nom_cookie]);
	}

	$id = md5(uniqid('', true) . random_int(0, mt_getrandmax()));

	if (!headers_sent()) {
		setcookie(
			$nom_cookie,
			$id,
			[
				'expires'  => time() + (3600 * 24 * 365),
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	return $id;
}

/**
 * Vérifie si le visiteur courant a déjà posé ce type de réaction
 * sur cet objet.
 *
 * C'est ici, en PHP, que se fait la vérification d'unicité que le
 * schéma SQL ne peut pas garantir seul (id_auteur = 0 pour tous les
 * anonymes).
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $type_reaction
 * @return bool
 */
function reactions_a_deja_reagi($objet, $id_objet, $type_reaction) {
	$identite = reactions_identite_visiteur();

	$where = [
		'objet = ' . sql_quote($objet),
		'id_objet = ' . intval($id_objet),
		'type_reaction = ' . sql_quote($type_reaction),
	];

	if ($identite['id_auteur']) {
		$where[] = 'id_auteur = ' . $identite['id_auteur'];
	} else {
		$where[] = 'id_auteur = 0';
		$where[] = 'session_id = ' . sql_quote($identite['session_id']);
	}

	$res = sql_getfetsel('id_reaction', 'spip_reactions', $where);
	return !empty($res);
}

/**
 * Pose une réaction, après vérification des règles métier
 * (anonymes autorisés ?, multi-réactions autorisées ?, doublon ?).
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $type_reaction
 * @return array{ok:bool, raison:string}
 */
function reactions_poser($objet, $id_objet, $type_reaction) {
	$types_actifs = reactions_types_actifs();
	if (!in_array($type_reaction, $types_actifs, true)) {
		return ['ok' => false, 'raison' => 'type_inconnu'];
	}

	$identite = reactions_identite_visiteur();

	if (!$identite['id_auteur'] && !reactions_anonymes_autorises()) {
		return ['ok' => false, 'raison' => 'anonymes_interdits'];
	}

	if (reactions_a_deja_reagi($objet, $id_objet, $type_reaction)) {
		return ['ok' => false, 'raison' => 'deja_pose'];
	}

	if (!reactions_multi_reactions_autorisees()) {
		// Une seule réaction (tout type confondu) par visiteur et par objet :
		// on retire l'éventuelle réaction précédente avant de poser la nouvelle.
		reactions_retirer_toutes($objet, $id_objet, $identite);
	}

	sql_insertq('spip_reactions', [
		'objet'         => $objet,
		'id_objet'      => $id_objet,
		'id_auteur'     => $identite['id_auteur'],
		'session_id'    => $identite['session_id'],
		'type_reaction' => $type_reaction,
		'date_reaction' => date('Y-m-d H:i:s'),
	]);

	return ['ok' => true, 'raison' => ''];
}

/**
 * Retire une réaction précise posée par le visiteur courant
 * (utilisé pour le "toggle" : recliquer sur le même smiley l'enlève).
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $type_reaction
 * @return bool
 */
function reactions_retirer($objet, $id_objet, $type_reaction) {
	$identite = reactions_identite_visiteur();

	$where = [
		'objet = ' . sql_quote($objet),
		'id_objet = ' . intval($id_objet),
		'type_reaction = ' . sql_quote($type_reaction),
	];

	if ($identite['id_auteur']) {
		$where[] = 'id_auteur = ' . $identite['id_auteur'];
	} else {
		$where[] = 'id_auteur = 0';
		$where[] = 'session_id = ' . sql_quote($identite['session_id']);
	}

	return (bool) sql_delete('spip_reactions', $where);
}

/**
 * Retire toutes les réactions du visiteur courant sur un objet donné
 * (utilisé en mode "une seule réaction par visiteur").
 *
 * @param string $objet
 * @param int $id_objet
 * @param array $identite
 * @return bool
 */
function reactions_retirer_toutes($objet, $id_objet, $identite) {
	$where = [
		'objet = ' . sql_quote($objet),
		'id_objet = ' . intval($id_objet),
	];

	if ($identite['id_auteur']) {
		$where[] = 'id_auteur = ' . $identite['id_auteur'];
	} else {
		$where[] = 'id_auteur = 0';
		$where[] = 'session_id = ' . sql_quote($identite['session_id']);
	}

	return (bool) sql_delete('spip_reactions', $where);
}

function reactions_compter($objet, $id_objet) {
    include_spip('inc/config');
    include_spip('reactions_fonctions');

    $types_actifs = reactions_types_actifs();
    $compteurs = [];
    foreach ($types_actifs as $type) {
        if (!empty($type)) {
            $compteurs[(string)$type] = 0; 
        }
    }
	$scores = sql_allfetsel(
        'type_reaction, COUNT(*) as total',
        'spip_reactions',
        'objet = ' . sql_quote($objet) . ' AND id_objet = ' . intval($id_objet),
        'type_reaction' // 🚀 Le GROUP BY est ici !
    );

    if (is_array($scores) && !empty($scores)) {
        foreach ($scores as $row) {
            $type = $row['type_reaction'];
            // On vérifie si ce type fait partie de nos compteurs initialisés
            if (array_key_exists($type, $compteurs)) {
                $compteurs[$type] = intval($row['total']);
            }
        }
    }

    return $compteurs;
}

/**
 * Retourne la liste des types de réactions déjà posées par le
 * visiteur courant sur un objet donné (pour afficher les smileys
 * "actifs" côté template).
 *
 * @param string $objet
 * @param int $id_objet
 * @return array Liste de types, ex: ['coeur', 'feu']
 */
function reactions_mes_reactions($objet, $id_objet) {
	$identite = reactions_identite_visiteur();

	$where = [
		'objet = ' . sql_quote($objet),
		'id_objet = ' . intval($id_objet),
	];

	if ($identite['id_auteur']) {
		$where[] = 'id_auteur = ' . $identite['id_auteur'];
	} else {
		if (!$identite['session_id']) {
			return [];
		}
		$where[] = 'id_auteur = 0';
		$where[] = 'session_id = ' . sql_quote($identite['session_id']);
	}

	$res = sql_allfetsel('type_reaction', 'spip_reactions', $where);
	return $res ? array_column($res, 'type_reaction') : [];
}

/**
 * Filtre de squelette : retourne ' reaction_active' si le type donné
 * fait partie des réactions déjà posées par le visiteur, sinon ''.
 *
 * Usage : #REACTION_CLASSE_ACTIVE{#CLE, #ENV**{_mes_reactions}}
 *
 * @param string $type
 * @param array $mes_reactions
 * @return string
 */
function reaction_classe_active($type, $mes_reactions) {
	$mes_reactions = is_array($mes_reactions) ? $mes_reactions : [];
	return in_array($type, $mes_reactions, true) ? ' reaction_active' : '';
}

/**
 * Filtre de squelette : retourne le compteur d'un type de réaction
 * donné, ou 0 si absent.
 *
 * Usage : #REACTION_COMPTEUR{#CLE, #ENV**{_compteurs}}
 *
 * @param string $type
 * @param array $compteurs
 * @return int
 */
function reaction_compteur($type, $compteurs) {
	$compteurs = is_array($compteurs) ? $compteurs : [];
	return (int) ($compteurs[$type] ?? 0);
}

/**
 * Filtre SPIP pour récupérer un encodage précis d'un smiley depuis son nom (sa clé)
 * Utilisation dans le squelette : [(#VALEUR|reactions_recuperer_smiley{html_encoding})]
 */
/**
 * Filtre SPIP : retourne le label français d'une réaction pour aria-label.
 *
 * @param string $cle
 * @return string
 */
function reaction_label_fr($cle) {
    $labels = [
        'coeur'     => 'Cœur',
        'amour'     => 'Amour',
        'pouce'     => 'Pouce en l\'air',
        'pouce_bas' => 'Pouce en bas',
        'feu'       => 'Feu',
        'rire'      => 'Rire',
        'triste'    => 'Triste',
        'etonne'    => 'Étonné',
        'colere'    => 'Colère',
        'clap'      => 'Applaudissements',
        'fete'      => 'Fête',
    ];
    return $labels[$cle] ?? $cle;
}

/**
 * Filtre SPIP : retourne 'true' ou 'false' pour aria-pressed.
 *
 * @param string $type
 * @param array $mes_reactions
 * @return string
 */
function reaction_aria_pressed($type, $mes_reactions) {
    $mes_reactions = is_array($mes_reactions) ? $mes_reactions : [];
    return in_array($type, $mes_reactions, true) ? 'true' : 'false';
}

function reactions_recuperer_smiley($cle, $type_encodage = 'html_encoding') {
    include_spip('formulaires/configurer_reactions');
    
    if (function_exists('reactions_catalogue_smileys')) {
        $catalogue = reactions_catalogue_smileys();
        if (isset($catalogue[$cle][$type_encodage])) {
            return $catalogue[$cle][$type_encodage];
        }
    }
    return '';
}