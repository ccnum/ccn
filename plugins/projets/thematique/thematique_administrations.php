<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/cextras');
include_spip('base/thematique_cextras');

function thematique_upgrade($nom_meta_base_version, $version_cible) {

	$maj = [];

	$maj['create'] = [
		['maj_tables', ['spip_articles']],
		['maj_tables', ['spip_syndic_articles']],
		['maj_tables', ['spip_rubriques']],
		['thematique_ajouter_mots_clef'],
		['ecrire_meta', 'articles_mots', 'oui'],
		['ecrire_meta', 'activer_sites', 'oui'],
		['ecrire_meta', 'activer_syndic', 'oui'],
		['ecrire_meta', 'activer_statistiques', 'oui'],
		['ecrire_meta', 'articles_descriptif', 'oui'],
		['ecrire_meta', 'articles_soustitre', 'oui'],
		['ecrire_meta', 'articles_surtitre', 'oui'],
		['ecrire_meta', 'articles_modif', 'oui'],
		['ecrire_meta', 'documents_article', 'oui'],
		['ecrire_meta', 'documents_rubrique', 'oui'],
		['ecrire_meta', 'documents_article', 'oui'],
		['thematique_configurer_meta'],
		['thematique_configurer_rubriques'],
	];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['create']);

	$maj['2.3.3'] = [['thematique_configurer_site']];

	$maj['2.3.4'] = [];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['2.3.4']);

	$maj['2.3.5'] = [];

	$maj['2.3.6'] = [['thematique_ajouter_mots_clef']];

	$maj['2.3.13'] = [['thematique_configurer_meta']];

	$maj['2.4.0'] = [['thematique_configurer_rubriques']];

	$maj['3.0.3'] = [['thematique_ajouter_mots_clef'], ['maj_tables', ['spip_rubriques']]];

	$maj['3.0.7'] = [['maj_tables', ['spip_articles', 'spip_rubriques']]];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['3.0.7']);

	$maj['3.0.8'] = [['maj_tables', ['spip_articles', 'spip_rubriques']]];

	$maj['3.0.9'] = [];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['3.0.9']);

	// mots-clés cap-sur-l-annee/la-rencontre : jamais créés jusqu'ici (absents
	// de thematique_ajouter_mots_clef() avant ce correctif), donc
	// genie/thematique_rentree_annee.php ne pouvait jamais créer les articles
	// jalons sur aucun site existant ("mot-clé introuvable, ignoré" en log).
	// Rejoue thematique_ajouter_mots_clef() (idempotent) pour les ajouter
	// rétroactivement sur tous les sites déjà installés.
	$maj['3.4.0'] = [['thematique_ajouter_mots_clef']];

	// spip_auteurs.nom_complet (prénom+nom réels, cf #SESSION{nom_complet} dans
	// authentification.html) : nouveau champ extra, à créer en base.
	$maj['3.4.1'] = [];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['3.4.1']);

	// Issue #369 : id_consigne (spip_articles) et id_rubrique_lien
	// (spip_rubriques) étaient déclarés avec un type différent ici
	// (bigint(21), via thematique_install.php) et côté champs extras
	// (int(5)/text, via thematique_cextras.php) — mismatch qui casse l'ADD
	// INDEX sur une (dés)installation/activation du plugin (MySQL interdit
	// un préfixe de longueur d'index sur une colonne numérique). Les deux
	// déclarations sont maintenant alignées sur bigint(21).
	// maj_tables()/cextras_api_upgrade() n'ALTERent jamais le type d'une
	// colonne déjà existante (seulement les colonnes/clés manquantes) :
	// cette entrée de version n'a donc d'effet que sur une (ré)installation
	// complète du plugin (table recréée/champs recréés depuis $maj['create']).
	// Un site déjà touché par le bug doit corriger sa colonne manuellement
	// (cf le correctif posté par le rapporteur sur l'issue #369).
	$maj['3.4.2'] = [['maj_tables', ['spip_articles', 'spip_rubriques']]];
	cextras_api_upgrade(thematique_declarer_champs_extras(), $maj['3.4.2']);

	$maj['3.4.3'] = [['thematique_migrer_bibliotheques']];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Réorganise l'arborescence "Ressources" (issue #299, décision du 18/09) :
 *
 * 1. trouve la rubrique racine (id_parent = 0) portant le mot-clé
 *    "ressources" (celle créée par thematique_configurer_rubriques(),
 *    jusqu'ici titrée "Espace Ressources") ;
 * 2. parmi ses rubriques filles déjà existantes, en renomme 3 en
 *    "Méthodologie", "Pédagogie" et "Thématique" (pas de création : on
 *    réutilise des rubriques déjà là, pour ne perdre aucun contenu déjà
 *    lié à elles) ;
 * 3. déplace tous les articles de tout le secteur (racine + toutes ses
 *    sous-rubriques, à toute profondeur) vers la rubrique "Pédagogie" ;
 * 4. supprime toutes les rubriques du secteur devenues inutiles (tout sauf
 *    la racine et les 3 rubriques renommées) ;
 * 5. renomme la racine en "Ressources".
 *
 * Idempotente : si la racine est déjà titrée "Ressources" et possède déjà
 * une fille "Pédagogie", on considère la migration déjà faite et on ne
 * touche à rien (évite de re-brasser les rubriques à un réexécution du
 * job de maj, cf thematique_rentree_annee.php pour le même principe de
 * garde).
 */
function thematique_migrer_bibliotheques() {
	// -----------------------------------------------------------------
	// 1. Trouver la rubrique racine "ressources" (mot-clé, pas titre :
	// le titre est justement ce qu'on va changer, et thematique_-
	// configurer_rubriques() n'a jamais garanti "Bibliothèque" comme titre).
	// -----------------------------------------------------------------

	$id_racine = (int) sql_getfetsel(
		'sr.id_rubrique',
		'spip_rubriques AS sr
			INNER JOIN spip_mots_liens AS sml ON (sr.id_rubrique=sml.id_objet AND sml.objet=' . sql_quote('rubrique') . ')
			INNER JOIN spip_mots AS sm ON sml.id_mot=sm.id_mot',
		'sm.titre=' . sql_quote('ressources') . ' AND sr.id_parent=0'
	);

	if (!$id_racine) {
		spip_log(
			'thematique_migrer_bibliotheques : aucune rubrique racine taguée "ressources", rien à migrer',
			'thematique'
		);
		return;
	}

	// -----------------------------------------------------------------
	// Garde d'idempotence : déjà migré ?
	// -----------------------------------------------------------------

	$deja_migre = sql_countsel('spip_rubriques', 'id_parent=' . $id_racine . ' AND titre=' . sql_quote('Pédagogie'));
	if ($deja_migre) {
		spip_log(
			"thematique_migrer_bibliotheques : rubrique #$id_racine déjà migrée (fille 'Pédagogie' présente), rien à faire",
			'thematique'
		);
		return;
	}

	// -----------------------------------------------------------------
	// 2. Choisir 3 rubriques filles déjà existantes et les renommer.
	//
	// Ordre par id_rubrique : arbitraire mais déterministe (peu importe
	// laquelle des 3 devient laquelle, tout le contenu est de toute façon
	// re-regroupé dans "Pédagogie" juste après).
	// -----------------------------------------------------------------

	$enfants = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_parent=' . $id_racine, '', 'id_rubrique ASC');

	if (count($enfants) < 3) {
		spip_log(
			'thematique_migrer_bibliotheques : rubrique #' . $id_racine . ' a moins de 3 sous-rubriques (' . count(
				$enfants
			) . '), abandon (rien à renommer)',
			'thematique' . _LOG_ERREUR
		);
		return;
	}

	$nouveaux_titres = ['Méthodologie', 'Pédagogie', 'Thématique'];
	$id_pedagogie = 0;

	foreach (array_slice($enfants, 0, 3) as $index => $enfant) {
		$id_enfant = intval($enfant['id_rubrique']);
		$titre = $nouveaux_titres[$index];

		sql_updateq('spip_rubriques', ['titre' => $titre], 'id_rubrique=' . $id_enfant);

		if ($titre === 'Pédagogie') {
			$id_pedagogie = $id_enfant;
		}
	}

	// -----------------------------------------------------------------
	// 3. Construire récursivement toute l'arborescence du secteur
	// (racine comprise), enfants avant parents — utile pour supprimer
	// ensuite dans le bon ordre.
	// -----------------------------------------------------------------

	$vus = [];
	$rubriques_du_secteur = [];

	$ajouter_arborescence = function ($id_rubrique) use (&$ajouter_arborescence, &$vus, &$rubriques_du_secteur) {
		$id_rubrique = intval($id_rubrique);

		if (!$id_rubrique || isset($vus[$id_rubrique])) {
			return;
		}
		$vus[$id_rubrique] = true;

		$enfants = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_parent=' . $id_rubrique);
		foreach ($enfants as $enfant) {
			$ajouter_arborescence($enfant['id_rubrique']);
		}

		$rubriques_du_secteur[] = $id_rubrique;
	};

	$ajouter_arborescence($id_racine);

	// -----------------------------------------------------------------
	// 4. Déplacer tous les articles du secteur vers "Pédagogie".
	// -----------------------------------------------------------------

	foreach ($rubriques_du_secteur as $id_rubrique) {
		if ($id_rubrique === $id_pedagogie) {
			continue;
		}

		sql_updateq('spip_articles', ['id_rubrique' => $id_pedagogie], 'id_rubrique=' . $id_rubrique);
	}

	// -----------------------------------------------------------------
	// 5. Supprimer les rubriques du secteur devenues inutiles : tout sauf
	// la racine et les 3 rubriques renommées à l'étape 2. On nettoie
	// d'abord leurs associations mots-clés/auteurs (spip_rubriques n'a pas
	// de suppression en cascade), puis on les supprime feuilles d'abord.
	// -----------------------------------------------------------------

	$id_a_conserver = array_merge([$id_racine], array_column(array_slice($enfants, 0, 3), 'id_rubrique'));
	$id_a_conserver = array_map('intval', $id_a_conserver);

	foreach ($rubriques_du_secteur as $id_rubrique) {
		if (in_array($id_rubrique, $id_a_conserver, true)) {
			continue;
		}

		sql_delete('spip_mots_liens', 'objet=' . sql_quote('rubrique') . ' AND id_objet=' . $id_rubrique);
		sql_delete('spip_auteurs_liens', 'objet=' . sql_quote('rubrique') . ' AND id_objet=' . $id_rubrique);
		sql_delete('spip_rubriques', 'id_rubrique=' . $id_rubrique);
	}

	// -----------------------------------------------------------------
	// 6. Renommer la racine en "Ressources".
	// -----------------------------------------------------------------

	sql_updateq('spip_rubriques', ['titre' => 'Ressources'], 'id_rubrique=' . $id_racine);

	spip_log(
		"thematique_migrer_bibliotheques : secteur #$id_racine migré, articles regroupés dans 'Pédagogie' (#$id_pedagogie)",
		'thematique'
	);
}

function thematique_vider_tables($nom_meta_base_version) {
	foreach (['Contenus', 'Presentation', 'site'] as $titre) {
		$groupes = sql_allfetsel('id_groupe', 'spip_groupes_mots', 'titre=' . sql_quote($titre));
		foreach ($groupes as $g) {
			$id_groupe = intval($g['id_groupe']);
			$mots = sql_allfetsel('id_mot', 'spip_mots', 'id_groupe=' . $id_groupe);
			foreach ($mots as $m) {
				sql_delete('spip_mots_liens', 'id_mot=' . intval($m['id_mot']));
			}
			sql_delete('spip_mots', 'id_groupe=' . $id_groupe);
			sql_delete('spip_groupes_mots', 'id_groupe=' . $id_groupe);
		}
	}
	effacer_meta($nom_meta_base_version);
}

function thematique_configurer_meta() {

	$documents_objets = lire_config('documents_objets');
	if (!preg_match('/spip\_articles/', $documents_objets)) {
		$documents_objets .= ',spip_articles';
	}
	if (!preg_match('/spip\_rubriques/', $documents_objets)) {
		$documents_objets .= ',spip_rubriques';
	}
	ecrire_meta('documents_objets', $documents_objets);

	ecrire_meta('image_process', 'gd2', 'non');
	ecrire_meta('formats_graphiques', lire_config('gd_formats_read'), 'non');

	ecrire_meta('auto_compress_http', 'oui');
	ecrire_meta('auto_compress_js', 'oui');
	ecrire_meta('auto_compress_closure', 'oui');
	ecrire_meta('auto_compress_css', 'oui');

	ecrire_meta('accepter_visiteurs', 'oui');

	ecrire_meta('forums_publics', 'abo');
	ecrire_meta('formats_documents_forum', '.pdf,.jpg,.jpeg,.png,.gif');

	ecrire_meta('type_urls', 'simple');

	include_spip('inc/config');
	appliquer_modifs_config(true);
}

function thematique_configurer_site() {

	$nom_site_spip = lire_config('nom_site');
	$site_ent_url = '';
	$site_ent_nom = '';

	switch ($nom_site_spip) {
		case 'philo.laclasse.com':
			$nom_site_spip = 'philo';
			$site_ent_nom = '.laclasse.com';
			/*
		if login
		http://www.laclasse.com/pls/education/!page.laclasse?rubrique=428&choix=105&p_env_id=688
		*/
			break;

		case 'design.laclasse.com':
			$nom_site_spip = 'design';
			$site_ent_url = 'Atelier design';
			// $site_ent_nom = $url_site_spip;
			/*
		if login & pgp = cybercolleges42
		$site_parent_url = http://www.cybercolleges42.fr
		$site_parent_nom = ".cybercolleges42.fr"
		if login
		$site_parent_nom = ".laclasse.com"
		$site_parent_url = http://www.laclasse.com
		*/

			break;
		default:
			$site_ent_url = lire_config('th/site_parent_url');
			$site_ent_nom = lire_config('th/site_ent_nom');
	}

	ecrire_config('th/site_ent_url', $site_ent_url);
	ecrire_config('th/site_ent_nom', $site_ent_nom);
	ecrire_config('nom_site', $nom_site_spip);
}

function thematique_ajouter_mot($titre, $id_groupe) {
	if (!sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre) . ' AND id_groupe=' . intval($id_groupe))) {
		sql_insertq('spip_mots', ['titre' => $titre, 'id_groupe' => $id_groupe]);
	}
}

function thematique_ajouter_groupe_mots($titre, $tables_liees, $condition_extra = '') {
	$condition = 'titre=' . sql_quote($titre);
	if ($condition_extra) {
		$condition .= " AND $condition_extra";
	}
	if (!$id_groupe = sql_getfetsel('id_groupe', 'spip_groupes_mots', $condition)) {
		$id_groupe = sql_insertq('spip_groupes_mots', [
			'titre' => $titre,
			'unseul' => 'non',
			'tables_liees' => $tables_liees,
			'minirezo' => 'oui',
			'comite' => 'non',
			'forum' => 'non',
		]);
	}
	return $id_groupe;
}

function thematique_ajouter_mots_clef() {

	// Groupe Contenus
	$id_groupe = thematique_ajouter_groupe_mots('Contenus', 'rubriques');
	foreach (['travail_en_cours', 'consignes', 'evenements', 'blogs', 'ressources', 'images_background'] as $mot) {
		thematique_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Presentation_rubriques
	$id_groupe = thematique_ajouter_groupe_mots('Presentation', 'rubriques', "tables_liees LIKE '%rubriques%'");
	foreach (['blog', 'pas_une', 'laclasse.com', 'trombinoscope'] as $mot) {
		thematique_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Presentation_articles
	$id_groupe = thematique_ajouter_groupe_mots('Presentation', 'articles', "tables_liees LIKE '%articles%'");
	// cap-sur-l-annee/la-rencontre : tags des 2 articles jalons du projet
	// (cf genie/thematique_rentree_annee.php), pas des articles "présentation"
	// au sens strict, mais rattachés à ce groupe existant plutôt qu'un
	// groupe dédié pour 2 mots-clés seulement.
	foreach (['laclasse.com', 'sommaire_edito', 'cap-sur-l-annee', 'la-rencontre'] as $mot) {
		thematique_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Sites
	thematique_ajouter_groupe_mots('site', '');
}

function thematique_configurer_rubriques() {
	$mots = [
		'travail_en_cours' => 'Travail des classes',
		'consignes' => 'Consignes',
		'ressources' => 'Espace Ressources',
		'blogs' => 'Agenda',
		'evenements' => 'Blog pédagogique',
		'images_background' => 'Contenu éditorial',
	];
	foreach ($mots as $mot => $titre) {
		$count = (int) sql_countsel(
			'spip_rubriques as sr
				LEFT JOIN spip_mots_liens as sml
					ON (sr.id_rubrique = sml.id_objet AND sml.objet = "rubrique")
				LEFT JOIN spip_mots as sm
					ON (sml.id_mot = sm.id_mot)',
			['sm.titre = ' . sql_quote($mot), 'sr.id_parent = 0']
		);

		if ($count < 1) {
			include_spip('action/editer_rubrique');
			$id_rubrique = rubrique_inserer(0);
			rubrique_modifier($id_rubrique, ['titre' => $titre]);

			$id_mot = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre = ' . sql_quote($mot));

			include_spip('action/editer_liens');
			objet_associer(['mots' => $id_mot], ['rubriques' => $id_rubrique]);
		}
	}
}
