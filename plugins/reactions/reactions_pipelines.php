<?php
/**
 * Pipelines du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline insert_head_css : insère le CSS dans <head> des pages
 * publiques où le widget réactions est susceptible d'apparaître.
 *
 * Pour rester simple et robuste, le CSS est chargé sur toutes les
 * pages publiques plutôt que de tenter une détection fine de la
 * présence du widget — le fichier est minuscule, le coût est négligeable.
 *
 * @param string $flux
 * @return string
 */
function reactions_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" href="'
		. timestamp(find_in_path('css/reactions.css'))
		. '" />';
	return $flux;
}

/**
 * Pipeline header_prive : charge également le CSS dans l'espace
 * privé (utile si le widget est prévisualisé depuis l'admin).
 *
 * @param string $flux
 * @return string
 */
function reactions_header_prive($flux) {
	$flux .= '<link rel="stylesheet" href="'
		. timestamp(find_in_path('css/reactions.css'))
		. '" />';
	return $flux;
}