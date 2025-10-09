<?php

function balise_ANNEE_SCOLAIRE_dist($p) {
	if ((isset($_GET['annee_scolaire'])) && ($_GET['annee_scolaire'] != 0) && ($_GET['annee_scolaire'] != '')) {
		$p->code = $_GET['annee_scolaire'];
	} else {
		if (date('m') >= 8) {
			$p->code = date('Y');
		} else {
			$p->code = date('Y') - 1;
		}
	}
	return $p;
}

/* Surcharges Fonctions plugins
}
 */

/**
 * FONCTIONS
 **/
function filtre_nb2col($nb) {
	return substr($nb, spip_strlen((int) $nb) - 1, 1);
}
