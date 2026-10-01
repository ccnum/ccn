<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Calendrier d'une année d'écriture : lancement, clôture, finalisation (#518, #525).
 */

function formulaires_fictionsv2_calendrier_charger_dist($annee) {
	if (!autoriser('fictionsv2associations')) {
		return false;
	}
	include_spip('inc/fictionsv2_participants');
	$config = fictionsv2_annee_config(intval($annee));
	return [
		'lancement' => $config['lancement'],
		'cloture' => $config['cloture'],
		'finalisation' => $config['finalisation'],
	];
}

function formulaires_fictionsv2_calendrier_verifier_dist($annee) {
	include_spip('inc/fictionsv2_participants');
	$code = fictionsv2_annee_dates_verifier(
		(string) _request('lancement'),
		(string) _request('cloture'),
		(string) _request('finalisation')
	);
	if (!$code) {
		return [];
	}
	$cle = 'fictionsv2:erreur_' . $code;
	return [$code === 'dates_ordre' ? 'cloture' : 'message_erreur' => _T($cle)];
}

function formulaires_fictionsv2_calendrier_traiter_dist($annee) {
	include_spip('inc/fictionsv2_participants');
	$code = fictionsv2_annee_dates_modifier(
		intval($annee),
		(string) _request('lancement'),
		(string) _request('cloture'),
		(string) _request('finalisation')
	);
	if ($code) {
		$cle = 'fictionsv2:erreur_' . $code;
		return ['message_erreur' => _T($cle)];
	}
	// La phase d'écriture affichée sur la page dépend des dates : retour sur la page
	return [
		'message_ok' => _T('fictionsv2:ok_dates'),
		'redirect' => generer_url_ecrire('fictionsv2_associations', 'annee=' . intval($annee) . '&ok=dates'),
	];
}
