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

/**
 * Modération des forums attachés à un article (modifier/supprimer un
 * message) : réservée aux profs/intervenants/admins, jamais aux élèves —
 * y compris sur leurs propres messages. Les élèves ne doivent pouvoir que
 * publier. (issue #356)
 *
 * Nom spécifique au type "article" (testé par SPIP avant la version
 * générique autoriser_modererforum) plutôt qu'une surcharge de
 * autoriser_modererforum() : ce nom générique est déjà défini par le
 * plugin autorite (plugins/autorite/inc/autoriser.php), le redéclarer
 * provoque un fatal "Cannot redeclare".
 */
function autoriser_article_modererforum_dist($faire, $type, $id, $qui, $opt) {
	include_spip('thematique_fonctions');
	$role = thematique_donner_role($qui['id_auteur'] ?? 0);

	return in_array($role, ['prof', 'intervenant', 'admin'], true);
}

function autoriser_forumsupprimer_dist($faire, $type, $id, $qui, $opt) {
	if (!$qui['id_auteur']) {
		return false;
	}

	$forum = sql_fetsel('id_auteur', 'spip_forum', 'id_forum=' . intval($id));
	if (!$forum) {
		return false;
	}

	$id_auteur_commentaire = intval($forum['id_auteur']);

	// L'auteur peut toujours supprimer son propre commentaire
	if ($id_auteur_commentaire === intval($qui['id_auteur'])) {
		return true;
	}

	// Sinon, un compte non-élève peut supprimer le commentaire d'un élève
	$role_visiteur = thematique_donner_role($qui['id_auteur']);
	$role_auteur_commentaire = thematique_donner_role($id_auteur_commentaire);

	return $role_visiteur !== 'eleve' && $role_auteur_commentaire === 'eleve';
}
