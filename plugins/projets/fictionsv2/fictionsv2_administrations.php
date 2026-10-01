<?php
/**
 * Installation / migration de fictionsv2
 *
 * Crée un groupe de mots-clés dédié aux contenus fictions v2
 * et attache les mots-clés existants aux rubriques/articles
 * qui étaient jusqu'ici identifiés par leurs titres/IDs codés en dur.
 *
 * Fichier <prefix>_administrations.php : seul nom chargé par SPIP pour appeler
 * fictionsv2_upgrade() quand le schema du paquet.xml change (l'ancien
 * install/fictionsv2_install.php n'était jamais exécuté).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Hook d'upgrade appelé automatiquement par SPIP à l'activation du plugin puis
 * à chaque changement de schema dans paquet.xml.
 */
function fictionsv2_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	// Issue #456 : migration des identifiants/titres codés en dur vers mots-clés
	$maj['create'] = [
		['fictionsv2_ajouter_mots_clef'],
		['fictionsv2_migrer_rubriques_articles'],
		['fictionsv2_migrer_rubrique_contenant_annees'],
	];

	// Chapitre 1 commun à toutes les histoires de l'année (cf fictionsv2_id_chapitre1())
	$maj['1.0.1'] = [
		['fictionsv2_ajouter_mots_clef'],
	];

	// Issue #453 : rubrique-contenant-annees (sélecteur d'années du footer, rentrée) et
	// mots des articles de config du footer, jusqu'ici créés à la main
	$maj['1.0.2'] = [
		['fictionsv2_ajouter_mots_clef'],
		['fictionsv2_migrer_rubrique_contenant_annees'],
	];

	// Mot année_en_ecriture (vue "fabrication" de l'accueil) créé s'il manque et posé
	// sur l'année active : la rentrée ne le posait pas, l'année s'affichait comme une
	// année terminée (cadres de couverture vides). Sans effet là où le mot est déjà
	// posé (objet_associer ignore un lien existant).
	$maj['1.0.3'] = [
		['fictionsv2_ajouter_mots_clef'],
		['fictionsv2_migrer_annee_en_ecriture'],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Désinstallation : on garde les mots-clés et leurs liaisons (contenu éditorial).
 */
function fictionsv2_vider_tables($nom_meta_base_version) {
	effacer_meta('fictionsv2_histoires_createurs');
	effacer_meta($nom_meta_base_version);
}

/**
 * Crée le groupe de mots-clés "Fictions v2" et les mots-clés nécessaires.
 * Idempotent : ne recrée pas ce qui existe déjà.
 */
function fictionsv2_ajouter_mots_clef() {
	$id_groupe = fictionsv2_ajouter_groupe_mots('Fictions v2', 'rubriques,articles');

	// Rubriques : "blog_pedagogique" identifie la rubrique du blog pédagogique
	fictionsv2_ajouter_mot('blog_pedagogique', $id_groupe);
	// "rubrique-contenant-annees" — parent des rubriques d'années ("2025", "2026"...)
	fictionsv2_ajouter_mot('rubrique-contenant-annees', $id_groupe);
	// "année_en_ecriture" — année en cours d'écriture (vue "fabrication" de l'accueil)
	fictionsv2_ajouter_mot('année_en_ecriture', $id_groupe);

	// Articles
	// "presentation" — article de présentation (page)
	fictionsv2_ajouter_mot('presentation', $id_groupe);
	// "prologue" — article du prologue
	fictionsv2_ajouter_mot('prologue', $id_groupe);
	// "chapitre1" — chapitre 1 commun à toutes les histoires de l'année
	fictionsv2_ajouter_mot('chapitre1', $id_groupe);
	// "footer-blog-auteur", "footer-espace-doc", "footer-forum" — articles de config du
	// footer, recopiés d'une année sur l'autre (cf fictionsv2_assurer_structure_annee())
	fictionsv2_ajouter_mot('footer-blog-auteur', $id_groupe);
	fictionsv2_ajouter_mot('footer-espace-doc', $id_groupe);
	fictionsv2_ajouter_mot('footer-forum', $id_groupe);
}

/**
 * Attache les mots-clés aux rubriques/articles existants qui étaient
 * jusqu'ici identifiés par leurs titres codés en dur.
 */
function fictionsv2_migrer_rubriques_articles() {
	// Rubrique "Blog pédagogique" → mot "blog_pedagogique"
	fictionsv2_associer_mot_par_titre('blog_pedagogique', 'rubrique', '%Blog Pédagogique%');
	// Articles "presentation…" → mot "presentation"
	fictionsv2_associer_mot_par_titre('presentation', 'article', 'presentation%');
	// Articles "prologue…" → mot "prologue"
	fictionsv2_associer_mot_par_titre('prologue', 'article', 'prologue%');
}

/**
 * Attache "rubrique-contenant-annees" au parent de la rubrique d'année la plus récente,
 * si aucune rubrique ne le porte encore (ne touche pas à un tag posé à la main).
 */
function fictionsv2_migrer_rubrique_contenant_annees() {
	$id_mot = intval(sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote('rubrique-contenant-annees')));
	if (!$id_mot || sql_countsel('spip_mots_liens', ['id_mot=' . $id_mot, 'objet=' . sql_quote('rubrique')])) {
		return;
	}
	$id_parent = intval(sql_getfetsel(
		'id_parent',
		'spip_rubriques',
		'titre REGEXP ' . sql_quote('^[0-9]{4}$'),
		'',
		'titre DESC',
		'0,1'
	));
	if (!$id_parent) {
		return;
	}
	include_spip('action/editer_liens');
	objet_associer(['mot' => $id_mot], ['rubrique' => $id_parent]);
}

/**
 * Pose année_en_ecriture sur la rubrique de l'année scolaire active (une seule fois,
 * à la migration).
 */
function fictionsv2_migrer_annee_en_ecriture() {
	include_spip('fictionsv2_fonctions');
	include_spip('inc/fictionsv2_rentree');
	$id_annee = fictionsv2_id_rubrique_annee(_ANNEE_SCOLAIRE);
	if ($id_annee) {
		fictionsv2_marquer_annee_en_ecriture($id_annee);
	}
}

/**
 * Associe le mot $titre_mot à tous les objets dont le titre correspond au motif LIKE.
 * objet_associer() ignore les liaisons déjà existantes.
 */
function fictionsv2_associer_mot_par_titre($titre_mot, $objet, $motif) {
	$id_mot = intval(sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot)));
	if (!$id_mot) {
		return;
	}
	include_spip('action/editer_liens');
	$id_table = id_table_objet($objet);
	$rows = sql_allfetsel($id_table, table_objet_sql($objet), 'titre LIKE ' . sql_quote($motif));
	foreach ($rows as $row) {
		objet_associer(['mot' => $id_mot], [$objet => intval($row[$id_table])]);
	}
}

/**
 * Crée un groupe de mots-clés s'il n'existe pas déjà.
 */
function fictionsv2_ajouter_groupe_mots($titre, $tables_liees) {
	$id_groupe = sql_getfetsel('id_groupe', 'spip_groupes_mots', 'titre=' . sql_quote($titre));
	if (!$id_groupe) {
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

/**
 * Crée un mot-clé s'il n'existe dans aucun groupe : les squelettes et
 * fictionsv2_id_mot() le cherchent par titre seul, un mot déjà créé à la main
 * (ex: dans le groupe "Présentation") suffit et ne doit pas être dupliqué.
 */
function fictionsv2_ajouter_mot($titre, $id_groupe) {
	if (sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre))) {
		return;
	}
	$type = sql_getfetsel('titre', 'spip_groupes_mots', 'id_groupe=' . intval($id_groupe));
	sql_insertq('spip_mots', ['titre' => $titre, 'id_groupe' => intval($id_groupe), 'type' => $type]);
}
