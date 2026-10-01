<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Opérations de la page ecrire/?exec=fictionsv2_associations (#525). Argument sécurisé :
 * "<annee>-<operation>" ; les valeurs saisies arrivent en POST. Réservé aux admins
 * complets et aux webmestres (autoriser fictionsv2associations). Retour sur la page avec
 * ok=<operation> ou erreur=<code> (items de langue fictionsv2:ok_* / erreur_*).
 */
function action_fictionsv2_associations_dist() {
	include_spip('inc/actions');
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = (string) $securiser_action();

	if (!preg_match('/^(\d{4})-([a-z_]+)$/', $arg, $m) || !autoriser('fictionsv2associations')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	$annee = (int) $m[1];
	$operation = $m[2];

	include_spip('inc/fictionsv2_plan');
	$erreur = fictionsv2_associations_operation($annee, $operation);

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
function fictionsv2_associations_operation(int $annee, string $operation): string {
	switch ($operation) {
		case 'dates':
			return fictionsv2_annee_dates_modifier(
				$annee,
				(string) _request('lancement'),
				(string) _request('cloture'),
				(string) _request('finalisation')
			);

		case 'ajouter':
			return fictionsv2_participant_ajouter(
				$annee,
				(string) _request('type'),
				(string) _request('nom'),
				intval(_request('id_auteur'))
			)[1];

		case 'modifier':
			return fictionsv2_participant_modifier(
				$annee,
				intval(_request('id_participant')),
				(string) _request('type'),
				(string) _request('nom'),
				intval(_request('id_auteur'))
			);

		case 'retirer':
			return fictionsv2_participant_retirer($annee, intval(_request('id_participant')));

		case 'synchroniser':
			return fictionsv2_histoires_synchroniser($annee)['erreur'];

		case 'generer':
			return fictionsv2_plan_generer($annee);

		case 'affectations':
			// Seules les cases modifiées sont enregistrées ; première erreur renvoyée
			$plan = fictionsv2_plan($annee);
			foreach ((array) _request('affectation') as $id_rubrique => $chapitres) {
				foreach ((array) $chapitres as $chapitre => $id_participant) {
					$id_rubrique = intval($id_rubrique);
					$chapitre = intval($chapitre);
					$id_participant = intval($id_participant);
					if ((int) ($plan[$id_rubrique][$chapitre] ?? 0) === $id_participant) {
						continue;
					}
					$erreur = fictionsv2_plan_modifier_affectation($annee, $id_rubrique, $chapitre, $id_participant);
					if ($erreur) {
						return $erreur;
					}
				}
			}
			return '';

		case 'valider':
			return fictionsv2_plan_valider($annee);
	}
	return 'operation_inconnue';
}
