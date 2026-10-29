<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Rouvre la pop-up de choix des classes d'un prof en cours d'année (lien
 * « Modifier mes classes » du menu haut, cf noisettes/inc/authentification.html) :
 * vide l'année de son choix (classes_ent_choisies), la pop-up
 * (formulaires/choisir_classes_ent.php) s'affiche alors au chargement suivant,
 * avec ses classes actuelles pré-cochées.
 *
 * session_set() réécrit le fichier de session (rôle et avatar recalculés par
 * thematique_preparer_fichier_session()).
 */
function action_thematique_modifier_classes_ent_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	include_spip('inc/session');
	include_spip('thematique_fonctions');
	$id_auteur = intval(session_get('id_auteur'));

	if ($id_auteur && thematique_classes_ent_modifiables($id_auteur)) {
		sql_updateq('spip_auteurs', ['classes_ent_choisies' => ''], 'id_auteur=' . $id_auteur);
		spip_log("choix des classes auteur #$id_auteur rouvert", 'cioidc');
		session_set('classes_ent_choisies', '');
	}
}
