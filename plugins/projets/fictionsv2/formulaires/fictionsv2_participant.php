<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajout (id_participant vide) ou modification d'un participant d'une année (#518,
 * #519, #525). L'ajout est refusé une fois le plan verrouillé ; la modification reste
 * possible (classe qui change d'enseignant).
 */

function formulaires_fictionsv2_participant_charger_dist($annee, $id_participant = 0) {
	if (!autoriser('fictionsv2associations')) {
		return false;
	}
	include_spip('inc/fictionsv2_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	$participant = $id_participant ? (fictionsv2_participants($annee, false)[$id_participant] ?? null) : null;
	if ($id_participant && !$participant) {
		return false;
	}

	$comptes = [];
	foreach (sql_allfetsel('id_auteur, nom', 'spip_auteurs', sql_in('statut', ['0minirezo', '1comite']), '', 'nom') as $row) {
		$comptes[(int) $row['id_auteur']] = $row['nom'];
	}

	$valeurs = [
		'nom' => $participant['nom'] ?? '',
		'type' => $participant['type'] ?? 'classe',
		'id_auteur' => $participant['id_auteur'] ?? '',
		'_comptes' => $comptes,
		'_modification' => (bool) $participant,
	];
	if (!$participant && fictionsv2_plan_verrouille($annee)) {
		$valeurs['editable'] = false;
		$valeurs['message_erreur'] = _T('fictionsv2:erreur_plan_verrouille');
	}
	return $valeurs;
}

function formulaires_fictionsv2_participant_verifier_dist($annee, $id_participant = 0) {
	include_spip('inc/fictionsv2_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	if (!$id_participant && fictionsv2_plan_verrouille($annee)) {
		return ['message_erreur' => _T('fictionsv2:erreur_plan_verrouille')];
	}

	$code = fictionsv2_participant_verifier(
		$annee,
		(string) _request('type'),
		(string) _request('nom'),
		intval(_request('id_auteur')),
		$id_participant
	);
	if (!$code) {
		return [];
	}
	// Erreur rattachée au champ concerné
	$champs = [
		'nom_obligatoire' => 'nom',
		'participant_doublon' => 'nom',
		'type_invalide' => 'type',
		'auteur_invalide' => 'id_auteur',
	];
	$cle = 'fictionsv2:erreur_' . $code;
	$message = _T($cle);
	return isset($champs[$code]) ? [$champs[$code] => $message] : ['message_erreur' => $message];
}

function formulaires_fictionsv2_participant_traiter_dist($annee, $id_participant = 0) {
	include_spip('inc/fictionsv2_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	$type = (string) _request('type');
	$nom = (string) _request('nom');
	$id_auteur = intval(_request('id_auteur'));

	if ($id_participant) {
		$code = fictionsv2_participant_modifier($annee, $id_participant, $type, $nom, $id_auteur);
		$ok = 'ok_modifier';
	} else {
		$code = fictionsv2_participant_ajouter($annee, $type, $nom, $id_auteur)[1];
		$ok = 'ok_ajouter';
	}
	if ($code) {
		$cle = 'fictionsv2:erreur_' . $code;
		return ['message_erreur' => _T($cle)];
	}
	// Retour à la liste (sans id_participant : le formulaire repasse en ajout)
	$cle = 'fictionsv2:' . $ok;
	return [
		'message_ok' => _T($cle),
		'redirect' => generer_url_ecrire('fictionsv2_associations', 'annee=' . $annee . '&ok=' . substr($ok, 3)),
	];
}
