<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_accesrestreint_charger_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('configurer', 'accesrestreint')) {
		return false;
	}
	include_spip('inc/config');
	$valeurs = [
		'accesrestreint_proteger_documents' => lire_config('accesrestreint_proteger_documents', 'non'),
		'creer_htpasswd' => lire_config('creer_htpasswd', 'non'),
	];
	return $valeurs;
}

function formulaires_configurer_accesrestreint_traiter_dist() {
	include_spip('inc/autoriser');

	if (autoriser('configurer', 'accesrestreint')) {
		$champs = ['accesrestreint_proteger_documents','creer_htpasswd'];

		include_spip('inc/config');
		$old_config = lire_config('accesrestreint_proteger_documents');

		foreach ($champs as $c) {
			ecrire_config($c, _request($c) == 'oui' ? 'oui' : 'non');
		}

		// generer/supprimer les fichiers htaccess qui vont bien
		include_spip('inc/accesrestreint_documents');
		$new_config = lire_config('accesrestreint_proteger_documents');
		accesrestreint_gerer_htaccess($new_config == 'oui');

		// si le reglage du htaccess a change, purger le cache
		if ($new_config !== $old_config) {
			$purger = charger_fonction('purger', 'action');
			$purger('cache');
		}
	}

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}
