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
include_spip('inc/fictionsv2_participants');

/**
 * La période d'écriture de l'année de l'histoire est-elle ouverte (#518) : entre
 * date de lancement et date de clôture. Année non configurée : pas de restriction.
 */
function fictionsv2_ecriture_ouverte(int $id_rubrique): bool {
	$annee = fictionsv2_annee_histoire($id_rubrique);
	return !$annee || !fictionsv2_annee_configuree($annee) || fictionsv2_phase_ecriture($annee) === 'ouverte';
}

/**
 * Auteur évalué : $qui (tableau auteur passé par autoriser()) ou, à défaut,
 * l'auteur connecté.
 *
 * @return array{0: int, 1: bool} [id_auteur, webmestre]
 */
function fictionsv2_droits_qui(?array $qui = null): array {
	if ($qui === null) {
		return [intval(session_get('id_auteur') ?: 0), session_get('webmestre') === 'oui'];
	}
	return [intval($qui['id_auteur'] ?? 0), ($qui['webmestre'] ?? '') === 'oui'];
}

/**
 * L'auteur est-il affecté à ce chapitre : lié (spip_auteurs_liens) à l'article du
 * chapitre (#524). C'est ce lien que pose le plan d'associations annuel (#521, #523) ;
 * en attendant, il s'ajoute à la main dans /ecrire (auteur de l'article). Les
 * participants passent d'une histoire à l'autre : un lien à la rubrique de
 * l'histoire (règle de fictions v1) ne convient pas.
 */
function fictionsv2_auteur_affecte_chapitre(int $id_auteur, int $id_article): bool {
	if (!$id_auteur || !$id_article) {
		return false;
	}
	return (bool) sql_countsel(
		'spip_auteurs_liens',
		'id_auteur=' . $id_auteur . " AND objet='article' AND id_objet=" . $id_article
	);
}

/**
 * Lit les droits de lecture d'un chapitre pour l'utilisateur courant.
 *
 * Règles :
 *   webmestre : tout visible, 'ecriture' sur le dernier chapitre
 *   N-1       : toujours visible en mode 'visible'
 *   N (dernier), auteur affecté au chapitre, période d'écriture ouverte : 'ecriture' (#518, #524)
 *   N (dernier), autre auteur : 'verrouille'
 *   autres    : masqué
 *
 * @param int $id_article ID de l'article concerné
 * @return array {montre: bool, mode: string}
 */
function fictionsv2_lecture_droits(int $id_article, ?array $qui = null): array {
	[$id_auteur, $webmestre] = fictionsv2_droits_qui($qui);

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
	// Hors période d'écriture (#518), le participant affecté ne peut plus écrire
	$est_affecte       = fictionsv2_auteur_affecte_chapitre($id_auteur, $id_article)
		&& fictionsv2_ecriture_ouverte($id_rubrique);

	// Webmestre : tout visible, et il peut écrire le dernier chapitre (règle d'origine,
	// le refactor #441 l'avait laissé en simple lecture)
	if ($webmestre) {
		return ['montre' => true, 'mode' => $est_dernier ? 'ecriture' : 'visible'];
	}

	// N-1 : toujours visible
	if ($est_avant_dernier) {
		return ['montre' => true, 'mode' => 'visible'];
	}

	// N, auteur affecté au chapitre : en cours d'écriture
	if ($est_dernier && $est_affecte) {
		return ['montre' => true, 'mode' => 'ecriture'];
	}

	// N, autre auteur : verrouillé
	if ($est_dernier && !$est_affecte) {
		return ['montre' => true, 'mode' => 'verrouille'];
	}

	// Hors de N : masqué
	return ['montre' => false, 'mode' => ''];
}

/**
 * Lit les droits d'écriture d'un chapitre pour l'utilisateur courant.
 *
 * Règles :
 *   webmestre + dernier : peut écrire
 *   auteur affecté au chapitre + dernier + période ouverte : peut écrire (#518, #524)
 *
 * @param int $id_article ID de l'article concerné
 * @return string 'oui' | ''
 */
function fictionsv2_ecriture_droits(int $id_article, ?array $qui = null): string {
	[$id_auteur, $webmestre] = fictionsv2_droits_qui($qui);

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
	$est_affecte       = fictionsv2_auteur_affecte_chapitre($id_auteur, $id_article)
		&& fictionsv2_ecriture_ouverte($id_rubrique);

	if ($webmestre && $est_dernier) {
		return 'oui';
	}

	if ($est_affecte && $est_dernier) {
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
function fictionsv2_est_auteur_droits(int $id_article, ?array $qui = null): string {
	[$id_auteur] = fictionsv2_droits_qui($qui);
	if (!$id_auteur) {
		return '';
	}

	// Vérifier si cet article a un lien avec cet auteur dans spip_auteurs_liens
	$count = sql_countsel('spip_auteurs_liens',
		"id_auteur=$id_auteur AND objet='article' AND id_objet=$id_article");
	return ($count > 0) ? 'oui' : '';
}
