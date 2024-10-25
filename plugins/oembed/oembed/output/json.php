<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Generer une sortie json a partit d'un tableau associatif
 * @param array $res
 * @param bool $output
 * @return void|string
 */
function oembed_output_json_dist($res, $output = true) {

	$out = json_encode($res, JSON_THROW_ON_ERROR);
	$out = str_replace(['<', '>'], ['\u003C', '\u003E'], $out);

	if (!$output) {
		return $out;
	}

	header('Content-type: application/json; charset=utf-8');
	echo $out;
}
