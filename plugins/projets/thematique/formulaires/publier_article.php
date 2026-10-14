<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Petit formulaire "Publier" pour un article existant, accessible depuis le
 * front (ex: articles jalons du projet, cf thematique_rentree_annee.php,
 * créés en statut 'prop').
 *
 * @package SPIP\Thematique\Formulaires
 *
 * @param int|string $id_article Article concerné
 * @return array|false Valeurs du formulaire, false si non éditable
 */

function formulaires_publier_article_charger_dist($id_article) {
	// Même autorisation que verifier() : sans elle, le bouton s'affichait
	// aussi aux visiteurs non connectés (l'action était refusée, mais seulement
	// après le clic).
	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article)) {
		return false;
	}

	return [
		'id_article' => $id_article,
	];
}

/**
 * Vérifie que le visiteur peut toujours modifier l'article.
 *
 * @param int|string $id_article Article concerné
 * @return array Erreurs, vide si aucune
 */
function formulaires_publier_article_verifier_dist($id_article) {
	$erreurs = [];

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
	}

	return $erreurs;
}

/**
 * Publie l'article.
 *
 * @param int|string $id_article Article concerné
 * @return array Retour du traitement (message_ok, message_erreur, redirect…)
 */
function formulaires_publier_article_traiter_dist($id_article) {
	include_spip('action/editer_article');
	include_spip('action/editer_liens');

	article_instituer($id_article, ['statut' => 'publie']);

	if ($id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? false) {
		objet_associer(['auteurs' => intval($id_auteur)], ['articles' => intval($id_article)]);
	}

	$res['message_ok'] = _T('thematique:article_publie');
	$res['message_ok'] .= "<script type='text/javascript'>"
		. 'setTimeout(function () { window.location.reload(); }, 800);'
		. '</script>';

	return $res;
}
