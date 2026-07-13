<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function thematique_autoriser() {
}

// declarations d'autorisations
// Uniquement des fonctions courtes ici théoriquement
function autoriser_thematique_creer_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', 'thematique', $id, $qui, $opt);
}

function autoriser_thematique_configurer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre', $type, $id, $qui, $opt);
}

function autoriser_article_modifier($faire, $type, $id, $qui, $opt) {
	if (!$id) {
		return false;
	}

	$role = thematique_donner_role($qui['id_auteur']);   // Cette fonction est rapide car elle utilise du cache.

	if ($role === 'admin') {
		return true;
	}

	if (in_array($role, ['prof', 'intervenant'], true)) {
		$r = sql_fetsel('statut', 'spip_articles', 'id_article=' . sql_quote($id));

		if (!$r) {
			return false;
		}

		// un article déjà publié ou refusé ne se modifie plus
		if (in_array($r['statut'], ['publie', 'refuse'], true)) {
			return false;
		}

		// uniquement ses propres articles
		return auteurs_objet('article', $id, 'id_auteur=' . $qui['id_auteur']);
	}

	// eleve, role inconnu, ou null : refus
	return false;
}