<?php

// annee_rub, balise_ANNEE_SCOLAIRE_dist, balise_ANNEE_ACTUELLE_dist, afficher_options_date
// sont définis par le plugin ccn (ccn_fonctions.php)

include_spip('base/abstract_sql');

/**
 * Retourne l'ID du mot-clé correspondant au titre donné, avec cache par requête.
 * Utilisable comme filtre SPIP : [(#VALEUR|fictionsv2_id_mot)]
 */
function fictionsv2_id_mot(string $titre_mot): int {
	static $cache = [];
	if (!array_key_exists($titre_mot, $cache)) {
		$cache[$titre_mot] = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	}
	return $cache[$titre_mot];
}

/**
 * Retourne l'ID de la première rubrique (à tout niveau) portant le mot-clé donné.
 * Utilisable comme filtre SPIP : [(#VALEUR|fictionsv2_id_rubrique_a_mot)]
 */
function fictionsv2_id_rubrique_a_mot(string $titre_mot): int {
	static $cache = [];

	if (array_key_exists($titre_mot, $cache)) {
		return $cache[$titre_mot];
	}

	$id_mot = fictionsv2_id_mot($titre_mot);
	if (!$id_mot) {
		return $cache[$titre_mot] = 0;
	}

	return $cache[$titre_mot] = (int) sql_getfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_mots_liens AS ml'],
		['ml.id_objet=r.id_rubrique', 'ml.objet=' . sql_quote('rubrique'), 'ml.id_mot=' . intval($id_mot)],
		'',
		'r.id_rubrique',
		'0,1'
	);
}

// Si balise_FIN_dist = false -> affichage de la grille sur la page d'accueil
// Si balise_FIN_dist = true -> affichage des couvertures et liens pdf sur la page d'accueil

function balise_FIN_dist($p) {
    $p->code = "'true'";
    return $p;
}

// Si balise_LECTURE_dist = false -> les textes sont masqués dans la vue lecture
// Si balise_LECTURE_dist = true -> les textes sont affichés dans la vue lecture

function balise_LECTURE_dist($p) {
    $p->code = "'true'";
    return $p;
}

/**
 * Masque les $nbDeCaracteresATronquerALaFin derniers caractères d'un texte
 * en remplaçant toutes les lettres (Unicode) par 'X'. Retourne une chaîne vide
 * si le texte est plus court que le seuil.
 *
 * Utilisable comme filtre SPIP : [(#DESCRIPTIF|textebrut|masquerTexteChapitre{400})]
 */
function masquerTexteChapitre(string $texteAMasquer = '', int $nbDeCaracteresATronquerALaFin = 325): string {
    if (mb_strlen($texteAMasquer) < $nbDeCaracteresATronquerALaFin) {
        return '';
    }
    $texteTronque = mb_substr($texteAMasquer, 0, mb_strlen($texteAMasquer) - $nbDeCaracteresATronquerALaFin);

    return preg_replace('/\p{L}/u', 'X', $texteTronque);
}

/**
 * Retourne les $nbDeDerniersCaracteresAAfficher derniers caractères d'un texte,
 * précédés de $chaineAConcatenerAuDebut. Retourne le texte complet si sa longueur
 * est inférieure au seuil.
 *
 * Utilisable comme filtre SPIP : [(#DESCRIPTIF|textebrut|recupererDernieresLignesChapitres{400})]
 */
function recupererDernieresLignesChapitres(string $texteChapitre = '', int $nbDeDerniersCaracteresAAfficher = 325, string $chaineAConcatenerAuDebut = '(...)'): string {
    if (mb_strlen($texteChapitre) < $nbDeDerniersCaracteresAAfficher) {
        return $texteChapitre;
    }
    return $chaineAConcatenerAuDebut . mb_substr($texteChapitre, -$nbDeDerniersCaracteresAAfficher);
}

/**
 * Balise PAGE : retourne le nom de la page SPIP courante.
 * Détecté via $_GET['page'] ou $_SERVER['REQUEST_URI'] en fallback.
 * Utilisable dans les squelettes : [(#PAGE)]
 *
 * Pages supportées : sommaire, page, rubrique, article, forum, forum_reponse,
 *                    lecture, lecture-texte, lecture-script
 */
function balise_PAGE_dist($p) {
	$page = '';

	// Via $_GET['page']
	if (isset($_GET['page']) && is_string($_GET['page']) && $_GET['page'] !== '') {
		$page = $_GET['page'];
	}
	// Via REQUEST_URI fallback
	else {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		$uri = parse_url($uri, PHP_URL_QUERY) ?? '';
		if (preg_match('/page=([^&]+)/', $uri, $m)) {
			$page = $m[1];
		}
	}

	$p->result = $page;
	return $p;
}

/**
 * Filtre fictionsv2_js_page : mappe un nom de page SPIP vers le nom de fichier JS.
 * Utilisable comme filtre SPIP : [(#PAGE|fictionsv2_js_page)]
 * Ex: sommaire -> sommaire, lecture-texte -> lecture-texte, forum -> forum
 */
function fictionsv2_js_page($page) {
	// Mapping explicite page -> fichier JS (certains noms de page ≠ nom de fichier)
	$map = [
		'sommaire' => 'sommaire',
		'page' => 'page',
		'rubrique' => 'rubrique',
		'article' => 'page', // les articles utilisent le squelette page.html
		'forum' => 'forum',
		'forum_reponse' => 'forum',
		'lecture' => 'lecture',
		'lecture-texte' => 'lecture',
		'lecture-script' => 'lecture-script',
		'lecture_forum' => 'forum',
	];

	return isset($map[$page]) ? $map[$page] : $page;
}

/**
 * Formate une année scolaire : "2025" -> "2025/2026".
 * Utilisable comme filtre SPIP : [(#TITRE|filtre_fictionsv2_annee_label)]
 */
function filtre_fictionsv2_annee_label($annee) {
	$annee = intval($annee);
	if ($annee < 2000 || $annee > 2100) {
		return $annee;
	}
	return $annee . '/' . ($annee + 1);
}

/**
 * Retourne l'ID de la rubrique de l'année scolaire donnée (titre "2026"), avec cache
 * par requête. Même recherche que les squelettes ({titre==#EVAL{_ANNEE_SCOLAIRE}}{tout}) :
 * la rubrique de l'année est cherchée où qu'elle soit (ex: sous "Collèges").
 */
function fictionsv2_id_rubrique_annee($annee): int {
	static $cache = [];
	$annee = (string) intval($annee);
	// 0 non mis en cache : la rubrique peut être créée plus loin dans la même requête
	// (cf fictionsv2_assurer_structure_annee()).
	if (empty($cache[$annee])) {
		$cache[$annee] = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre=' . sql_quote($annee), '', 'id_rubrique', '0,1');
	}
	return $cache[$annee];
}

/**
 * Retourne l'ID de l'article "chapitre 1" commun à toutes les histoires d'une année :
 * article de la rubrique de l'année portant le mot-clé "chapitre1". À partir de
 * _FICTIONSV2_ANNEE_CHAPITRE1_COMMUN, le premier chapitre n'est plus copié dans chaque
 * histoire mais affiché depuis cet article unique. 0 si aucun (années antérieures).
 * Utilisable comme filtre SPIP : [(#ANNEE_SCOLAIRE|fictionsv2_id_chapitre1)]
 */
function fictionsv2_id_chapitre1($annee): int {
	static $cache = [];
	$annee = intval($annee);
	if (array_key_exists($annee, $cache)) {
		return $cache[$annee];
	}
	$id_mot = fictionsv2_id_mot('chapitre1');
	$id_annee = fictionsv2_id_rubrique_annee($annee);
	if ($annee < _FICTIONSV2_ANNEE_CHAPITRE1_COMMUN || !$id_mot || !$id_annee) {
		return $cache[$annee] = 0;
	}
	return $cache[$annee] = (int) sql_getfetsel(
		'a.id_article',
		['spip_articles AS a', 'spip_mots_liens AS ml'],
		[
			'ml.id_objet=a.id_article',
			'ml.objet=' . sql_quote('article'),
			'ml.id_mot=' . intval($id_mot),
			'a.id_rubrique=' . intval($id_annee),
			'a.statut=' . sql_quote('publie'),
		],
		'',
		'a.id_article',
		'0,1'
	);
}

/**
 * Chapitre 1 commun de l'histoire donnée : l'année est le titre de sa rubrique parente
 * (et non _ANNEE_SCOLAIRE, qui suit le cookie du sélecteur d'année).
 * Utilisable comme filtre SPIP : [(#ID_RUBRIQUE|fictionsv2_id_chapitre1_histoire)]
 */
function fictionsv2_id_chapitre1_histoire($id_rubrique): int {
	$annee = sql_getfetsel(
		'p.titre',
		['spip_rubriques AS r', 'spip_rubriques AS p'],
		['p.id_rubrique=r.id_parent', 'r.id_rubrique=' . intval($id_rubrique)]
	);
	return preg_match('/^\d{4}$/', (string) $annee) ? fictionsv2_id_chapitre1($annee) : 0;
}

/**
 * Nombre de chapitres écrits ou en cours d'écriture (publie + prop) d'une histoire.
 * Utilisable comme filtre SPIP : [(#ID_RUBRIQUE|fictionsv2_nb_chapitres_histoire)]
 */
function fictionsv2_nb_chapitres_histoire($id_rubrique): int {
	return (int) sql_countsel('spip_articles', [
		'id_rubrique=' . intval($id_rubrique),
		sql_in('statut', ['publie', 'prop']),
	]);
}
