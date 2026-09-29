<?php
/**
 * Installation / migration de fictionsv2
 * 
 * Crée un groupe de mots-clés dédié aux contenus fictions v2
 * et attache les mots-clés existants aux rubriques/articles
 * qui étaient jusqu'ici identifiés par leurs titres/IDs codés en dur.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Hook d'upgrade appelé automatiquement par SPIP à chaque mise à jour
 * du plugin (quand le version du paquet.xml change).
 * Exécuté avant toute autre chose, donc c'est le bon endroit pour
 * les migrations de données avant l'activation du nouveau code.
 */
function fictionsv2_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	// v1.0.3 — Issue #456 : migration des identifiants/titres codés en dur vers mots-clés
	$maj['create'] = [
		['fictionsv2_ajouter_mots_clef'],
		['fictionsv2_migrer_rubriques_articles'],
	];

	// Utiliser la fonction cextras si disponible, sinon exécuter les fonctions en série
	if (function_exists('cextras_api_upgrade')) {
		cextras_api_upgrade([], $maj['create']);
	} else {
		foreach ($maj['create'] as $tache) {
			call_user_func($tache[0]);
		}
	}
}

/**
 * Crée le groupe de mots-clés "Fictions v2" et les 3 mots-clés nécessaires.
 * Idempotent : ne recrée pas ce qui existe déjà.
 */
function fictionsv2_ajouter_mots_clef() {
	// Créer ou récupérer le groupe "Fictions v2" pour rubriques ET articles
	$id_groupe = fictionsv2_ajouter_groupe_mots('Fictions v2', 'rubriques,articles');

	// Mots-clés pour les rubriques
	// "Blog pédagogique" — identifie la rubrique du blog pédagogique
	fictionsv2_ajouter_mot('blog_pedagogique', $id_groupe, 'rubriques');

	// Mots-clés pour les articles
	// "presentation" — identifie l'article de présentation (page)
	fictionsv2_ajouter_mot('presentation', $id_groupe, 'articles');
	// "prologue" — identifie l'article du prologue
	fictionsv2_ajouter_mot('prologue', $id_groupe, 'articles');
}

/**
 * Attache les mots-clés aux rubriques/articles existants qui étaient
 * jusqu'ici identifiés par leurs titres/IDs codés en dur.
 */
function fictionsv2_migrer_rubriques_articles() {
	// 1. Rubrique "Blog pédagogique" → mot "blog_pedagogique"
	// Trouve les rubriques dont le titre correspond approximativement à "Blog pédagogique"
	$id_rubriques_blog = sql_allfetsel('id_rubrique', 'spip_rubriques',
		'titre LIKE ' . sql_quote('%Blog Pédagogique%'));
	foreach ($id_rubriques_blog as $row) {
		// Vérifie si le mot-clé est déjà attaché
		if (!sql_getfetsel('id_mot', 'spip_mots_liens',
				'id_objet=' . intval($row['id_rubrique']) . ' AND objet=\'rubrique\' AND id_mot IN (SELECT id_mot FROM spip_mots WHERE titre=\'blog_pedagogique\')')) {
			$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=\'blog_pedagogique\'');
			if ($id_mot) {
				objet_associer(['mots' => intval($id_mot)], ['rubriques' => intval($row['id_rubrique'])]);
			}
		}
	}

	// 2. Articles de "presentation" → mot "presentation"
	// L'ancienne logique cherchait les articles dont le titre commence par "presentation"
	$id_articles_presentation = sql_allfetsel('id_article', 'spip_articles',
		'titre LIKE ' . sql_quote('presentation%'));
	foreach ($id_articles_presentation as $row) {
		if (!sql_getfetsel('id_mot', 'spip_mots_liens',
				'id_objet=' . intval($row['id_article']) . ' AND objet=\'article\' AND id_mot IN (SELECT id_mot FROM spip_mots WHERE titre=\'presentation\')')) {
			$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=\'presentation\' AND id_groupe IN (SELECT id_groupe FROM spip_groupes_mots WHERE tables_liees LIKE \'%articles%\')');
			if ($id_mot) {
				objet_associer(['mots' => intval($id_mot)], ['articles' => intval($row['id_article'])]);
			}
		}
	}

	// 3. Articles de "prologue" → mot "prologue"
	// L'ancienne logique cherchait les articles dont le titre commence par "prologue"
	$id_articles_prologue = sql_allfetsel('id_article', 'spip_articles',
		'titre LIKE ' . sql_quote('prologue%'));
	foreach ($id_articles_prologue as $row) {
		if (!sql_getfetsel('id_mot', 'spip_mots_liens',
				'id_objet=' . intval($row['id_article']) . ' AND objet=\'article\' AND id_mot IN (SELECT id_mot FROM spip_mots WHERE titre=\'prologue\')')) {
			$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=\'prologue\'');
			if ($id_mot) {
				objet_associer(['mots' => intval($id_mot)], ['articles' => intval($row['id_article'])]);
			}
		}
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
 * Crée un mot-clé s'il n'existe pas déjà dans le groupe.
 */
function fictionsv2_ajouter_mot($titre, $id_groupe, $type = '') {
	// Vérifie si le mot existe déjà dans le groupe
	if (sql_getfetsel('id_mot', 'spip_mots',
			'titre=' . sql_quote($titre) . ' AND id_groupe=' . intval($id_groupe) .
			($type ? " AND type=" . sql_quote($type) : ''))) {
		return;
	}

	$donnees = ['titre' => $titre, 'id_groupe' => intval($id_groupe)];
	if ($type) {
		$donnees['type'] = $type;
	}
	sql_insertq('spip_mots', $donnees);
}
