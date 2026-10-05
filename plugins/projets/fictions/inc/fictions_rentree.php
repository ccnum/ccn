<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Structure d'une année scolaire fictions, créée à la rentrée par
 * genie/fictions_rentree_annee.php (équivalent de thematique_assurer_structure_annee()) :
 *
 * <rubrique-contenant-annees> ("Collèges")
 *   └ <année> ("2026")
 *       ├ articles footer-blog-auteur / footer-espace-doc / footer-forum : copiés de
 *       │ l'année précédente (liens de config du footer, dans le sous-titre)
 *       ├ Présentation (mot presentation), Prologue (mot prologue) : vides, en prepa
 *       └ 1/ Chapitre 1 (mot chapitre1) : chapitre 1 commun, vide, en prepa
 *
 * Appelée aussi dès qu'une rubrique d'année est titrée à la main (cf
 * fictions_post_edition_rubrique_annee()).
 *
 * Les histoires ("01. Histoire 01"...) ne sont pas créées ici mais depuis la liste
 * des participants de l'année (#520, cf inc/fictions_histoires.php).
 **/

include_spip('fictions_fonctions');

// Mots-clés des articles de config du footer, repris tels quels d'une année sur l'autre.
const FICTIONSV2_MOTS_FOOTER = ['footer-blog-auteur', 'footer-espace-doc', 'footer-forum'];

/**
 * Crée (si absente) la rubrique de l'année et ses articles. Idempotent : chaque article
 * est identifié par son mot-clé dans la rubrique de l'année et n'est jamais recréé.
 *
 * @return array{0: int, 1: bool} [id_rubrique de l'année (0 si échec), tout est OK]
 */
function fictions_assurer_structure_annee(int $annee): array {
	$id_annee = fictions_id_rubrique_annee($annee);
	$id_precedente = fictions_id_rubrique_annee($annee - 1);

	if (!$id_annee) {
		$id_parent = fictions_id_rubrique_a_mot('rubrique-contenant-annees');
		if (!$id_parent && $id_precedente) {
			$id_parent = (int) sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . $id_precedente);
		}
		include_spip('action/editer_objet');
		$id_annee = (int) objet_inserer('rubrique', $id_parent, ['titre' => (string) $annee]);
		if (!$id_annee) {
			spip_log("fictions_rentree_annee $annee : échec de création de la rubrique de l'année", 'fictions' . _LOG_ERREUR);
			return [0, false];
		}
		sql_updateq('spip_rubriques', ['statut' => 'publie', 'date' => date('Y-m-d H:i:s')], 'id_rubrique=' . $id_annee);
		spip_log("fictions_rentree_annee $annee : rubrique de l'année #$id_annee créée sous #$id_parent", 'fictions');
	}

	$ok = true;

	// Année tout juste mise en place (pas encore d'article de présentation) : elle
	// s'ouvre en écriture. Sans le mot année_en_ecriture, l'accueil (sommaire.html)
	// l'affiche comme une année terminée (une couverture et un PDF par histoire),
	// donc des cadres vides. Posé une seule fois : un admin peut le retirer en fin
	// d'année pour passer à l'affichage des couvertures, la tâche quotidienne ne le
	// remet pas.
	if (!fictions_id_article_a_mot('presentation', $id_annee)) {
		$ok = fictions_marquer_annee_en_ecriture($id_annee) && $ok;
	}

	foreach (FICTIONSV2_MOTS_FOOTER as $titre_mot) {
		$id_source = $id_precedente ? fictions_id_article_a_mot($titre_mot, $id_precedente) : 0;
		if (!$id_source) {
			spip_log("fictions_rentree_annee $annee : pas d'article '$titre_mot' en " . ($annee - 1) . ', non copié', 'fictions');
			continue;
		}
		$source = sql_fetsel('surtitre,titre,soustitre,descriptif,chapo,texte,ps,statut', 'spip_articles', 'id_article=' . $id_source);
		$ok = fictions_assurer_article_a_mot($titre_mot, $id_annee, $source) && $ok;
	}

	$ok = fictions_assurer_article_a_mot('presentation', $id_annee, [
		'titre' => _T('fictions:titre_presentation_defaut'),
		'statut' => 'prepa',
	]) && $ok;
	$ok = fictions_assurer_article_a_mot('prologue', $id_annee, [
		'titre' => _T('fictions:titre_prologue_defaut'),
		'statut' => 'prepa',
	]) && $ok;
	// À publier une fois rédigé : fictions_id_chapitre1() ne retient qu'un article publié.
	$ok = fictions_assurer_article_a_mot('chapitre1', $id_annee, [
		'titre' => _T('fictions:titre_chapitre1_defaut'),
		'statut' => 'prepa',
	]) && $ok;

	return [$id_annee, $ok];
}

/**
 * Associe le mot année_en_ecriture à la rubrique d'année $id_annee (sans effet s'il y
 * est déjà).
 */
function fictions_marquer_annee_en_ecriture(int $id_annee): bool {
	$id_mot = fictions_id_mot('année_en_ecriture');
	if (!$id_mot) {
		spip_log("fictions_rentree_annee : mot-clé 'année_en_ecriture' introuvable, année #$id_annee non marquée", 'fictions' . _LOG_ERREUR);
		return false;
	}
	include_spip('action/editer_liens');
	objet_associer(['mot' => $id_mot], ['rubrique' => $id_annee]);
	spip_log("fictions_rentree_annee : année #$id_annee marquée année_en_ecriture", 'fictions');
	return true;
}

/**
 * Rubrique qui contient les rubriques d'années : celle taguée rubrique-contenant-annees,
 * ou à défaut le parent d'une rubrique d'année existante ("Collèges" sur fictions).
 */
function fictions_est_rubrique_des_annees(int $id_rubrique): bool {
	$id_tag = fictions_id_rubrique_a_mot('rubrique-contenant-annees');
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
function fictions_id_article_a_mot(string $titre_mot, int $id_rubrique): int {
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
function fictions_assurer_article_a_mot(string $titre_mot, int $id_rubrique, array $champs): bool {
	if (fictions_id_article_a_mot($titre_mot, $id_rubrique)) {
		return true;
	}
	$id_mot = fictions_id_mot($titre_mot);
	if (!$id_mot) {
		spip_log("fictions_rentree_annee : mot-clé '$titre_mot' introuvable, article non créé", 'fictions' . _LOG_ERREUR);
		return false;
	}
	// Champs passés à l'insertion (statut compris) : pas de objet_instituer(), qui
	// dépend de autoriser('publierdans') sans vrai visiteur en contexte cron.
	include_spip('action/editer_article');
	include_spip('action/editer_liens');
	$id_article = (int) article_inserer($id_rubrique, $champs);
	if (!$id_article) {
		spip_log("fictions_rentree_annee : échec de création de l'article '$titre_mot'", 'fictions' . _LOG_ERREUR);
		return false;
	}
	objet_associer(['mots' => $id_mot], ['articles' => $id_article]);
	spip_log("fictions_rentree_annee : article #$id_article ('$titre_mot') créé dans #$id_rubrique", 'fictions');
	return true;
}
