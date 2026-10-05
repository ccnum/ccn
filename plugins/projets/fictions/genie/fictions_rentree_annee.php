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
 * les 24h. Vise l'année scolaire en cours (_ANNEE_ACTUELLE_CALCULEE, ccn_options.php :
 * bascule en septembre) tant qu'elle n'est pas marquée traitée — et non plus seulement
 * en septembre : un site mis en service (ou un cron qui n'a pas tourné) après la
 * rentrée n'aurait sinon pas sa structure avant l'année suivante. Désactivable via
 * _CCN_PROJET_ACTIVE (mes_options.php, variable d'environnement Docker CCN_PROJET_ACTIVE).
 *
 * @param int $last
 * @return int
 */
function genie_fictions_rentree_annee_dist($last) {
	spip_log('fictions_rentree_annee : tâche déclenchée (last=' . $last . ')', 'fictions');

	if (defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE) {
		spip_log('fictions_rentree_annee : _CCN_PROJET_ACTIVE=false, projet inactif, on ne fait rien', 'fictions');
		return 1;
	}

	$annee = defined('_ANNEE_ACTUELLE_CALCULEE')
		? intval(_ANNEE_ACTUELLE_CALCULEE)
		: (intval(date('n')) >= 9 ? intval(date('Y')) : intval(date('Y')) - 1);
	// Meta écrite seulement si tout a été créé : une exécution incomplète (ex: mot-clé
	// manquant) est retentée au passage suivant du cron.
	if (intval($GLOBALS['meta']['fictions_rentree_annee_traitee'] ?? 0) >= $annee) {
		spip_log("fictions_rentree_annee : année $annee déjà traitée, on ne fait rien", 'fictions');
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
