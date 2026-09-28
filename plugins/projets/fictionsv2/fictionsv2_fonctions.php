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
	// Via REQUEST_URI fallback : extraire le nom de la page depuis l'URL
	else {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		// SPIP URL : /spip.php?page=lecture&id_article=123
		if (preg_match('#[?&]page=([^&\s#]+)#', $uri, $m)) {
			$page = $m[1];
		}
	}

	$p->code = '$page';
	$p->is_cache = false;
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
