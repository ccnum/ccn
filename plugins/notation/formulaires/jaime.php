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

include_spip('inc/notation');
include_spip('base/abstract_sql');

function formulaires_jaime_charger_dist($objet, $id_objet) {
	$valeurs = [];
	$charger = charger_fonction('charger', 'formulaires/notation');

	// definition des valeurs de base du formulaire
	$valeurs = $charger($objet, $id_objet);
	return $valeurs;
}

function formulaires_jaime_verifier_dist($objet, $id_objet) {
	$erreurs = [];

	$table = table_objet($objet);

	$note = 0;
	if (_request("jaime-$table$id_objet")) {
		// j'aime -> note maxi
		$note = notation_get_nb_notes();
	}
	elseif (_request("retirer-$table$id_objet")) {
		// je n'aime plus -> on retire la note
		$note = -1;
	}
	else {
		// on ne fait rien, mais quid ?
		return [
			'message_erreur' => '?',
		];
	}

	set_request("notation-$table$id_objet", $note);

	$verifier = charger_fonction('verifier', 'formulaires/notation');
	$erreurs = $verifier($objet, $id_objet);

	return $erreurs;
}

function formulaires_jaime_traiter_dist($objet, $id_objet) {

	$traiter = charger_fonction('traiter', 'formulaires/notation');
	$result = $traiter($objet, $id_objet);
	if ($result['message_ok']) {
		$result['message_ok'] = $result['id_notation'] ? _T('notation:jaidonnemonavis') : '';
	}
	return $result;
}
