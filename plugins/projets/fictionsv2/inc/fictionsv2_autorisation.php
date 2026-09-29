<?php
/**
 * Fonctions de calcul des droits d'accès aux chapitres (fictionsv2).
 *
 * Centralise la logique d'autorisation actuellement éparpillée dans les
 * squelettes inclure/rubrique-cadavres.html et inclure/liste-cadavres-auteur*.html.
 * Ces fonctions lisent le contexte via session_get() — le contexte est écrit en
 * session par les squelettes via session_set() au début de la page.
 *
 * Usage squelette :
 *   #SET{id_auteur_fv2,#SESSION{id_auteur}|session_set{id_auteur_fv2}}
 *   #SET{max_cadavres_fv2,#GET{var_max_cadavres}|session_set{max_cadavres_fv2}}
 *   #SET{zone_fv2,#GET{var_id_zone}|session_set{zone_fv2}}
 *   #SET{webmaster_fv2,#SESSION{webmestre}|session_set{webmaster_fv2}}
 *   #SET{peut_lire,#ID_ARTICLE|LECTURE_DROITS{montre}}
 *   #SET{mode_aff,#ID_ARTICLE|LECTURE_DROITS{mode}}
 *   #SET{peut_ecrire,#ID_ARTICLE|ECRIRE_DROITS}
 *   #SET{est_mon_chapitre,#ID_ARTICLE|EST_AUTEUR_DROITS}
 *
 * Valeurs retournées par LECTURE_DROITS :
 *   'oui' / '' pour montre
 *   'visible' / 'verrouille' / 'ecriture' pour mode
 *
 * Valeur retournée par ECRIRE_DROITS :
 *   'oui' / ''
 *
 * Valeur retournée par EST_AUTEUR_DROITS :
 *   'oui' / ''
 *
 * @package fictionsv2
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/session');

/**
 * Lit les droits de lecture d'un chapitre pour l'utilisateur courant.
 *
 * Règles (issu de inclure/rubrique-cadavres.html) :
 *   webmestre : tout visible en mode 'visible'
 *   N-1       : toujours visible en mode 'visible'
 *   N (dernier, sur sa zone) : visible en mode 'ecriture'
 *   N (dernier, pas sur zone) : visible en mode 'verrouille'
 *   autres    : masqué
 *
 * @param int $id_article ID de l'article concerné
 * @return array {montre: bool, mode: string}
 */
function fictionsv2_lecture_droits(int $id_article): array {
	$id_auteur      = intval(session_get('id_auteur_fv2') ?: 0);
	$max_cadavres   = intval(session_get('max_cadavres_fv2') ?: 0);
	$id_zone        = intval(session_get('zone_fv2') ?: 0);
	$webmestre      = session_get('webmaster_fv2') === 'oui';

	if (!$id_auteur) {
		return ['montre' => false, 'mode' => ''];
	}

	// Webmestre voit tout, en mode visible
	if ($webmestre) {
		return ['montre' => true, 'mode' => 'visible'];
	}

	if (!$max_cadavres) {
		return ['montre' => false, 'mode' => ''];
	}

	// Rang 1-indexé de l'article dans sa rubrique (publie + prop)
	$id_rubrique = intval(sql_getfetsel('id_rubrique', 'spip_articles', "id_article=$id_article"));
	$pos = sql_countsel('spip_articles',
		"id_rubrique=$id_rubrique AND statut IN ('publie','prop') AND id_article<=$id_article");

	if (!$pos) {
		return ['montre' => false, 'mode' => ''];
	}

	$est_dernier       = ($pos == $max_cadavres);
	$est_avant_dernier = ($pos == $max_cadavres - 1);
	$est_sur_zone      = ($id_zone && $id_article == $id_zone);

	// N-1 : toujours visible
	if ($est_avant_dernier) {
		return ['montre' => true, 'mode' => 'visible'];
	}

	// N sur sa zone : visible (en cours d'écriture)
	if ($est_dernier && $est_sur_zone) {
		return ['montre' => true, 'mode' => 'ecriture'];
	}

	// N pas sur sa zone : verrouillé (dernière chance)
	if ($est_dernier && !$est_sur_zone) {
		return ['montre' => true, 'mode' => 'verrouille'];
	}

	// Hors de N : masqué
	return ['montre' => false, 'mode' => ''];
}

/**
 * Lit les droits d'écriture d'un chapitre pour l'utilisateur courant.
 *
 * Règles (issu de inclure/rubrique-cadavres.html) :
 *   webmestre + dernier : peut écrire
 *   sur sa zone + dernier : peut écrire
 *
 * @param int $id_article ID de l'article concerné
 * @return string 'oui' | ''
 */
function fictionsv2_ecriture_droits(int $id_article): string {
	$id_auteur      = intval(session_get('id_auteur_fv2') ?: 0);
	$max_cadavres   = intval(session_get('max_cadavres_fv2') ?: 0);
	$id_zone        = intval(session_get('zone_fv2') ?: 0);
	$webmestre      = session_get('webmaster_fv2') === 'oui';

	if (!$id_auteur || !$max_cadavres) {
		return '';
	}

	// Rang 1-indexé de l'article dans sa rubrique (publie + prop)
	$id_rubrique = intval(sql_getfetsel('id_rubrique', 'spip_articles', "id_article=$id_article"));
	$pos = sql_countsel('spip_articles',
		"id_rubrique=$id_rubrique AND statut IN ('publie','prop') AND id_article<=$id_article");

	if (!$pos) {
		return '';
	}

	$est_dernier       = ($pos == $max_cadavres);
	$est_sur_zone      = ($id_zone && $id_article == $id_zone);

	if ($webmestre && $est_dernier) {
		return 'oui';
	}

	if ($est_sur_zone && $est_dernier) {
		return 'oui';
	}

	return '';
}

/**
 * Vérifie si l'utilisateur courant est l'auteur d'un article.
 *
 * Utilisé dans liste-cadavres-auteur*.html pour déterminer le style du lien
 * (auteur vs verrouille).
 *
 * @param int $id_article ID de l'article concerné
 * @return string 'oui' | ''
 */
function fictionsv2_est_auteur_droits(int $id_article): string {
	$id_auteur = intval(session_get('id_auteur_fv2') ?: 0);
	if (!$id_auteur) {
		return '';
	}

	// Vérifier si cet article a un lien avec cet auteur dans spip_auteurs_liens
	$count = sql_countsel('spip_auteurs_liens',
		"id_auteur=$id_auteur AND objet='article' AND id_article=$id_article");
	return ($count > 0) ? 'oui' : '';
}
