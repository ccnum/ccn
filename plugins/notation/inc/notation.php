<?php

/**
 * Plugin Notation v3
 * par JEM (jean-marc.viglino@ign.fr) / b_b / Matthieu Marcillaud
 *
 * Copyright (c) depuis 2008
 * Logiciel libre distribue sous licence GNU/GPL.
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

/**
 * Retourne la configuration de la ponderation (defaut : 30)
 *
 * @return int $ponderation
 *        Valeur de ponderation
 */
function notation_get_ponderation() {
	static $ponderation = '';
	if (!$ponderation) {
		include_spip('inc/config'); // lire_config
		$ponderation = lire_config('notation/ponderation', 30);
		$ponderation = intval($ponderation);
		if ($ponderation < 1) {
			$ponderation = 1;
		}
	}
	return $ponderation;
}


/**
 * Nombre d'etoile a afficher en fonction de la configuration
 * du plugin. Varie de 1 a 10. Defaut 5.
 *
 * @return int $nb
 *        Nombre d'etoiles a afficher
 */
function notation_get_nb_notes() {
	static $nb = '';
	if (!$nb) {
		include_spip('inc/config'); // lire_config
		$nb = intval(lire_config('notation/nombre', 5));
		if ($nb < 1) {
			$nb = 5;
		}
		if ($nb > 10) {
			$nb = 10;
		}
	}
	return $nb;
}

/**
 * Calcule de la note ponderee
 * utilise uniquement pour l'affichage dans la page de configuration
 * (vrai calcul en SQL dans action/editer_notation)
 *
 * @param float $note
 *        Note moyenne obtenue
 * @param int $nb
 *        Nombre de votes
 * @return int $note_ponderee
 *        Note ponderee en fonction de la configuration du plugin
 */
function notation_ponderee($note, $nb, $ponderation, $note_max = 5) {
	$ponderation = max(intval($ponderation), 1);
	$note_moyenne = round($note_max / 2, 2);
	$note_ponderee = $note_moyenne + round(($note - $note_moyenne) * (1 - exp(-5 * $nb / $ponderation)), 2);
	return $note_ponderee;
}

/**
 * Fonction pour identifier le visiteur qui veut voter/a vote
 * on gere les methodes d'identification id_auteur/ip/hash/cookie
 * @param bool $set_cookie
 *   true pour forcer la pose d'un cookie si pas deja existant (au moment de l'enregistrement du vote)
 * @return array
 */
function notation_identifier_visiteur($set_cookie = false) {

	$qui = [
		'id_auteur' => 0,
		'ip' => '',
		'hash' => '',
		'cookie' => '',
		'a_vote' => false,
		'where' => '0=1',
	];

	if (isset($GLOBALS['visiteur_session']['id_auteur'])) {
		$qui['id_auteur'] = $GLOBALS['visiteur_session']['id_auteur'];
	}
	if (isset($GLOBALS['ip']) and $GLOBALS['ip']) {
		$qui['ip'] = $GLOBALS['ip'];
	}

	// Identification du client
	$qui['hash'] = substr(md5(
		$qui['ip']
		. (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
		. (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
		. (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '')
	), 0, 10);

	if (isset($_COOKIE['spip_a_vote']) and $_COOKIE['spip_a_vote']) {
		$qui['cookie'] = $_COOKIE['spip_a_vote'];
	}
	if (
		$qui['cookie']
		// compat anciennes versions
		or session_get('a_vote')
	) {
		$qui['a_vote'] = true;
	}

	if (!$qui['cookie'] and $set_cookie) {
		include_spip('inc/acces');
		$qui['cookie'] = substr(md5(creer_uniqid()), 0, 16);
		include_spip('inc/cookie');
		spip_setcookie('spip_a_vote', $_COOKIE['spip_a_vote'] = $qui['cookie']);
	}

	if ($qui['id_auteur']) {
		$qui['where'] = 'id_auteur=' . intval($qui['id_auteur']);
	} else {
		if (!function_exists('lire_config')) {
			include_spip('inc/config');
		}
		$methode_id = lire_config('notation/methode_id', 'ip');
		if ($methode_id == 'cookie') {
			$qui['where'] = 'cookie=' . sql_quote($qui['cookie'], '', 'text');
		} elseif ($methode_id == 'hash') {
			$qui['where'] = 'hash=' . sql_quote($qui['hash'], '', 'text');
		} else {
			$qui['where'] = 'ip=' . sql_quote($qui['ip'], '', 'text');
		}
	}

	return $qui;
}

/**
 * Retrouver la note d'un objet/id_objet
 * pour un visiteur decrit par $qui (fourni par la fonction notation_identifier_visiteur)
 * @param string $objet
 * @param int $id_objet
 * @param array $qui
 * @return bool|int
 */
function notation_retrouver_note($objet, $id_objet, $qui) {

	if (!$qui or !isset($qui['where'])) {
		return false;
	}

	$where = [
		'objet=' . sql_quote($objet),
		'id_objet=' . sql_quote($id_objet),
		$qui['where'],
	];

	$id_notation = sql_getfetsel('id_notation', 'spip_notations', $where);

	return intval($id_notation);
}

/**
 * Recalculer les notes moyennes en fonction de la ponderation
 *
 * cette fonction est presque devenue inutile
 * comme la table spip_notations_objets
 * (qui devrait s'appeler spip_notations_stats plutot !)
 * car le critere {notation} permet d'obtenir ces resultats
 * totalements a jour...
 * Cependant, quelques cas tres particuliers de statistiques
 * font que je le laisse encore, comme calculer l'objet le mieux note :
 * `<BOUCLE_notes_pond(NOTATIONS_OBJETS){0,10}{!par note_ponderee}>`
 *
 * qu'il n'est pas possible de traduire dans une boucle NOTATION facilement.
 *
 *
 * @param $ponderation
 * @param string $objet
 * @param int $id_objet
 */
function notation_recalculer_notes_moyennes($ponderation, $objet = '', $id_objet = 0) {
	$invalide = false;
	include_spip('inc/invalideur');

	// securite
	$ponderation = floatval($ponderation);
	$ponderation = max($ponderation, 1);

	$note_max = notation_get_nb_notes();
	$note_moy = round($note_max / 2, 2);

	// Mettre a jour les moyennes
	// cf critere {notation}
	$select = [
		'notations.objet',
		'notations.id_objet',
		'COUNT(notations.note) AS nombre_votes',
		'ROUND(AVG(notations.note),2) AS moyenne',
		'ROUND(' . $note_moy . ' + AVG(notations.note - ' . $note_moy . ')*(1-EXP(-5*COUNT(notations.note)/' . $ponderation . ')),2) AS moyenne_ponderee'
	];

	$where = [];
	if ($objet and $id_objet) {
		$where = [
			'objet=' . sql_quote($objet),
			'id_objet=' . sql_quote($id_objet)
		];
	}

	$res = sql_select($select, 'spip_notations AS notations', $where, 'objet, id_objet');

	// on a plus de note pour l'objet concerne ?
	if (!empty($where) and !sql_count($res)) {
		sql_delete('spip_notations_objets', $where);
		$invalide = true;
	}

	while ($row = sql_fetch($res)) {
		$deja = sql_fetsel(
			'note_ponderee',
			'spip_notations_objets',
			['objet=' . sql_quote($row['objet']), 'id_objet=' . sql_quote($row['id_objet'])]
		);

		$set = [
			'note' => $row['moyenne'],
			'note_ponderee' => $row['moyenne_ponderee'],
			'nombre_votes' => $row['nombre_votes'],
		];

		if (empty($deja)) {
			$ancienne_note = 0;
			$set['objet'] = $row['objet'];
			$set['id_objet'] = $row['id_objet'];

			sql_insertq('spip_notations_objets', $set);
		} else {
			$ancienne_note = $deja['note_ponderee'];

			sql_updateq(
				'spip_notations_objets',
				$set,
				[
					'objet=' . sql_quote($row['objet']),
					'id_objet=' . sql_quote($row['id_objet'])
				]
			);
		}

		if (round($row['moyenne_ponderee']) !== round($ancienne_note)) {
			$invalide = true;
		}
	}


	if ($invalide) {
		if ($where) {
			suivre_invalideur("id='notation/$objet/$id_objet'");
		} else {
			suivre_invalideur("id='notation/all'");
		}
	}

	return $invalide;
}
