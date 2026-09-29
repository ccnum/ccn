<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Structure d'une année scolaire fictionsv2, créée à la rentrée par
 * genie/fictionsv2_rentree_annee.php (équivalent de thematique_assurer_structure_annee()) :
 *
 * <rubrique-contenant-annees> ("Collèges")
 *   └ <année> ("2026")
 *       ├ articles footer-blog-auteur / footer-espace-doc / footer-forum : copiés de
 *       │ l'année précédente (liens de config du footer, dans le sous-titre)
 *       ├ Présentation (mot presentation), Prologue (mot prologue) : vides, en prepa
 *       └ 1/ Chapitre 1 (mot chapitre1) : chapitre 1 commun, vide, en prepa
 *
 * Appelée aussi dès qu'une rubrique d'année est titrée à la main (cf
 * fictionsv2_post_edition_rubrique_annee()).
 *
 * Les histoires ("01. Histoire 01"...) ne sont pas créées ici mais à la connexion
 * de chaque prof inscrit (cf inc/fictionsv2_cioidc.php).
 **/

include_spip('fictionsv2_fonctions');

// Mots-clés des articles de config du footer, repris tels quels d'une année sur l'autre.
const FICTIONSV2_MOTS_FOOTER = ['footer-blog-auteur', 'footer-espace-doc', 'footer-forum'];

/**
 * Crée (si absente) la rubrique de l'année et ses articles. Idempotent : chaque article
 * est identifié par son mot-clé dans la rubrique de l'année et n'est jamais recréé.
 *
 * @return array{0: int, 1: bool} [id_rubrique de l'année (0 si échec), tout est OK]
 */
function fictionsv2_assurer_structure_annee(int $annee): array {
	$id_annee = fictionsv2_id_rubrique_annee($annee);
	$id_precedente = fictionsv2_id_rubrique_annee($annee - 1);

	if (!$id_annee) {
		$id_parent = fictionsv2_id_rubrique_a_mot('rubrique-contenant-annees');
		if (!$id_parent && $id_precedente) {
			$id_parent = (int) sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . $id_precedente);
		}
		include_spip('action/editer_objet');
		$id_annee = (int) objet_inserer('rubrique', $id_parent, ['titre' => (string) $annee]);
		if (!$id_annee) {
			spip_log("fictionsv2_rentree_annee $annee : échec de création de la rubrique de l'année", 'fictionsv2' . _LOG_ERREUR);
			return [0, false];
		}
		sql_updateq('spip_rubriques', ['statut' => 'publie', 'date' => date('Y-m-d H:i:s')], 'id_rubrique=' . $id_annee);
		spip_log("fictionsv2_rentree_annee $annee : rubrique de l'année #$id_annee créée sous #$id_parent", 'fictionsv2');
	}

	$ok = true;

	foreach (FICTIONSV2_MOTS_FOOTER as $titre_mot) {
		$id_source = $id_precedente ? fictionsv2_id_article_a_mot($titre_mot, $id_precedente) : 0;
		if (!$id_source) {
			spip_log("fictionsv2_rentree_annee $annee : pas d'article '$titre_mot' en " . ($annee - 1) . ', non copié', 'fictionsv2');
			continue;
		}
		$source = sql_fetsel('surtitre,titre,soustitre,descriptif,chapo,texte,ps,statut', 'spip_articles', 'id_article=' . $id_source);
		$ok = fictionsv2_assurer_article_a_mot($titre_mot, $id_annee, $source) && $ok;
	}

	$ok = fictionsv2_assurer_article_a_mot('presentation', $id_annee, [
		'titre' => _T('fictionsv2:titre_presentation_defaut'),
		'statut' => 'prepa',
	]) && $ok;
	$ok = fictionsv2_assurer_article_a_mot('prologue', $id_annee, [
		'titre' => _T('fictionsv2:titre_prologue_defaut'),
		'statut' => 'prepa',
	]) && $ok;
	// À publier une fois rédigé : fictionsv2_id_chapitre1() ne retient qu'un article publié.
	$ok = fictionsv2_assurer_article_a_mot('chapitre1', $id_annee, [
		'titre' => _T('fictionsv2:titre_chapitre1_defaut'),
		'statut' => 'prepa',
	]) && $ok;

	return [$id_annee, $ok];
}

/**
 * Rubrique qui contient les rubriques d'années : celle taguée rubrique-contenant-annees,
 * ou à défaut le parent d'une rubrique d'année existante ("Collèges" sur fictions).
 */
function fictionsv2_est_rubrique_des_annees(int $id_rubrique): bool {
	$id_tag = fictionsv2_id_rubrique_a_mot('rubrique-contenant-annees');
	if ($id_tag) {
		return $id_rubrique === $id_tag;
	}
	return (bool) sql_countsel('spip_rubriques', [
		'id_parent=' . $id_rubrique,
		'titre REGEXP ' . sql_quote('^[0-9]{4}$'),
	]);
}

/**
 * ID de l'article de la rubrique $id_rubrique portant le mot-clé $titre_mot (0 si aucun).
 */
function fictionsv2_id_article_a_mot(string $titre_mot, int $id_rubrique): int {
	return (int) sql_getfetsel(
		'a.id_article',
		['spip_articles AS a', 'spip_mots_liens AS ml', 'spip_mots AS m'],
		[
			'ml.id_objet=a.id_article',
			'ml.objet=' . sql_quote('article'),
			'm.id_mot=ml.id_mot',
			'm.titre=' . sql_quote($titre_mot),
			'a.id_rubrique=' . $id_rubrique,
		],
		'',
		'a.id_article',
		'0,1'
	);
}

/**
 * Crée dans $id_rubrique l'article $champs portant le mot-clé $titre_mot, s'il n'y en a
 * pas déjà un. Retourne false si le mot-clé n'existe pas ou si la création échoue.
 */
function fictionsv2_assurer_article_a_mot(string $titre_mot, int $id_rubrique, array $champs): bool {
	if (fictionsv2_id_article_a_mot($titre_mot, $id_rubrique)) {
		return true;
	}
	$id_mot = fictionsv2_id_mot($titre_mot);
	if (!$id_mot) {
		spip_log("fictionsv2_rentree_annee : mot-clé '$titre_mot' introuvable, article non créé", 'fictionsv2' . _LOG_ERREUR);
		return false;
	}
	// Champs passés à l'insertion (statut compris) : pas de objet_instituer(), qui
	// dépend de autoriser('publierdans') sans vrai visiteur en contexte cron.
	include_spip('action/editer_article');
	include_spip('action/editer_liens');
	$id_article = (int) article_inserer($id_rubrique, $champs);
	if (!$id_article) {
		spip_log("fictionsv2_rentree_annee : échec de création de l'article '$titre_mot'", 'fictionsv2' . _LOG_ERREUR);
		return false;
	}
	objet_associer(['mots' => $id_mot], ['articles' => $id_article]);
	spip_log("fictionsv2_rentree_annee : article #$id_article ('$titre_mot') créé dans #$id_rubrique", 'fictionsv2');
	return true;
}
