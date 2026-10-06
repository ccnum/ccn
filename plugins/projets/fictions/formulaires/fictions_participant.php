<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajout (id_participant vide) ou modification d'un participant d'une année (#518,
 * #519, #525). L'ajout est refusé une fois le plan verrouillé ; la modification reste
 * possible (classe qui change d'enseignant).
 */

/**
 * Champs du formulaire (plugin saisies). Saisies contrôle aussi, avant verifier(), les
 * champs obligatoires et que type et compte font partie des choix proposés : un POST
 * forgé ne peut pas associer un visiteur (6forum), qui ne pourrait pas écrire.
 */
function formulaires_fictions_participant_saisies_dist($annee, $id_participant = 0) {
	include_spip('inc/fictions_participants');
	$annee = intval($annee);

	// Comptes proposés : d'abord les profs inscrits au projet de l'année (notés à leur
	// connexion SSO, cf fictions_cioidc_userinfo), puis tous les autres comptes
	// rédacteurs et admins (comptes créés à la main, écrivains).
	$inscrits = fictions_annee_config($annee)['inscrits'];
	$comptes_inscrits = $comptes = [];
	// Libellé = nom, suivi de l'email quand il est connu : beaucoup de comptes portent
	// un nom de classe, la recherche (filtre du formulaire) porte aussi sur l'email.
	foreach (sql_allfetsel('id_auteur, nom, email', 'spip_auteurs', sql_in('statut', ['0minirezo', '1comite']), '', 'nom') as $row) {
		$libelle = $row['nom'] . ($row['email'] ? ' — ' . $row['email'] : '');
		if (isset($inscrits[(int) $row['id_auteur']])) {
			$comptes_inscrits[(int) $row['id_auteur']] = $libelle;
		} else {
			$comptes[(int) $row['id_auteur']] = $libelle;
		}
	}
	// Groupes d'options : la clé sert de libellé à l'optgroup
	$data_comptes = [];
	if ($comptes_inscrits) {
		$data_comptes[_T('fictions:associations_comptes_inscrits', ['annee' => $annee])] = $comptes_inscrits;
	}
	$data_comptes[_T('fictions:associations_comptes_autres')] = $comptes;

	// Mêmes valeurs que FICTIONS_TYPES_PARTICIPANT
	$types = [
		'classe' => _T('fictions:participant_type_classe'),
		'ecrivain' => _T('fictions:participant_type_ecrivain'),
	];

	return [
		[
			'saisie' => 'input',
			'options' => [
				'nom' => 'nom',
				'label' => _T('fictions:associations_nom'),
				'obligatoire' => 'oui',
			],
		],
		[
			'saisie' => 'selection',
			'options' => [
				'nom' => 'type',
				'label' => _T('fictions:associations_type'),
				'obligatoire' => 'oui',
				'cacher_option_intro' => 'oui',
				'data' => $types,
				'defaut' => 'classe',
			],
		],
		[
			'saisie' => 'selection',
			'options' => [
				'nom' => 'id_auteur',
				'label' => _T('fictions:associations_compte'),
				'explication' => _T('fictions:associations_compte_explication'),
				'obligatoire' => 'oui',
				'data' => $data_comptes,
			],
		],
		'options' => [
			'verifier_valeurs_acceptables' => true,
		],
	];
}

function formulaires_fictions_participant_charger_dist($annee, $id_participant = 0) {
	if (!autoriser('fictionsassociations')) {
		return false;
	}
	include_spip('inc/fictions_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	$participant = $id_participant ? (fictions_participants($annee, false)[$id_participant] ?? null) : null;
	if ($id_participant && !$participant) {
		return false;
	}

	$valeurs = [
		'nom' => $participant['nom'] ?? '',
		'type' => $participant['type'] ?? 'classe',
		'id_auteur' => $participant['id_auteur'] ?? '',
		'annee' => $annee,
		'_modification' => (bool) $participant,
	];
	if (!$participant && fictions_plan_verrouille($annee)) {
		$valeurs['editable'] = false;
		$valeurs['message_erreur'] = _T('fictions:erreur_plan_verrouille');
	}
	return $valeurs;
}

function formulaires_fictions_participant_verifier_dist($annee, $id_participant = 0) {
	include_spip('inc/fictions_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	if (!$id_participant && fictions_plan_verrouille($annee)) {
		return ['message_erreur' => _T('fictions:erreur_plan_verrouille')];
	}

	$code = fictions_participant_verifier(
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
	$cle = 'fictions:erreur_' . $code;
	$message = _T($cle);
	return isset($champs[$code]) ? [$champs[$code] => $message] : ['message_erreur' => $message];
}

function formulaires_fictions_participant_traiter_dist($annee, $id_participant = 0) {
	// charger() n'est pas rappelé au POST : on revérifie le droit ici.
	if (!autoriser('fictionsassociations')) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}
	include_spip('inc/fictions_participants');
	$annee = intval($annee);
	$id_participant = intval($id_participant);
	$type = (string) _request('type');
	$nom = (string) _request('nom');
	$id_auteur = intval(_request('id_auteur'));

	if ($id_participant) {
		$code = fictions_participant_modifier($annee, $id_participant, $type, $nom, $id_auteur);
		$ok = 'ok_modifier';
	} else {
		$code = fictions_participant_ajouter($annee, $type, $nom, $id_auteur)[1];
		$ok = 'ok_ajouter';
	}
	if ($code) {
		$cle = 'fictions:erreur_' . $code;
		return ['message_erreur' => _T($cle)];
	}
	// Retour à la liste (sans id_participant : le formulaire repasse en ajout)
	$cle = 'fictions:' . $ok;
	return [
		'message_ok' => _T($cle),
		'redirect' => generer_url_ecrire('fictions_associations', 'annee=' . $annee . '&ok=' . substr($ok, 3)),
	];
}
