<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function oembed_input_pretraite_vimeo_com_dist($data_url, $args) {

	$data_url = parametre_url($data_url, 'dnt', '1', '&');

	return $data_url;
}
