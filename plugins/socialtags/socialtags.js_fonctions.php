<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_once __DIR__ . '/socialtags_fonctions.php';

function socialtags_json($cfg) {
	if (!is_array($cfg)) {
		return '[]';
	}

	$json = [];

	include_spip('socialtags_fonctions');

	foreach (socialtags_liste() as $service) {
	if (in_array($a = $service['lesauteurs'], $cfg)) {
		$t = _q($service['titre']);
		$u = _q($service['url']);
		$d = isset($service['descriptif']) ? _q($service['descriptif']) : $t;
		$u_site = _q($GLOBALS['meta']['adresse_site']);
		$png = find_in_path('images/' . $a . '.png');
		$svg = find_in_path('images/' . $a . '.svg');
		if ($svg) {
			$xml = simplexml_load_file($svg);
			$attr = $xml->attributes();
			$w = intval($attr->width);
			$h = intval($attr->height);
			$i = _q('data:image/svg+xml;base64,' . base64_encode(file_get_contents($svg)));
		}
		elseif ($png) {
			$icon = file_get_contents($png);
			$img = imagecreatefromstring($icon);
			$w = intval(imagesx($img));
			$h = intval(imagesy($img));
			$i = _q('data:image/png;base64,' . base64_encode($icon));
		}
		$json[] = "{ a: '{$a}', n: {$t}, i: {$i}, w: {$w}, h: {$h}, u: {$u}, u_site: {$u_site}}";
	}
	}

	return "[\n" . join(",\n", $json) . "\n]";
}
