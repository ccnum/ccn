<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Calendrier d'une année d'écriture : lancement, clôture, finalisation (#518, #525).
 */

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
