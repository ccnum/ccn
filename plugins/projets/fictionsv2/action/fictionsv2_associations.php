<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Opérations ponctuelles de la page ecrire/?exec=fictionsv2_associations (#525),
 * déclenchées par BOUTON_ACTION : retrait d'un participant, synchronisation des
 * histoires, génération et validation du plan. Les saisies (calendrier, participant,
 * affectations) passent par les formulaires CVT formulaires/fictionsv2_*.
 *
 * Argument sécurisé : "<annee>-<operation>[-<id_participant>]". Réservé aux admins
 * complets et aux webmestres (autoriser fictionsv2associations). Retour sur la page
 * avec ok=<operation> ou erreur=<code> (items de langue fictionsv2:ok_* / erreur_*).
 */
function action_fictionsv2_associations_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = (string) $securiser_action();

	if (!preg_match('/^(\d{4})-([a-z_]+)(?:-(\d+))?$/', $arg, $m) || !autoriser('fictionsv2associations')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	$annee = (int) $m[1];
	$operation = $m[2];
	$id = (int) ($m[3] ?? 0);

	include_spip('inc/fictionsv2_plan');
	$erreur = fictionsv2_associations_operation($annee, $operation, $id);

	include_spip('inc/headers');
	redirige_par_entete(generer_url_ecrire(
		'fictionsv2_associations',
		'annee=' . $annee . ($erreur ? '&erreur=' . $erreur : '&ok=' . $operation),
		true
	));
}

/**
 * @return string '' si OK, sinon le code d'erreur
 */
function fictionsv2_associations_operation(int $annee, string $operation, int $id = 0): string {
	switch ($operation) {
		case 'retirer':
			return fictionsv2_participant_retirer($annee, $id);

		case 'synchroniser':
			return fictionsv2_histoires_synchroniser($annee)['erreur'];

		case 'generer':
			return fictionsv2_plan_generer($annee);

		case 'valider':
			return fictionsv2_plan_valider($annee);
	}
	return 'operation_inconnue';
}
