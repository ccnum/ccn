<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * À la rentrée de septembre, crée la structure de la nouvelle année scolaire si elle
 * n'existe pas encore (cf fictions_assurer_structure_annee()), sur le modèle de
 * genie/thematique_rentree_annee.php.
 *
 * Enregistrée via le pipeline taches_generales_cron (fictions_pipelines.php), toutes
 * les 24h ; ne fait quelque chose qu'en septembre. Désactivable via _CCN_PROJET_ACTIVE
 * (mes_options.php, variable d'environnement Docker CCN_PROJET_ACTIVE).
 *
 * @param int $last
 * @return int
 */
function genie_fictions_rentree_annee_dist($last) {
	if (defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE) {
		spip_log('fictions_rentree_annee : _CCN_PROJET_ACTIVE=false, projet inactif, on ne fait rien', 'fictions');
		return 1;
	}

	if (date('n') != 9) {
		return 1;
	}

	$annee = intval(date('Y'));
	// Meta écrite seulement si tout a été créé : une exécution incomplète (ex: mot-clé
	// manquant) est retentée au passage suivant du cron.
	if (intval($GLOBALS['meta']['fictions_rentree_annee_traitee'] ?? 0) >= $annee) {
		return 1;
	}

	include_spip('inc/fictions_rentree');
	[$id_annee, $ok] = fictions_assurer_structure_annee($annee);
	if (!$id_annee || !$ok) {
		spip_log(
			"fictions_rentree_annee $annee : structure incomplète, nouvelle tentative au prochain passage du cron",
			'fictions' . _LOG_ERREUR
		);
		return 1;
	}

	include_spip('inc/meta');
	ecrire_meta('fictions_rentree_annee_traitee', $annee);
	spip_log("fictions_rentree_annee $annee : structure de l'année OK (#$id_annee)", 'fictions');
	return 1;
}
