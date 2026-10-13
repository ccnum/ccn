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
 * 3. crée (si absente) une fiche d'intervenant dans chaque rubrique de
 *    projet sous "Consignes", identifiée par le mot-clé intervenant.
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
 * @see plugins/projets/thematique/genie/thematique_rentree_poubelle.php pour le
 *      même schéma de déclenchement (une fois par an, en septembre).
 *
 * @param int $last
 * @return int
 */
function genie_thematique_rentree_annee_dist($last) {
	spip_log('thematique_rentree_annee : tâche déclenchée (last=' . $last . ')', 'thematique');

	if (defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE) {
		spip_log('thematique_rentree_annee : _CCN_PROJET_ACTIVE=false, projet inactif, on ne fait rien', 'thematique');
		return 1;
	}

	if (date('n') != 9) {
		spip_log(
			'thematique_rentree_annee : hors fenêtre (mois=' . date('n') . ', déclenché seulement en août), on ne fait rien',
			'thematique'
		);
		return 1;
	}

	$annee = date('Y');
	$derniere_execution = intval($GLOBALS['meta']['thematique_rentree_annee_traitee'] ?? 0);
	if ($derniere_execution >= $annee) {
		// Ne PAS s'arrêter là pour autant : cette meta n'est écrite plus bas
		// que si tous les jalons ont vraiment été créés (cf $tous_jalons_ok),
		// mais une exécution antérieure à ce garde-fou a pu la poser à tort
		// après un échec silencieux (ex: mot-clé manquant en base). On
		// continue donc, la vérification par jalon plus bas étant de toute
		// façon idempotente (ne recrée pas ce qui existe déjà) : ça permet
		// de se rattraper tout seul au prochain passage du cron sans accès
		// serveur pour vider la meta à la main.
		spip_log(
			"thematique_rentree_annee $annee : déjà marquée traitée (dernière exécution $derniere_execution), on revérifie quand même que les jalons existent",
			'thematique'
		);
	}

	spip_log("thematique_rentree_annee $annee : traitement en cours", 'thematique');

	include_spip('thematique_fonctions');
	// crée la rubrique de l'année (+ "Travail des classes"/"Consignes") si
	// elle n'existe pas encore sur ce site — évite d'avoir à le faire à la
	// main sur chacun des ~40 sites CCN à chaque rentrée.
	$id_rubrique = thematique_assurer_structure_annee();
	if (!$id_rubrique) {
		spip_log(
			"thematique_rentree_annee $annee : thematique_assurer_structure_annee() a échoué (voir logs précédents), abandon",
			'thematique' . _LOG_ERREUR
		);
		return 1;
	}

	spip_log("thematique_rentree_annee $annee : rubrique de l'année #$id_rubrique OK", 'thematique');

	$id_intervenant = thematique_premier_intervenant($id_rubrique);

	$jalons = [
		'cap-sur-l-annee' => [
			'titre' => _T("thematique:cap_sur_annee"),
			'texte' => _T("thematique:jalon_texte_a_completer"),
			'date' => $annee . '-09-15 00:00:00',
		],
		'la-rencontre' => [
			'titre' => _T("thematique:la_rencontre"),
			'texte' => _T("thematique:jalon_texte_a_completer"),
			'date' => ($annee + 1) . '-06-15 00:00:00',
		],
	];

	include_spip('action/editer_article');
	include_spip('action/editer_liens');

	// Passe à false dès qu'un article attendu n'a pas pu être créé (mot-clé
	// manquant, auteur ou insertion) : on ne marque alors pas l'année comme
	// "traitée" plus bas, pour que le job retente automatiquement au
	// prochain passage du cron (dans les 24h, tant qu'on est en août) au
	// lieu de rester bloqué jusqu'à la rentrée suivante.
	$tous_articles_ok = true;
	foreach ($jalons as $titre_mot => $infos) {
		if (!thematique_creer_article_mot_cle_si_absent($id_rubrique, $titre_mot, $infos, $id_intervenant)) {
			$tous_articles_ok = false;
		}
	}

	$infos_fiche_intervenant = [
		'titre' => _T('thematique:fiche_intervenant_titre'),
		'texte' => _T('thematique:fiche_intervenant_texte_a_completer'),
		'date' => $annee . '-09-01 00:00:00',
	];
	$rubriques_intervenants = sql_allfetsel(
		'id_rubrique',
		'spip_rubriques',
		'id_parent=' . intval($id_rubrique),
		'',
		'id_rubrique'
	);
	foreach ($rubriques_intervenants as $rubrique_intervenant) {
		$id_rubrique_intervenant = intval($rubrique_intervenant['id_rubrique']);
		$id_auteur_intervenant = intval(sql_getfetsel(
			'id_auteur',
			'spip_auteurs_liens',
			"objet='rubrique' AND id_objet=" . $id_rubrique_intervenant,
			'',
			'id_auteur',
			'0,1'
		));
		if (!$id_auteur_intervenant) {
			spip_log(
				"thematique_rentree_annee $annee : aucun auteur lié à la rubrique intervenant #$id_rubrique_intervenant",
				'thematique' . _LOG_ERREUR
			);
			$tous_articles_ok = false;
		}
		if (!thematique_creer_article_mot_cle_si_absent(
			$id_rubrique_intervenant,
			'intervenant',
			$infos_fiche_intervenant,
			$id_auteur_intervenant
		)) {
			$tous_articles_ok = false;
		}
	}

	if (!$tous_articles_ok) {
		spip_log(
			"thematique_rentree_annee $annee : au moins un jalon ou une fiche intervenant n'a pas pu être créé, meta non mise à jour (nouvelle tentative au prochain passage du cron)",
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
