<?php

/**
 * Plugin Notation v3
 * par JEM (jean-marc.viglino@ign.fr) / b_b / Matthieu Marcillaud
 *
 * Copyright (c) depuis 2008
 * Logiciel libre distribue sous licence GNU/GPL.
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('inc/notation'); // pour fonction |notation_ponderee dans le squelette

function formulaires_configurer_notation_verifier_dist() {

	$erreurs = [];

	$ponderation = _request('ponderation');
	if (!$ponderation or floatval($ponderation) < 1) {
		set_request('ponderation', $ponderation = 1);
	}

	return $erreurs;
}

function formulaires_configurer_notation_traiter_dist() {
	include_spip('inc/cvt_configurer');
	$trace = cvtconf_formulaires_configurer_enregistre('configurer_notation', []);
	$res = [
		'message_ok' => _T('config_info_enregistree') . $trace,
		'editable' => true
	];

	// Recalculer les notes
	// apres enregistrement de la config
	$ponderation = _request('ponderation');
	notation_recalculer_notes_moyennes($ponderation);

	return $res;
}
