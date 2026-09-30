<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Choix de la classe active d'un prof rattaché à plusieurs classes (menu
 * haut, cf noisettes/inc/authentification.html) : mémorisée en
 * #SESSION{classe_active}, elle devient la classe dans laquelle il rédige et
 * celle de son emoji d'avatar (cf thematique_id_rubrique_classe()).
 *
 * L'avatar est recalculé à l'écriture de la session
 * (thematique_preparer_fichier_session()), déclenchée par session_set().
 */
function action_thematique_choisir_classe_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_rubrique = intval($securiser_action());

	include_spip('inc/session');
	include_spip('thematique_fonctions');
	$id_auteur = intval(session_get('id_auteur'));

	// Uniquement une de ses propres classes
	if (_THEMATIQUE_CHOIX_CLASSE && $id_auteur && in_array($id_rubrique, thematique_classes_auteur($id_auteur), true)) {
		session_set('classe_active', $id_rubrique);
	}
}
