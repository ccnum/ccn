<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Met un article à la poubelle (bouton « Supprimer » d'un billet/évènement, #515).
 * Réponse HTTP seule (appel fetch() de supprimerArticle() dans controleurs.js) :
 * 200 si l'article est bien passé à la poubelle, 403 sinon.
 */
function action_thematique_supprimer_article_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	$id_article = intval($arg);
	if (!$id_article || !autoriser('supprimer', 'article', $id_article)) {
		http_response_code(403);
		exit;
	}
	include_spip('action/editer_objet');
	// objet_instituer() refuse sans erreur (année passée, cf autoriser_article_modifier) : on relit le statut
	objet_instituer('article', $id_article, ['statut' => 'poubelle']);
	$statut = sql_getfetsel('statut', 'spip_articles', 'id_article=' . $id_article);

	http_response_code($statut === 'poubelle' ? 200 : 403);
	exit;
}
