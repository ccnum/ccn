<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_zone_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (
		$id_zone = intval($arg)
		and autoriser('supprimer', 'zone', $id_zone)
	) {
		include_spip('action/editer_zone');
		zone_supprimer($id_zone);
	}
}
