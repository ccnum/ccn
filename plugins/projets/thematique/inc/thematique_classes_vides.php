<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Nettoyage des rubriques de classe VIDES sous "Travail des classes" d'une année
 * scolaire : classes créées à tort par la connexion SSO de comptes ENT ne
 * participant pas au projet (avant le correctif de
 * thematique_cioidc_resoudre_liens_rubriques()), qui apparaissaient comme
 * "participants" dans le footer (noisettes/menu_classes.html).
 *
 * Interface : prive/squelettes/contenu/thematique_classes_vides.html (webmestres),
 * suppression : action/thematique_supprimer_classes_vides.php.
 */

/**
 * Rubriques de classe (enfants directs des rubriques "Travail des classes", mot-clé
 * travail_en_cours, de la rubrique racine de l'année), avec leur contenu.
 *
 * @param string $annee ex: "2026"
 * @return array Liste de ['id_rubrique', 'titre', 'maj', 'contenu' => [type => nb], 'nb_contenu', 'vide' => bool]
 */
function thematique_classes_annee_avec_contenu($annee) {
	$id_annee = (int) sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre=' . sql_quote((string) $annee) . ' AND id_parent=0'
	);
	$id_mot = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote('travail_en_cours'));
	if (!$id_annee || !$id_mot) {
		return [];
	}

	$conteneurs = array_column(sql_allfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_mots_liens AS ml'],
		[
			'ml.id_objet=r.id_rubrique',
			'ml.objet=' . sql_quote('rubrique'),
			'ml.id_mot=' . $id_mot,
			'r.id_parent=' . $id_annee,
		]
	), 'id_rubrique');
	if (!$conteneurs) {
		return [];
	}

	$classes = [];
	foreach (sql_allfetsel(
		'id_rubrique, titre, maj',
		'spip_rubriques',
		sql_in('id_parent', $conteneurs),
		'',
		'id_rubrique'
	) as $classe) {
		$contenu = array_filter(thematique_classe_compter_contenu((int) $classe['id_rubrique']));
		$classes[] = [
			'id_rubrique' => (int) $classe['id_rubrique'],
			'titre' => $classe['titre'],
			'maj' => $classe['maj'],
			'contenu' => $contenu,
			'nb_contenu' => array_sum($contenu),
			'vide' => !$contenu,
		];
	}
	return $classes;
}

/**
 * Tout ce qui empêche de considérer une classe comme vide : sous-rubriques,
 * articles de TOUS statuts (même poubelle, pour ne jamais les orpheliner),
 * documents hors logos, et ce que les plugins déclarent via
 * objet_compte_enfants (cf autoriser_rubrique_supprimer_dist()).
 *
 * @param int $id_rubrique
 * @return array [type => nb]
 */
function thematique_classe_compter_contenu($id_rubrique) {
	$id_rubrique = (int) $id_rubrique;
	$compte = [
		'rubriques' => sql_countsel('spip_rubriques', 'id_parent=' . $id_rubrique),
		'articles' => sql_countsel('spip_articles', 'id_rubrique=' . $id_rubrique),
		'documents' => sql_countsel(
			'spip_documents AS D JOIN spip_documents_liens AS L ON D.id_document=L.id_document',
			'L.id_objet=' . $id_rubrique . " AND L.objet='rubrique' AND D.mode NOT IN('logoon', 'logooff')"
		),
	];
	return pipeline(
		'objet_compte_enfants',
		['args' => ['objet' => 'rubrique', 'id_objet' => $id_rubrique], 'data' => $compte]
	);
}

/**
 * Supprime les classes vides de l'année (liste recalculée ici, jamais passée par la
 * requête : seules des rubriques de classe vides peuvent être touchées).
 *
 * Suppression directe plutôt que action_supprimer_rubrique_dist() : celle-ci passe
 * en rédacteur (1comite) tout auteur dont c'était la seule rubrique liée, ce qui
 * promouvrait les élèves/visiteurs rattachés à ces classes. Les auteurs eux-mêmes
 * ne sont pas touchés, seuls leurs liens vers la rubrique supprimée.
 *
 * @param string $annee
 * @return int nombre de classes supprimées
 */
function thematique_supprimer_classes_vides($annee) {
	$nb = 0;
	foreach (thematique_classes_annee_avec_contenu($annee) as $classe) {
		if (!$classe['vide']) {
			continue;
		}
		$id_rubrique = $classe['id_rubrique'];
		sql_delete('spip_auteurs_liens', "objet='rubrique' AND id_objet=" . $id_rubrique);
		sql_delete('spip_mots_liens', "objet='rubrique' AND id_objet=" . $id_rubrique);
		sql_delete('spip_documents_liens', "objet='rubrique' AND id_objet=" . $id_rubrique);
		sql_delete('spip_rubriques', 'id_rubrique=' . $id_rubrique);
		spip_log('classe vide supprimée id_rubrique=' . $id_rubrique . ' titre=' . $classe['titre'], 'thematique');
		$nb++;
	}

	if ($nb) {
		effacer_meta('date_calcul_rubriques');
		include_spip('inc/rubriques');
		calculer_rubriques();
		include_spip('inc/invalideur');
		suivre_invalideur('1');
	}
	return $nb;
}
