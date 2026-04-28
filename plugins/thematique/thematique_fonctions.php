<?php

function balise_ANNEE_SCOLAIRE_dist($p) {
	// _ANNEE_SCOLAIRE est déjà calculé et validé dans thematique_options.php
	$p->code = '_ANNEE_SCOLAIRE';
	return $p;
}


/**
 * FONCTIONS
 **/
function filtre_nb2col($nb) {
	return substr($nb, spip_strlen((int) $nb) - 1, 1);
}
