<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Calendrier d'une année d'écriture : lancement, clôture, finalisation (#518, #525).
 */

/**
 * Champs du formulaire (plugin saisies). Saisie date : valeur postée au format AAAA-MM-JJ,
 * celui attendu par fictions_annee_dates_verifier(). fictions_calendrier.html est vide
 * exprès : saisies affiche alors son gabarit générique (formulaires/inc-saisies-cvt.html).
 */
function formulaires_fictions_calendrier_saisies_dist($annee) {
	$champs = [
		'lancement' => [_T('fictions:associations_lancement'), _T('fictions:associations_lancement_explication')],
		'cloture' => [_T('fictions:associations_cloture'), _T('fictions:associations_cloture_explication')],
		'finalisation' => [_T('fictions:associations_finalisation'), _T('fictions:associations_finalisation_explication')],
	];
	$saisies = [];
	foreach ($champs as $nom => [$label, $explication]) {
		$saisies[] = [
			'saisie' => 'date',
			'options' => [
				'nom' => $nom,
				'label' => $label,
				'explication' => $explication,
			],
		];
	}
	$saisies['options'] = ['texte_submit' => _T('fictions:associations_enregistrer')];
	return $saisies;
}

function formulaires_fictions_calendrier_charger_dist($annee) {
	if (!autoriser('fictionsassociations')) {
		return false;
	}
	include_spip('inc/fictions_participants');
	$config = fictions_annee_config(intval($annee));
	return [
		'lancement' => $config['lancement'],
		'cloture' => $config['cloture'],
		'finalisation' => $config['finalisation'],
	];
}

function formulaires_fictions_calendrier_verifier_dist($annee) {
	include_spip('inc/fictions_participants');
	$code = fictions_annee_dates_verifier(
		(string) _request('lancement'),
		(string) _request('cloture'),
		(string) _request('finalisation')
	);
	if (!$code) {
		return [];
	}
	$cle = 'fictions:erreur_' . $code;
	return [$code === 'dates_ordre' ? 'cloture' : 'message_erreur' => _T($cle)];
}

function formulaires_fictions_calendrier_traiter_dist($annee) {
	// charger() n'est pas rappelé au POST : on revérifie le droit ici.
	if (!autoriser('fictionsassociations')) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}
	include_spip('inc/fictions_participants');
	$code = fictions_annee_dates_modifier(
		intval($annee),
		(string) _request('lancement'),
		(string) _request('cloture'),
		(string) _request('finalisation')
	);
	if ($code) {
		$cle = 'fictions:erreur_' . $code;
		return ['message_erreur' => _T($cle)];
	}
	// La phase d'écriture affichée sur la page dépend des dates : retour sur la page
	return [
		'message_ok' => _T('fictions:ok_dates'),
		'redirect' => generer_url_ecrire('fictions_associations', 'annee=' . intval($annee) . '&ok=dates'),
	];
}
