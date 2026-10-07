<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pop-up de choix des classes d'un prof inscrit au CCN (issue #541), incluse
 * dans squelettes/layout.html.
 *
 * L'ENT renvoie toutes les classes d'un prof, y compris d'anciennes années
 * (ENTClassesGroupes n'a pas d'année) : la connexion SSO ne les crée donc plus
 * sous "Travail des classes", elle les mémorise
 * (thematique_cioidc_memoriser_classes_ent()) et le prof coche ici celles
 * inscrites au CCN. Tant qu'il n'a pas validé pour l'année scolaire en cours,
 * la pop-up réapparaît à chaque chargement de page, y compris pour un prof
 * rattaché à ses classes automatiquement avant cette pop-up.
 *
 * @package SPIP\Thematique\Formulaires
 */

// verifier/traiter s'exécutent avant tout squelette : thematique_fonctions.php
// n'y est pas forcément chargé.
include_spip('thematique_fonctions');

function formulaires_choisir_classes_ent_charger_dist() {
	include_spip('inc/session');
	$classes = thematique_classes_ent_a_choisir(session_get('id_auteur'));
	if (!$classes) {
		return false;
	}

	return [
		'classes' => [],
		'_classes' => array_column($classes, 'titre'),
	];
}

/**
 * Indices (dans thematique_classes_ent_a_choisir()) des classes cochées.
 *
 * @return int[]
 */
function formulaires_choisir_classes_ent_cochees() {
	return array_values(array_unique(array_map('intval', (array) _request('classes'))));
}

function formulaires_choisir_classes_ent_verifier_dist() {
	include_spip('inc/session');
	$classes = thematique_classes_ent_a_choisir(session_get('id_auteur'));
	$cochees = formulaires_choisir_classes_ent_cochees();

	if (!$classes) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}
	if (!$cochees) {
		return ['classes' => _T('thematique:choisir_classes_ent_erreur_aucune')];
	}
	foreach ($cochees as $indice) {
		if (!isset($classes[$indice])) {
			return ['classes' => _T('thematique:choisir_classes_ent_erreur_aucune')];
		}
	}

	return [];
}

function formulaires_choisir_classes_ent_traiter_dist() {
	include_spip('inc/session');
	include_spip('inc/thematique_cioidc');
	include_spip('action/editer_liens');
	$id_auteur = intval(session_get('id_auteur'));
	$classes = thematique_classes_ent_a_choisir($id_auteur);
	$annee = (string) thematique_annee_scolaire();

	[, $id_travail_classes] = thematique_cioidc_rubriques_annee($annee);
	if (!$id_travail_classes) {
		return ['message_erreur' => _T('thematique:choisir_classes_ent_erreur_annee')];
	}

	// Même rubrique que celle que créait la connexion SSO avant #541 (titre avec
	// établissement, renommage d'une rubrique au titre ancien) : on retrouve celle
	// d'un collègue de la même classe plutôt que d'en créer une seconde.
	$choisies = [];
	foreach (formulaires_choisir_classes_ent_cochees() as $indice) {
		$groupe = (object) [
			'group_name' => $classes[$indice]['group_name'],
			'group_structure_id' => $classes[$indice]['group_structure_id'] ?? '',
		];
		if ($id_classe = thematique_cioidc_rubrique_classe_prof($groupe, $id_travail_classes)) {
			$choisies[] = intval($id_classe);
		}
	}
	if (!$choisies) {
		return ['message_erreur' => _T('thematique:choisir_classes_ent_erreur_annee')];
	}

	// Classes de l'année non cochées : liées automatiquement à la connexion avant #541
	// (dont d'anciennes classes du prof), retirées pour ne garder que son choix.
	foreach (thematique_classes_auteur($id_auteur) as $id_classe) {
		if (!in_array($id_classe, $choisies, true)) {
			objet_dissocier(['id_auteur' => $id_auteur], ['rubrique' => $id_classe]);
		}
	}
	foreach ($choisies as $id_classe) {
		objet_associer(['id_auteur' => $id_auteur], ['rubrique' => $id_classe]);
	}
	sql_updateq('spip_auteurs', ['classes_ent_choisies' => $annee], 'id_auteur=' . $id_auteur);
	spip_log("choix des classes auteur #$id_auteur année $annee : " . implode(',', $choisies), 'cioidc');

	// Réécrit le fichier de session : rôle (prof, rattaché à une classe) et avatar
	// (emoji de la classe) recalculés par thematique_preparer_fichier_session().
	session_set('classes_ent_choisies', $annee);

	return ['redirect' => self()];
}
