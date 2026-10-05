<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Grille du plan d'associations : participant de chaque chapitre de rotation de chaque
 * histoire (#521, #523, #525). Seuls les chapitres non écrits sont modifiables ; sur un
 * plan validé, les liens auteur ↔ chapitre suivent (#524).
 */

function formulaires_fictions_affectations_charger_dist($annee) {
	if (!autoriser('fictionsassociations')) {
		return false;
	}
	include_spip('inc/fictions_plan');
	$donnees = fictions_associations_donnees(intval($annee));
	if (!$donnees['plan']) {
		return false;
	}
	return [
		'affectation' => [],
		'_plan' => $donnees['plan'],
		'_participants' => array_filter($donnees['participants'], fn($p) => !empty($p['actif'])),
		'_chapitres' => $donnees['chapitres'],
	];
}

function formulaires_fictions_affectations_traiter_dist($annee) {
	// charger() n'est pas rappelé au POST : on revérifie le droit ici.
	if (!autoriser('fictionsassociations')) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}
	include_spip('inc/fictions_plan');
	$annee = intval($annee);
	$plan = fictions_plan($annee);
	$nb = 0;
	foreach ((array) _request('affectation') as $id_rubrique => $chapitres) {
		foreach ((array) $chapitres as $chapitre => $id_participant) {
			$id_rubrique = intval($id_rubrique);
			$chapitre = intval($chapitre);
			$id_participant = intval($id_participant);
			if ((int) ($plan[$id_rubrique][$chapitre] ?? 0) === $id_participant) {
				continue;
			}
			$code = fictions_plan_modifier_affectation($annee, $id_rubrique, $chapitre, $id_participant);
			if ($code) {
				$cle = 'fictions:erreur_' . $code;
				return ['message_erreur' => _T($cle)];
			}
			$nb++;
		}
	}
	// Retour sur la page : les anomalies et l'état du plan en dépendent
	return [
		'message_ok' => _T('fictions:ok_affectations'),
		'redirect' => generer_url_ecrire('fictions_associations', 'annee=' . $annee . '&ok=affectations'),
	];
}
