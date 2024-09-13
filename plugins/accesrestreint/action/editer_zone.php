<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * editer une zone (action apres creation/modif de zone)
 *
 * @param int $arg
 * @return array
 */
function action_editer_zone_dist($arg = null) {

	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// Envoi depuis le formulaire d'edition d'une zone
	if (!$id_zone = intval($arg)) {
		$id_zone = zone_inserer();
	}

	if (!$id_zone) {
		return [0, '']; // erreur
	}

	if (_request('droits_admin')) {
		zone_lier($id_zone, 'auteur', $GLOBALS['visiteur_session']['id_auteur']);
	}

	$err = zone_modifier($id_zone);

	return [$id_zone,$err];
}


/**
 * Inserer une zone en base
 *
 * @return int
 */
function zone_inserer() {

	include_spip('inc/autoriser');
	if (!autoriser('creer', 'zone')) {
		return false;
	}

	$champs = [
		'publique' => 'non',
		'privee' => 'non',
	];

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_zones',
			],
			'data' => $champs
		]
	);
	$id_zone = sql_insertq('spip_zones', $champs);
	pipeline(
		'post_insertion',
		[
			'args' => [
				'table' => 'spip_zones',
				'id_objet' => $id_zone
			],
			'data' => $champs
		]
	);
	return $id_zone;
}


/**
 * Modifier une zone en base
 * $c est un contenu (par defaut on prend le contenu via _request())
 *
 * @param int $id_zone
 * @param array $set
 * @return string|bool
 */
function zone_modifier($id_zone, $set = null) {
	include_spip('inc/modifier');

	// Cherchons les vrais champs déclarés
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table('spip_zones');
	if (isset($desc['champs_editables']) and is_array($desc['champs_editables'])) {
		$white = $desc['champs_editables'];
	} else {
		$white = ['titre', 'descriptif','publique', 'privee'];
	}

	$c = collecter_requests(
		// white list
		$white,
		// black list
		[],
		// donnees eventuellement fournies
		$set
	);

	// Si la zone est publiee, invalider les caches et demander sa reindexation
	$invalideur = $indexation = '';
	$t = sql_getfetsel('publique', 'spip_zones', "id_zone=$id_zone");
	if ($t == 'oui') {
		$invalideur = "id='zone/$id_zone'";
		$indexation = true;
	}

	if (
		$err = objet_modifier_champs(
			'zone',
			$id_zone,
			[
			'data' => $set,
			'nonvide' => ['titre' => _T('info_sans_titre')],
			'invalideur' => $invalideur,
			'indexation' => $indexation
			],
			$c
		)
	) {
		return $err;
	}

	zone_lier($id_zone, 'rubrique', _request('rubriques'), 'set');
	return $err;
}


/**
 * Mettre à jour les liens objets/zones.
 *
 * @param int|array|string $zones
 *     Identifiant ou liste d'identifiants zones à affecter.
 *     Si zones vaut '', associe toutes les zones a(aux) objets(s).
 * @param string $type
 *     Type d'objet (rubrique, auteur).
 * @param int|array $ids
 *     Identifiant ou liste d'identifiants de l'objet
 * @param string $operation
 *     Action à effectuer parmi `add`, `set` ou `del` pour ajouter, affecter uniquement,
 *     ou supprimer les objets listés dans ids.
 */
function zone_lier($zones, $type, $ids, $operation = 'add') {
	include_spip('inc/autoriser');
	include_spip('action/editer_liens');
	if (!$zones) {
		$zones = '*';
	}
	if (!$ids) {
		$ids = [];
	} elseif (!is_array($ids)) {
		$ids = [$ids];
	}

	if ($operation == 'del') {
		// on supprime les ids listes
		objet_dissocier(['zone' => $zones], [$type => $ids]);
	} else {
		// si c'est une affectation exhaustive, supprimer les existants qui ne sont pas dans ids
		// si c'est un ajout, ne rien effacer
		if ($operation == 'set') {
			objet_dissocier(['zone' => $zones], [$type => ['NOT', $ids]]);
			// bugfix temporaire : objet_associer ne gere pas id=0
			if (!in_array(0, $ids) and $type == 'rubrique') {
				sql_delete('spip_zones_liens', 'id_zone=' . intval($zones) . ' AND id_objet=0 AND objet=' . sql_quote($type));
			}
		}
		foreach ($ids as $id) {
			if (autoriser('affecterzones', $type, $id, null, ['id_zone' => $zones])) {
				// bugfix temporaire : objet_associer ne gere pas id=0
				if ($id == 0 and $type == 'rubrique') {
					sql_insertq('spip_zones_liens', ['id_zone' => $zones, 'id_objet' => $id, 'objet' => $type]);
				} else {
					objet_associer(['zone' => $zones], [$type => $id]);
				}
			}
		}
	}
}



/**
 * Supprimer une zone
 *
 * @param int $id_zone
 * @return int
 */
function zone_supprimer($id_zone) {
	include_spip('action/editer_liens');
	objet_dissocier(['zone' => $id_zone], ['*' => '*']);

	// puis la zone
	sql_delete('spip_zones', 'id_zone=' . intval($id_zone));

	$id_zone = 0;
	return $id_zone;
}
