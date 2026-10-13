<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Mois de déclenchement de la tâche (9 = septembre). */
if (!defined('_THEMATIQUE_RENTREE_MOIS')) {
	define('_THEMATIQUE_RENTREE_MOIS', 9);
}

/**
 * À la rentrée de septembre, sur chaque site CCN :
 * 1. crée la structure de la nouvelle année scolaire si elle n'existe pas
 *    encore (rubrique racine + "Travail des classes"/"Consignes", cf
 *    thematique_assurer_structure_annee()) ;
 * 2. crée (si absents) les deux articles jalons du projet : "Cap sur
 *    l'année" (cap-sur-l-annee) et "La Rencontre" (la-rencontre), en
 *    statut prop, assignés au premier intervenant trouvé sur le projet ;
 * 3. crée (si absente) une fiche d'intervenant dans chaque rubrique de
 *    projet sous "Consignes", identifiée par le mot-clé intervenant.
 *
 * Enregistrée via le pipeline taches_generales_cron (thematique_pipelines.php)
 * plutôt que la balise <genie> de paquet.xml, car le pipeline est réévalué à
 * chaque calcul des tâches de fond (pas besoin que SPIP revérifie le paquet).
 *
 * Désactivable projet par projet via _CCN_PROJET_ACTIVE (mes_options.php,
 * défini depuis la variable d'environnement Docker CCN_PROJET_ACTIVE).
 *
 * @see plugins/projets/thematique/genie/thematique_rentree_poubelle.php
 *
 * @param int $last
 * @return int
 */
function genie_thematique_rentree_annee_dist($last) {
	spip_log('thematique_rentree_annee : tâche déclenchée (last=' . $last . ')', 'thematique');

	if (!thematique_rentree_projet_actif()) {
		spip_log('thematique_rentree_annee : _CCN_PROJET_ACTIVE=false, projet inactif, on ne fait rien', 'thematique');
		return 1;
	}

	if ((int) date('n') !== _THEMATIQUE_RENTREE_MOIS) {
		spip_log(
			'thematique_rentree_annee : hors fenêtre (mois=' . date('n') . ', attendu=' . _THEMATIQUE_RENTREE_MOIS . '), on ne fait rien',
			'thematique'
		);
		return 1;
	}

	$annee = (int) date('Y');

	// On ne s'arrête PAS si l'année est déjà marquée traitée : une ancienne
	// version a pu poser la meta à tort après un échec silencieux. Les
	// créations ci-dessous sont idempotentes, donc on revérifie.
	$derniere_execution = (int) ($GLOBALS['meta']['thematique_rentree_annee_traitee'] ?? 0);
	if ($derniere_execution >= $annee) {
		spip_log(
			"thematique_rentree_annee $annee : déjà marquée traitée (dernière exécution $derniere_execution), on revérifie quand même",
			'thematique'
		);
	}

	spip_log("thematique_rentree_annee $annee : traitement en cours", 'thematique');

	include_spip('thematique_fonctions');
	include_spip('action/editer_article');
	include_spip('action/editer_liens');

	$id_rubrique = thematique_assurer_structure_annee();
	if (!$id_rubrique) {
		spip_log(
			"thematique_rentree_annee $annee : thematique_assurer_structure_annee() a échoué (voir logs précédents), abandon",
			'thematique' . _LOG_ERREUR
		);
		return 1;
	}
	spip_log("thematique_rentree_annee $annee : rubrique de l'année #$id_rubrique OK", 'thematique');

	// Les deux étapes sont toujours exécutées, même si la première échoue,
	// pour qu'une erreur sur les jalons ne bloque pas les fiches intervenants.
	$jalons_ok = thematique_rentree_creer_jalons($id_rubrique, $annee);
	$fiches_ok = thematique_rentree_creer_fiches_intervenants($id_rubrique, $annee);

	if (!$jalons_ok || !$fiches_ok) {
		spip_log(
			"thematique_rentree_annee $annee : au moins un jalon ou une fiche intervenant n'a pas pu être créé, "
			. 'meta non mise à jour (nouvelle tentative au prochain passage du cron)',
			'thematique' . _LOG_ERREUR
		);
		return 1;
	}

	ecrire_meta('thematique_rentree_annee_traitee', $annee);
	spip_log(
		"thematique_rentree_annee $annee : traitement terminé, meta thematique_rentree_annee_traitee mise à jour",
		'thematique'
	);

	return 1;
}

/**
 * Le projet est-il actif sur ce site ? (_CCN_PROJET_ACTIVE, actif par défaut)
 */
function thematique_rentree_projet_actif(): bool {
	return !defined('_CCN_PROJET_ACTIVE') || (bool) _CCN_PROJET_ACTIVE;
}

/**
 * Crée les articles jalons "Cap sur l'année" et "La Rencontre" s'ils sont absents.
 *
 * @param int $id_rubrique Rubrique racine de l'année
 * @param int $annee       Année de la rentrée
 * @return bool true si tous les jalons existent à la fin
 */
function thematique_rentree_creer_jalons(int $id_rubrique, int $annee): bool {
	$id_intervenant = thematique_premier_intervenant($id_rubrique);

	$jalons = [
		'cap-sur-l-annee' => [
			'titre' => _T('thematique:cap_sur_annee'),
			'texte' => _T('thematique:jalon_texte_a_completer'),
			'date' => $annee . '-09-15 00:00:00',
		],
		'la-rencontre' => [
			'titre' => _T('thematique:la_rencontre'),
			'texte' => _T('thematique:jalon_texte_a_completer'),
			'date' => ($annee + 1) . '-06-15 00:00:00',
		],
	];

	$ok = true;
	foreach ($jalons as $mot => $infos) {
		if (!thematique_creer_article_mot_cle_si_absent($id_rubrique, $mot, $infos, $id_intervenant)) {
			$ok = false;
		}
	}

	return $ok;
}

/**
 * Crée une fiche intervenant (mot-clé "intervenant") dans chaque rubrique
 * intervenant de l'année, si elle est absente.
 *
 * @param int $id_parent Rubrique parente des rubriques intervenants
 * @param int $annee     Année de la rentrée
 * @return bool true si toutes les fiches existent à la fin
 */
function thematique_rentree_creer_fiches_intervenants(int $id_parent, int $annee): bool {
	$infos = [
		'titre' => _T('thematique:fiche_intervenant_titre'),
		'texte' => _T('thematique:fiche_intervenant_texte_a_completer'),
		'date' => $annee . '-09-01 00:00:00',
	];

	$rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_parent=' . $id_parent, '', 'id_rubrique');

	$ok = true;
	foreach ($rubriques as $rubrique) {
		$id_rubrique_intervenant = (int) $rubrique['id_rubrique'];

		$id_auteur = thematique_rentree_auteur_de_rubrique($id_rubrique_intervenant);
		if (!$id_auteur) {
			spip_log(
				"thematique_rentree_annee $annee : aucun auteur lié à la rubrique intervenant #$id_rubrique_intervenant",
				'thematique' . _LOG_ERREUR
			);
			$ok = false;
			continue;
		}

		if (!thematique_creer_article_mot_cle_si_absent($id_rubrique_intervenant, 'intervenant', $infos, $id_auteur)) {
			$ok = false;
		}
	}

	return $ok;
}

/**
 * Premier auteur lié à une rubrique (0 si aucun).
 */
function thematique_rentree_auteur_de_rubrique(int $id_rubrique): int {
	return (int) sql_getfetsel(
		'id_auteur',
		'spip_auteurs_liens',
		"objet='rubrique' AND id_objet=" . $id_rubrique,
		'',
		'id_auteur',
		'0,1'
	);
}