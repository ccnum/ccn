<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Supprime les rubriques de classe vides d'une année scolaire (argument sécurisé :
 * l'année). Réservé aux webmestres, cf inc/thematique_classes_vides.php.
 */
function action_thematique_supprimer_classes_vides_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$annee = (string) $securiser_action();

	if (!preg_match('/^\d{4}$/', $annee) || !autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	include_spip('inc/thematique_classes_vides');
	$nb = thematique_supprimer_classes_vides($annee);

	include_spip('inc/headers');
	redirige_par_entete(generer_url_ecrire('thematique_classes_vides', 'annee=' . $annee . '&supprimees=' . $nb, true));
}
