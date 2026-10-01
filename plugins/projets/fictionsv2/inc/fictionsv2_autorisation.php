<?php
/**
 * Fonctions de calcul des droits d'accès aux chapitres (fictionsv2).
 *
 * Centralise la logique d'autorisation des squelettes inclure/rubrique-cadavres.html
 * et inclure/liste-cadavres-auteur*.html (#441).
 *
 * Autonomes : l'auteur connecté et son statut webmestre sont lus dans sa session,
 * le nombre de chapitres de l'histoire est compté ici. Le refactor #441 faisait
 * transiter ce contexte par #SET{x,valeur|session_set{x}}, qui inverse les
 * arguments de session_set($nom, $valeur) : le contexte restait vide et aucun
 * chapitre n'était affiché ni rédigeable.
 *
 * Usage squelette :
 *   #ID_ARTICLE|LECTURE_DROITS{montre}  'oui' / ''
 *   #ID_ARTICLE|LECTURE_DROITS{mode}    'visible' / 'verrouille' / 'ecriture'
 *   #ID_ARTICLE|ECRIRE_DROITS           'oui' / ''
 *   #ID_ARTICLE|EST_AUTEUR_DROITS       'oui' / ''
 *
 * @package fictionsv2
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/session');
include_spip('fictionsv2_fonctions');

/**
 * L'auteur est-il "sur sa zone" pour cet article : lié (spip_auteurs_liens) à la
 * rubrique de l'histoire qui contient l'article. Règle d'origine, avant #441
 * (var_id_zone == ID_RUBRIQUE) : le refactor comparait la rubrique liée à l'id de
 * l'article, jamais égaux, et le mode écriture ne s'activait pour personne. Tous les
 * liens de l'auteur comptent, pas seulement le premier.
 */
function fictionsv2_auteur_sur_zone(int $id_auteur, int $id_rubrique): bool {
	if (!$id_auteur || !$id_rubrique) {
		return false;
	}
	return (bool) sql_countsel(
		'spip_auteurs_liens',
		'id_auteur=' . $id_auteur . " AND objet='rubrique' AND id_objet=" . $id_rubrique
	);
}

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
	$id_auteur = intval(session_get('id_auteur') ?: 0);
	$webmestre = session_get('webmestre') === 'oui';

	if (!$id_auteur) {
		return ['montre' => false, 'mode' => ''];
	}

	// Rang 1-indexé de l'article dans sa rubrique (publie + prop)
	$id_rubrique = intval(sql_getfetsel('id_rubrique', 'spip_articles', "id_article=$id_article"));
	$max_cadavres = fictionsv2_nb_chapitres_histoire($id_rubrique);
	$pos = sql_countsel('spip_articles',
		"id_rubrique=$id_rubrique AND statut IN ('publie','prop') AND id_article<=$id_article");

	if (!$pos) {
		return ['montre' => false, 'mode' => ''];
	}

	$est_dernier       = ($pos == $max_cadavres);
	$est_avant_dernier = ($pos == $max_cadavres - 1);
	$est_sur_zone      = fictionsv2_auteur_sur_zone($id_auteur, $id_rubrique);

	// Webmestre : tout visible, et il peut écrire le dernier chapitre (règle d'origine,
	// le refactor #441 l'avait laissé en simple lecture)
	if ($webmestre) {
		return ['montre' => true, 'mode' => $est_dernier ? 'ecriture' : 'visible'];
	}

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
	$id_auteur = intval(session_get('id_auteur') ?: 0);
	$webmestre = session_get('webmestre') === 'oui';

	if (!$id_auteur) {
		return '';
	}

	// Rang 1-indexé de l'article dans sa rubrique (publie + prop)
	$id_rubrique = intval(sql_getfetsel('id_rubrique', 'spip_articles', "id_article=$id_article"));
	$max_cadavres = fictionsv2_nb_chapitres_histoire($id_rubrique);
	$pos = sql_countsel('spip_articles',
		"id_rubrique=$id_rubrique AND statut IN ('publie','prop') AND id_article<=$id_article");

	if (!$pos) {
		return '';
	}

	$est_dernier       = ($pos == $max_cadavres);
	$est_sur_zone      = fictionsv2_auteur_sur_zone($id_auteur, $id_rubrique);

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
	$id_auteur = intval(session_get('id_auteur') ?: 0);
	if (!$id_auteur) {
		return '';
	}

	// Vérifier si cet article a un lien avec cet auteur dans spip_auteurs_liens
	$count = sql_countsel('spip_auteurs_liens',
		"id_auteur=$id_auteur AND objet='article' AND id_objet=$id_article");
	return ($count > 0) ? 'oui' : '';
}
