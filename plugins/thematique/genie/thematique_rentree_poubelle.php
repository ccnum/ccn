<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chaque rentrée scolaire (septembre), passe tous les comptes non-webmestres
 * en statut 5poubelle (élèves et enseignants), pour forcer un réapprovisionnement
 * des comptes via la connexion ENT (cioidc) de la nouvelle année.
 *
 * @param int $last
 * @return int
 */
function genie_thematique_rentree_poubelle_dist($last) {
	if (date('n') != 9) {
		return 1;
	}

	$annee = date('Y');
	$derniere_execution = intval($GLOBALS['meta']['thematique_rentree_poubelle_annee'] ?? 0);
	if ($derniere_execution >= $annee) {
		return 1;
	}

	sql_updateq('spip_auteurs', ['statut' => '5poubelle'], "webmestre != 'oui'");
	spip_log('rentrée ' . $annee . ' : passage en 5poubelle de tous les comptes non-webmestres', 'thematique');

	ecrire_meta('thematique_rentree_poubelle_annee', $annee);

	return 1;
}
