<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Change le statut d'un article depuis le front :
 * - publie / prop : boutons Publier/Dépublier des jalons (cf
 *   noisettes/sidebar/inc/boutons_publication_jalon.html), pour qui peut
 *   modifier l'article ;
 * - poubelle : bouton « Supprimer » d'un billet/évènement (cf
 *   noisettes/sidebar/inc/supprimer_article.html), pour qui peut le supprimer.
 * Réponse HTTP seule (appels fetch() de changerStatutArticle() et
 * supprimerArticle() dans controleurs.js) : 200 si l'article a bien le
 * statut visé, 403 sinon.
 *
 * @param string|null $arg "id_article-statut", lu depuis l'action sécurisée si null
 */
function action_thematique_statut_article_dist($arg = null) {
	if ($arg === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	[$id_article, $statut] = array_pad(explode('-', (string) $arg, 2), 2, '');
	$id_article = intval($id_article);
	$autorisations = [
		'publie' => 'modifier',
		'prop' => 'modifier',
		'poubelle' => 'supprimer',
	];
	if (
		!$id_article
		|| !isset($autorisations[$statut])
		|| !autoriser($autorisations[$statut], 'article', $id_article)
	) {
		http_response_code(403);
		exit;
	}

	include_spip('action/editer_article');
	include_spip('action/editer_liens');

	// article_instituer() exige 'publierdans' sur la rubrique de l'article
	// pour publier (cf ecrire/action/editer_article.php), que n'a pas un
	// intervenant (1comite) : sans exception, la publication d'un jalon était
	// refusée silencieusement. Le droit de modifier l'article suffit ici.
	// Pas pour les autres statuts : article_instituer() s'appuie alors sur
	// autoriser_article_modifier, qui refuse les contenus d'une année passée.
	$id_rubrique = $statut === 'publie'
		? sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_article)
		: 0;
	if ($id_rubrique) {
		autoriser_exception('publierdans', 'rubrique', $id_rubrique, true);
	}
	article_instituer($id_article, ['statut' => $statut]);
	if ($id_rubrique) {
		autoriser_exception('publierdans', 'rubrique', $id_rubrique, false);
	}

	// article_instituer() refuse sans erreur : on relit le statut
	if (sql_getfetsel('statut', 'spip_articles', 'id_article=' . $id_article) !== $statut) {
		spip_log("passage de l'article $id_article en statut $statut refusé", 'thematique' . _LOG_ERREUR);
		http_response_code(403);
		exit;
	}

	if ($statut === 'publie' && ($id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0))) {
		objet_associer(['auteurs' => $id_auteur], ['articles' => $id_article]);
	}

	http_response_code(200);
	exit;
}
