<?php

/**
 * Plugin Acces Restreint 5.0 pour Spip 4.x
 * Licence GPL (c) depuis 2006 Cedric Morin
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function action_affecter_zone_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (
		preg_match(',^([0-9]+|-1)-([a-z]+)-([0-9]+|-1)$,', $arg, $regs)
		and $regs[2] == 'auteur'
	) {
		$id_zone = intval($regs[1]);
		$id_auteur = intval($regs[3]);
		include_spip('action/editer_zone');
		if ($id_auteur == -1) {
			$id_auteur = sql_allfetsel('id_auteur', 'spip_auteurs', "statut!='poub'");
			$id_auteur = array_column($id_auteur, 'id_auteur');
		}
		zone_lier($id_zone == '-1' ? '' : $id_zone, 'auteur', $id_auteur);
	}
}
