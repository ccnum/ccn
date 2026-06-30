<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_toggle_favori_ccn_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	header('Content-Type: application/json');

	$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
	if (!$id_auteur) {
		echo json_encode(['ok' => false, 'raison' => 'non_connecte']);
		exit;
	}

	// arg format : "objet-id_objet-categorie"
	$parts = explode('-', $arg, 3);
	if (count($parts) !== 3) {
		echo json_encode(['ok' => false, 'raison' => 'arg_invalide']);
		exit;
	}

	[$objet, $id_objet, $categorie] = $parts;
	$id_objet = intval($id_objet);

	if (!$id_objet || !preg_match('/^\w+$/', $objet)) {
		echo json_encode(['ok' => false, 'raison' => 'arg_invalide']);
		exit;
	}

	include_spip('mesfavoris_ccn_fonctions');
	$categories_valides = array_keys(favoris_ccn_categories());
	if (!in_array($categorie, $categories_valides, true)) {
		echo json_encode(['ok' => false, 'raison' => 'categorie_invalide']);
		exit;
	}

	include_spip('inc/mesfavoris');
	$favori = mesfavoris_trouver($id_objet, $objet, $id_auteur);

	if ($favori && $favori['categorie'] === $categorie) {
		// Même catégorie → toggle off
		mesfavoris_supprimer(['id_favori' => intval($favori['id_favori'])]);
		$action            = 'retire';
		$nouvelle_categorie = '';
	} elseif ($favori) {
		// Catégorie différente → on change
		mesfavoris_categoriser($id_objet, $objet, $id_auteur, $categorie);
		$action            = 'change';
		$nouvelle_categorie = $categorie;
	} else {
		// Pas encore de favori → on ajoute
		mesfavoris_ajouter($id_objet, $objet, $id_auteur, $categorie);
		$action            = 'pose';
		$nouvelle_categorie = $categorie;
	}

	echo json_encode([
		'ok'        => true,
		'action'    => $action,
		'categorie' => $nouvelle_categorie,
		'compteurs' => favoris_ccn_compter($objet, $id_objet),
	]);
	exit;
}
