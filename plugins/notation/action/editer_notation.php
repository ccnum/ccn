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

include_spip('base/abstract_sql');

/**
 * Inserer une nouvelle note
 *
 * @param string $objet
 * @param int $id_objet
 * @return int|bool
 */
function notation_inserer($objet, $id_objet) {
	$champs = [
		'objet' => $objet,
		'id_objet' => $id_objet,
		'id_auteur' => 0,
		'ip' => '',
		'hash' => '',
		'cookie' => '',
		'note' => 0,
	];

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_notations',
			],
			'data' => $champs
		]
	);

	$id_notation = sql_insertq('spip_notations', $champs);

	pipeline(
		'post_insertion',
		[
			'args' => [
				'table' => 'spip_notations',
				'id_objet' => $id_notation
			],
			'data' => $champs
		]
	);

	return $id_notation;
}

/**
 * Modifier une note existante
 *
 * @param int $id_notation
 * @param array|null $set
 * @return bool|string
 */
function notation_modifier($id_notation, $set = null) {
	include_spip('inc/modifier');
	include_spip('inc/filtres');
	$c = collecter_requests(
		// white list
		['id_auteur','ip','hash','cookie','note'],
		// black list : on ne peut pas changer sur quoi porte une note
		['objet','id_objet'],
		// donnees eventuellement fournies
		$set
	);

	// recuperer l'objet sur lequel porte la notation
	$t = sql_fetsel('objet,id_objet', 'spip_notations', 'id_notation=' . intval($id_notation));
	if (
		$err = objet_modifier_champs(
			'notation',
			$id_notation,
			[
			'data' => $set,
			],
			$c
		)
	) {
		return $err;
	}

	// mettre a jour les stats
	//
	// cette action est presque devenue inutile
	// comme la table spip_notations_objets
	// (qui devrait s'appeler spip_notations_stats plutot !)
	// car le critere {notation} permet d'obtenir ces resultats
	// totalements a jour...
	// Cependant, quelques cas tres particuliers de statistiques
	// font que je le laisse encore, comme calculer l'objet le mieux note :
	// 	<BOUCLE_notes_pond(NOTATIONS_OBJETS){0,10}{!par note_ponderee}>
	// qu'il n'est pas possible de traduire dans une boucle NOTATION facilement.
	notation_recalculer_total($t['objet'], $t['id_objet']);

	// invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='notation/" . $t['objet'] . '/' . $t['id_objet'] . "'");

	return $err;
}

/**
 * Supprimer une note existante
 *
 * @param int $id_notation
 * @return bool
 */
function notation_supprimer($id_notation) {
	// recuperer l'objet sur lequel porte la notation
	$t = sql_fetsel('objet,id_objet', 'spip_notations', 'id_notation=' . intval($id_notation));


	// Envoyer aux plugins
	$champs = pipeline(
		'pre_edition',
		[
			'args' => [
				'table' => 'spip_notations',
				'id_objet' => $id_notation,
				'action' => 'supprimer',
			],
			'data' => []
		]
	);

	sql_delete('spip_notations', 'id_notation=' . sql_quote($id_notation));

	// Envoyer aux plugins
	$champs = pipeline(
		'post_edition',
		[
			'args' => [
				'table' => 'spip_notations',
				'id_objet' => $id_notation,
				'action' => 'supprimer',
			],
			'data' => []
		]
	);

	// mettre a jour les stats
	//
	// cette action est presque devenue inutile
	// comme la table spip_notations_objets
	// (qui devrait s'appeler spip_notations_stats plutot !)
	// car le critere {notation} permet d'obtenir ces resultats
	// totalements a jour...
	// Cependant, quelques cas tres particuliers de statistiques
	// font que je le laisse encore, comme calculer l'objet le mieux note :
	// 	<BOUCLE_notes_pond(NOTATIONS_OBJETS){0,10}{!par note_ponderee}>
	// qu'il n'est pas possible de traduire dans une boucle NOTATION facilement.
	notation_recalculer_total($t['objet'], $t['id_objet']);

	// invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='notation/" . $t['objet'] . '/' . $t['id_objet'] . "'");

	return true;
}


/**
 * je me demande vraiment si tout cela est utile...
 * vu que tout peut etre calcule en requete depuis spip_notations
 * a peu de choses pres (!)
 *
 * @use notation_recalculer_notes_moyennes()
 *
 * @param string $objet
 * @param int $id_objet
 * @return void
 */
function notation_recalculer_total($objet, $id_objet) {

	$objet = objet_type($objet);

	include_spip('inc/notation');
	$ponderation = notation_get_ponderation();

	$invalide = notation_recalculer_notes_moyennes($ponderation, $objet, $id_objet);

	if ($invalide) {
		// si on utilise indexer, on dit de reindexer l'objet (c'est pas possible de modifier juste une propriété de l'objet?)
		$info_plugin = chercher_filtre('info_plugin');
		$indexer = $info_plugin('indexer', 'est_actif');

		if ($indexer) {
			include_spip('indexer_pipelines');
			indexer_redindex_objet($objet, $id_objet);
		}
	}
}


// pour compat
function insert_notation($objet, $id_objet) {
	return notation_inserer($objet, $id_objet);
}
function modifier_notation($id_notation, $c = []) {
	return notation_modifier($id_notation, $c);
}
function supprimer_notation($id_notation) {
	return notation_supprimer($id_notation);
}
