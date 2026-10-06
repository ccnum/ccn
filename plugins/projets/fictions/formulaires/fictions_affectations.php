<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Grille du plan d'associations : participant de chaque chapitre de rotation de chaque
 * histoire (#521, #523, #525). Seuls les chapitres non écrits sont modifiables ; sur un
 * plan validé, les liens auteur ↔ chapitre suivent (#524).
 */

/**
 * Grille du plan prête pour le squelette : chaque cellule porte le nom de son champ
 * (affectation[id_rubrique][chapitre]) et son libellé, partagés avec la déclaration des
 * saisies. Calculée une fois par requête (charger et verifier l'appellent tous deux).
 */
function fictions_affectations_grille(int $annee): array {
	static $cache = [];
	if (!isset($cache[$annee])) {
		include_spip('inc/fictions_plan');
		$donnees = fictions_associations_donnees($annee);
		$participants = [];
		foreach ($donnees['participants'] as $participant) {
			if (!empty($participant['actif'])) {
				// Échappé ici : le squelette de la saisie affiche le libellé tel quel
				$participants[(int) $participant['id']] = spip_htmlspecialchars($participant['nom']);
			}
		}
		$plan = $donnees['plan'];
		foreach ($plan as &$ligne) {
			foreach ($ligne['cellules'] as &$cellule) {
				$cellule['nom'] = 'affectation[' . $ligne['id_rubrique'] . '][' . $cellule['chapitre'] . ']';
				$cellule['id'] = 'fictions_affectation_' . $ligne['id_rubrique'] . '_' . $cellule['chapitre'];
				$cellule['label'] = _T('fictions:associations_chapitre', ['chapitre' => $cellule['chapitre']]);
			}
		}
		$cache[$annee] = ['plan' => $plan, 'participants' => $participants, 'chapitres' => $donnees['chapitres']];
	}
	return $cache[$annee];
}

/**
 * Champs du formulaire (plugin saisies) : une liste par cellule de la grille. Saisies
 * contrôle, avant traiter(), que chaque participant posté est un participant actif ;
 * les chapitres déjà écrits sont désactivés (rien n'est posté pour eux).
 */
function formulaires_fictions_affectations_saisies_dist($annee) {
	$grille = fictions_affectations_grille(intval($annee));
	$saisies = [];
	foreach ($grille['plan'] as $ligne) {
		foreach ($ligne['cellules'] as $cellule) {
			$saisies[] = [
				'saisie' => 'selection',
				'options' => [
					'nom' => $cellule['nom'],
					'label' => $cellule['label'],
					'data' => $grille['participants'],
					'cacher_option_intro' => 'oui',
					'disable' => $cellule['ecrit'] ? 'oui' : '',
				],
			];
		}
	}
	$saisies['options'] = ['verifier_valeurs_acceptables' => true];
	return $saisies;
}

function formulaires_fictions_affectations_charger_dist($annee) {
	if (!autoriser('fictionsassociations')) {
		return false;
	}
	$grille = fictions_affectations_grille(intval($annee));
	if (!$grille['plan']) {
		return false;
	}
	return [
		'affectation' => [],
		'_plan' => $grille['plan'],
		'_participants' => $grille['participants'],
		'_chapitres' => $grille['chapitres'],
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
