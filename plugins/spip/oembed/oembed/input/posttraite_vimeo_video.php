<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function oembed_input_posttraite_vimeo_video_dist($data, $url_orig) {

	// https://git.spip.net/spip-contrib-extensions/oembed/issues/5
	// recuperer des URLs en resolution superieure a la valeur par defaut qui est tres petite
	if (
		!empty($data['thumbnail_url'])
		and preg_match(',(_(\d+)x(\d+))(\.\w+)?$,', $data['thumbnail_url'], $m)
	) {
		$thumbnail_url = $data['thumbnail_url'];
		$_size = $m[1];
		$width = $m[2];
		$height = $m[3];
		$extension = ($m[4] ?? '');

		include_spip('inc/config');
		$maxwidth = lire_config('oembed/maxwidth', '600');

		if ($width < $maxwidth) {
			$w = 640;
			if ($maxwidth > 640) {
				$w = 1280;
			}
			$h = intval(round($height * ($w / $width)));
			$thumbnail_url = str_replace($m[0], "_{$w}x{$h}" . $extension, $thumbnail_url);
			$data['thumbnail_url'] = $thumbnail_url;
			$data['thumbnail_width'] = $w;
			$data['thumbnail_height'] = $h;
			spip_log("VIMEO : replace url thumbnail => $thumbnail_url", 'oembed');
		}
	}

	return $data;
}
