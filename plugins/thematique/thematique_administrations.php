<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/cextras');
include_spip('base/th_cextras');

function thematique_upgrade($nom_meta_base_version, $version_cible) {

	$maj = [];

	$maj['create'] = [
		['maj_tables', ['spip_articles']],
		['maj_tables', ['spip_syndic_articles']],
		['maj_tables', ['spip_rubriques']],
		['th_ajouter_mots_clef'],
		['sql_alter', "TABLE spip_syndic CHANGE oubli oubli VARCHAR(3) DEFAULT 'oui'"],
		['sql_alter', "TABLE spip_syndic CHANGE resume resume VARCHAR(3) DEFAULT 'non'"],
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
		['th_configurer_meta'],
		['th_configurer_rubriques'],
	];
	cextras_api_upgrade(th_declarer_champs_extras(), $maj['create']);

	$maj['2.3.3'] = [['th_configurer_site']];

	cextras_api_upgrade(th_declarer_champs_extras(), $maj['2.3.4']);

	$maj['2.3.5'] = [
		['sql_update', 'spip_auteurs', ['ent_statut' => 'bio']],
		['sql_update', 'spip_auteurs', ['ent' => 'pgp']],
	];

	$maj['2.3.6'] = [['th_ajouter_mots_clef']];

	$maj['2.3.13'] = [['th_configurer_meta']];

	$maj['2.4.0'] = [['th_configurer_rubriques']];

	$maj['3.0.3'] = [['th_ajouter_mots_clef'], ['maj_tables', ['spip_rubriques']]];
	cextras_api_upgrade(th_declarer_champs_extras(), $maj['3.0.7']);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function th_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}

function th_configurer_meta() {

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
	ecrire_meta('formats_documents_forum', 'gif, jpg, png, mp3, pdf');

	ecrire_meta('type_urls', 'simple');

	include_spip('inc/config');
	appliquer_modifs_config(true);
}

function th_configurer_site() {

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

function th_ajouter_mot($titre, $id_groupe) {
	if (!sql_getfetsel('id_mot', 'spip_mots', "titre=" . sql_quote($titre) . " AND id_groupe=" . intval($id_groupe))) {
		sql_insertq('spip_mots', ['titre' => $titre, 'id_groupe' => $id_groupe]);
	}
}

function th_ajouter_groupe_mots($titre, $tables_liees, $condition_extra = '') {
	$condition = "titre=" . sql_quote($titre);
	if ($condition_extra) {
		$condition .= " AND $condition_extra";
	}
	if (!$id_groupe = sql_getfetsel('id_groupe', 'spip_groupes_mots', $condition)) {
		$id_groupe = sql_insertq('spip_groupes_mots', [
			'titre'       => $titre,
			'unseul'      => 'non',
			'tables_liees' => $tables_liees,
			'minirezo'    => 'oui',
			'comite'      => 'non',
			'forum'       => 'non',
		]);
	}
	return $id_groupe;
}

function th_ajouter_mots_clef() {

	// Groupe Contenus
	$id_groupe = th_ajouter_groupe_mots('Contenus', 'rubriques');
	foreach (['travail_en_cours', 'consignes', 'evenements', 'blogs', 'ressources', 'images_background', 'agora'] as $mot) {
		th_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Presentation_rubriques
	$id_groupe = th_ajouter_groupe_mots('Presentation', 'rubriques', "tables_liees LIKE '%rubriques%'");
	foreach (['blog', 'pas_une', 'laclasse.com', 'trombinoscope'] as $mot) {
		th_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Presentation_articles
	$id_groupe = th_ajouter_groupe_mots('Presentation', 'articles', "tables_liees LIKE '%articles%'");
	foreach (['laclasse.com', 'sommaire_edito', 'livrable'] as $mot) {
		th_ajouter_mot($mot, $id_groupe);
	}

	// Groupe Sites
	th_ajouter_groupe_mots('site', '');
}

function th_configurer_rubriques() {
	$mots = [
		'travail_en_cours' => 'Travail des classes',
		'consignes' => 'Consignes',
		'ressources' => 'Espace Ressources',
		'blogs' => 'Agenda',
		'evenements' => 'Blog pédagogique',
		'images_background' => 'Contenu éditorial',
		'agora' => 'Discuter avec',
	];
	foreach ($mots as $mot => $titre) {
		$count = (int) sql_countsel(
			'spip_rubriques as sr
				LEFT JOIN spip_mots_liens as sml
					ON (sr.id_rubrique = sml.id_objet AND sml.objet = "rubrique")
				LEFT JOIN spip_mots as sm
					ON (sml.id_mot = sm.id_mot)',
			['sm.titre = "' . $mot . '"', 'sr.id_parent = 0']
		);

		if ($count < 1) {
			include_spip('action/editer_rubrique');
			$id_rubrique = rubrique_inserer(0);
			rubrique_modifier($id_rubrique, ['titre' => $titre]);

			$id_mot = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre = "' . $mot . '"');

			include_spip('action/editer_liens');
			$res = objet_associer(['mots' => $id_mot], ['rubriques' => $id_rubrique]);
		}
	}
}
