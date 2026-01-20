<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');
include_spip('inc/cioidc_commun');

function action_supprimer_serveur_oidc($id_serveur = null) {

	if (is_null($id_serveur)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_serveur = $securiser_action();
	}

	if (autoriser('configurer', '_cioidc')) {
		cioidc_supprimer_serveur_additionnel(intval($id_serveur));
	}
}
