<?php
/**
 * Plugin Fictionsv2 – autorisations personnalisées.
 *
 * Enregistre les autorisations :
 *   #AUTORISER{lirechapitre,article,#ID_ARTICLE}
 *   #AUTORISER{ecrirechapitre,article,#ID_ARTICLE}
 *   #AUTORISER{est_auteur,article,#ID_ARTICLE}
 *
 * Le contexte (zone, max_cadavres, webmestre) est passé via session_set()
 * par les squelettes avant l'appel de #AUTORISER.
 *
 * Ce fichier est chargé automatiquement par SPIP s'il est dans la racine
 * du plugin. Il n'est pas déclaré dans paquet.xml.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/session');
include_spip('inc/fictionsv2_autorisation');

/**
 * Autorisation personnalisée : lirechapitre.
 *
 * @param string $faire  Action demandée ('lirechapitre')
 * @param string $type   Type d'objet ('article')
 * @param int    $id     ID de l'article
 * @param array  $qui    Auteur demandeur
 * @param array  $opt    Options
 * @return bool
 */
function autoriser_lirechapitre_article_dist($faire, $type, $id, $qui, $opt) {
	$result = fictionsv2_lecture_droits($id);
	return $result['montre'];
}

/**
 * Autorisation personnalisée : ecrirechapitre.
 *
 * @param string $faire  Action demandée ('ecrirechapitre')
 * @param string $type   Type d'objet ('article')
 * @param int    $id     ID de l'article
 * @param array  $qui    Auteur demandeur
 * @param array  $opt    Options
 * @return bool
 */
function autoriser_ecrirechapitre_article_dist($faire, $type, $id, $qui, $opt) {
	return (bool) fictionsv2_ecriture_droits($id);
}

/**
 * Autorisation personnalisée : est_auteur.
 *
 * @param string $faire  Action demandée ('est_auteur')
 * @param string $type   Type d'objet ('article')
 * @param int    $id     ID de l'article
 * @param array  $qui    Auteur demandeur
 * @param array  $opt    Options
 * @return bool
 */
function autoriser_est_auteur_article_dist($faire, $type, $id, $qui, $opt) {
	return (bool) fictionsv2_est_auteur_droits($id);
}
