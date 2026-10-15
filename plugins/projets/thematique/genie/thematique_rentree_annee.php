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
 * 3. crée ou réutilise la fiche de l'intervenant de l'année, dans la
 *    rubrique racine « Intervenants » (année ajoutée à son surtitre, cf
 *    thematique_creer_fiche_intervenant()).
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
			'thematique_rentree_annee : hors fenêtre (mois=' . date(
				'n'
			) . ', attendu=' . _THEMATIQUE_RENTREE_MOIS . '), on ne fait rien',
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
	// pour qu'une erreur sur les jalons ne bloque pas la fiche intervenant.
	$jalons_ok = thematique_creer_jalons($id_rubrique, $annee);
	$fiche_ok = thematique_creer_fiche_intervenant();

	if (!$jalons_ok || !$fiche_ok) {
		spip_log(
			"thematique_rentree_annee $annee : au moins un jalon ou la fiche intervenant n'a pas pu être créé, "
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
