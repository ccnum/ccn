<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * À la rentrée de septembre, sur chaque site CCN :
 * 1. crée la structure de la nouvelle année scolaire si elle n'existe pas
 *    encore (rubrique racine + "Travail des classes"/"Consignes", cf
 *    thematique_assurer_structure_annee()) — évite de le faire à la main
 *    sur chacun des ~40 sites ;
 * 2. crée (si absents) les deux articles jalons du projet : "Cap sur
 *    l'année" (cap-sur-l-annee) et "La Rencontre" (la-rencontre), en
 *    statut prop, assignés au premier intervenant trouvé sur le projet.
 *    C'est ensuite à lui de les compléter et de les publier.
 *
 * Enregistrée via le pipeline taches_generales_cron (thematique_pipelines.php)
 * plutôt que la balise <genie> de paquet.xml : la balise <genie> fait la
 * même chose en interne (cf ecrire/inc/genie.php) mais nécessite que SPIP
 * revérifie le paquet du plugin pour enregistrer la tâche, alors que le
 * pipeline est réévalué à chaque calcul des tâches de fond.
 *
 * Désactivable projet par projet via _CCN_PROJET_ACTIVE (mes_options.php,
 * défini depuis la variable d'environnement Docker CCN_PROJET_ACTIVE) pour
 * les CCN qui ne repartent pas d'une année sur l'autre.
 *
 * @see plugins/thematique/genie/thematique_rentree_poubelle.php pour le
 *      même schéma de déclenchement (une fois par an, en septembre).
 *
 * @param int $last
 * @return int
 */
function genie_thematique_rentree_annee_dist($last) {
	if (defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE) {
		return 1;
	}

	if (date('n') != 8) {
		return 1;
	}

	$annee = date('Y');
	$derniere_execution = intval($GLOBALS['meta']['thematique_rentree_annee_traitee'] ?? 0);
	if ($derniere_execution >= $annee) {
		return 1;
	}

	include_spip('thematique_fonctions');
	// crée la rubrique de l'année (+ "Travail des classes"/"Consignes") si
	// elle n'existe pas encore sur ce site — évite d'avoir à le faire à la
	// main sur chacun des ~40 sites CCN à chaque rentrée.
	$id_rubrique = thematique_assurer_structure_annee();
	if (!$id_rubrique) {
		return 1;
	}

	$id_intervenant = thematique_premier_intervenant($id_rubrique);

	$jalons = [
		'cap-sur-l-annee' => [
			'titre' => "Cap sur l'année",
			'date' => $annee . '-09-15 00:00:00',
		],
		'la-rencontre' => [
			'titre' => 'La Rencontre',
			'date' => ($annee + 1) . '-06-15 00:00:00',
		],
	];

	include_spip('action/editer_article');
	include_spip('action/editer_objet');
	include_spip('action/editer_liens');

	foreach ($jalons as $titre_mot => $infos) {
		$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
		if (!$id_mot) {
			spip_log("thematique_rentree_annee : mot-clé '$titre_mot' introuvable, ignoré", 'thematique' . _LOG_ERREUR);
			continue;
		}

		// déjà créé (idempotent, au cas où le job repasserait dans le mois)
		$id_article_existant = sql_getfetsel(
			'spip_articles.id_article',
			['spip_articles', 'spip_mots_liens'],
			[
				'spip_mots_liens.id_objet=spip_articles.id_article',
				"spip_mots_liens.objet='article'",
				'spip_mots_liens.id_mot=' . intval($id_mot),
				'spip_articles.id_rubrique=' . intval($id_rubrique),
			]
		);
		if ($id_article_existant) {
			continue;
		}

		$id_article = article_inserer($id_rubrique);
		if (!$id_article) {
			spip_log("thematique_rentree_annee : échec de création de l'article '$titre_mot'", 'thematique' . _LOG_ERREUR);
			continue;
		}

		objet_instituer('article', $id_article, [
			'titre' => $infos['titre'],
			'date' => $infos['date'],
			'statut' => 'prop',
		]);

		objet_associer(['mots' => intval($id_mot)], ['articles' => $id_article]);
		if ($id_intervenant) {
			objet_associer(['auteurs' => intval($id_intervenant)], ['articles' => $id_article]);
		}

		spip_log(
			"thematique_rentree_annee $annee : article #$id_article ('$titre_mot') créé en statut prop",
			'thematique'
		);
	}

	ecrire_meta('thematique_rentree_annee_traitee', $annee);

	return 1;
}
