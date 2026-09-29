<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function socialtags_autoriser() {
}

// Affichage du bouton de menu
function autoriser_socialtags_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre', $type, $id, $qui, $opt);
}

// Autorisation de configuration
function autoriser_socialtags_configurer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre', $type, $id, $qui, $opt);
}
