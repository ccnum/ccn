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
 * Suppression d'un commentaire de forum (issue #356), règle actée par
 * ChristoErasme le 24/08 :
 * - un élève ne peut jamais supprimer, même son propre message ;
 * - un intervenant ne peut supprimer que ses propres messages ;
 * - un prof peut supprimer ses propres messages, ou ceux d'un élève de sa
 *   classe (comparaison des rubriques classe via thematique_id_rubrique_-
 *   classe_prof/_auteur, pas juste "un élève quelconque") ;
 * - un admin peut tout supprimer.
 *
 * Seule fonction d'autorisation pour cette action : le bouton "supprimer"
 * (article-forum-detail.html, forum_succes.html) teste désormais
 * #AUTORISER{forumsupprimer,...} directement, la même permission que
 * l'action instituer_forum vérifie réellement — plus de double check
 * incohérent entre affichage et action.
 */
function autoriser_forumsupprimer_dist($faire, $type, $id, $qui, $opt) {
	$id_auteur_visiteur = intval($qui['id_auteur'] ?? 0);
	if (!$id_auteur_visiteur) {
		return false;
	}

	include_spip('thematique_fonctions');
	$role_visiteur = thematique_donner_role($id_auteur_visiteur);

	if ($role_visiteur === 'admin') {
		return true;
	}
	if ($role_visiteur === 'eleve') {
		return false;
	}

	$forum = sql_fetsel('id_auteur', 'spip_forum', 'id_forum=' . intval($id));
	if (!$forum) {
		return false;
	}
	$id_auteur_commentaire = intval($forum['id_auteur']);

	// Ses propres messages : toujours autorisé (prof comme intervenant)
	if ($id_auteur_commentaire === $id_auteur_visiteur) {
		return true;
	}

	// Intervenant : uniquement ses propres messages
	if ($role_visiteur === 'intervenant') {
		return false;
	}

	// Prof : le message d'un élève de sa classe uniquement
	if (thematique_donner_role($id_auteur_commentaire) !== 'eleve') {
		return false;
	}

	$id_rubrique_classe_prof = thematique_id_rubrique_classe($id_auteur_visiteur);

	return $id_rubrique_classe_prof
		&& $id_rubrique_classe_prof === thematique_id_rubrique_classe_auteur($id_auteur_commentaire);
}
