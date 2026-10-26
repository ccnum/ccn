<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pop-up de choix des classes d'un prof inscrit au CCN, incluse
 * dans squelettes/layout.html.
 *
 * L'ENT renvoie toutes les classes d'un prof, y compris d'anciennes années
 * (ENTClassesGroupes n'a pas d'année) : la connexion SSO ne les crée donc plus
 * sous "Travail des classes", elle les mémorise
 * (thematique_cioidc_memoriser_classes_ent()) et le prof coche ici celles
 * inscrites au CCN. Tant qu'il n'a pas validé pour l'année scolaire en cours,
 * la pop-up réapparaît à chaque chargement de page, y compris pour un prof
 * rattaché à ses classes automatiquement avant cette pop-up, et même s'il n'a
 * qu'une classe ENT : il doit voir ce qui sera retenu pour pouvoir le corriger.
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

	// Classes déjà rattachées (choix rouvert en cours d'année, cf action
	// thematique_modifier_classes_ent, ou rattachement automatique) pré-cochées.
	$titres_lies = [];
	if ($ids = thematique_classes_auteur(session_get('id_auteur'))) {
		$titres_lies = array_column(sql_allfetsel('titre', 'spip_rubriques', sql_in('id_rubrique', $ids)), 'titre');
	}

	return [
		'classes' => array_keys(array_filter($classes, fn ($classe) => in_array($classe['titre'], $titres_lies, true))),
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
	$id_auteur = intval(session_get('id_auteur'));
	$cochees = array_intersect_key(
		thematique_classes_ent_a_choisir($id_auteur),
		array_flip(formulaires_choisir_classes_ent_cochees())
	);

	if (!thematique_cioidc_valider_classes_ent($id_auteur, $cochees, (string) thematique_annee_scolaire())) {
		return ['message_erreur' => _T('thematique:choisir_classes_ent_erreur_annee')];
	}

	// Réécrit le fichier de session : rôle (prof, rattaché à une classe) et avatar
	// (emoji de la classe) recalculés par thematique_preparer_fichier_session().
	session_set('classes_ent_choisies', (string) thematique_annee_scolaire());

	return ['redirect' => self()];
}
