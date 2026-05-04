<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * FONCTIONS
 **/
function filtre_nb2col($nb) {
	return substr($nb, spip_strlen((int) $nb) - 1, 1);
}
