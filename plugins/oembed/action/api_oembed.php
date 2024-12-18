<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_api_oembed_dist() {

	$args = [
		'url' => $url = _request('url'),
		'maxheight' => _request('maxheight'),
		'maxwidth' => _request('maxwidth'),
		'format' => _request('format'),
		// support du jsonp: http://json-p.org/
		'callback_jsonp' => _request('callback_jsonp'),
	];

	// Un pipeline pour pouvoir manipuler les arguments (en ajouter des spécifiques par ex.)
	$args = pipeline('oembed_liste_arguments', $args);

	$format = ($args['format'] == 'xml' ? 'xml' : 'json');

	$md5 = md5(serialize($args));
	$oembed_cache = sous_repertoire(_DIR_CACHE, substr($md5, 0, 1)) . 'oe-' . $md5 . '.' . $format;

	// si cache oembed dispo et pas de recalcul demande, l'utiliser (perf issue)
	$res = '';
	if (
		file_exists($oembed_cache)
		and _VAR_MODE !== 'recalcul'
		and (!defined('_VAR_NOCACHE')
			or !_VAR_NOCACHE)
	) {
		lire_fichier($oembed_cache, $res);
	} else {
		include_spip('inc/urls');
		defined('_DEFINIR_CONTEXTE_TYPE_PAGE') || define('_DEFINIR_CONTEXTE_TYPE_PAGE', true);
		[$fond, $contexte, $url_redirect] = urls_decoder_url($url ?? '', '', $args);
		if (
			!isset($contexte['type-page'])
			or !$type = $contexte['type-page']
		) {
			return '';
		}

		$res = '';
		// chercher le modele json si il existe
		if (trouver_fond($f = "oembed/output/modeles/$type.json")) {
			$res = trim(recuperer_fond($f, $contexte));

			if ($format == 'xml') {
				try {
					$res = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
				} catch (JsonException $e) {
					$res = [];
					spip_log('Failed to parse Json data : ' . $e->getMessage(), 'oembed.' . _LOG_ERREUR);
				}
				$output = charger_fonction('xml', 'oembed/output');
				$res = $output($res, false);
			}
		}
		ecrire_fichier($oembed_cache, $res);
	}

	if (!$res) {
		include_spip('inc/headers');
		http_response_code(404);
		echo '404 Not Found';
	} else {
		$content_type = ($format == 'xml' ? 'text/xml' : 'application/json');
		header("Content-type: $content_type; charset=utf-8");
		echo $res;
	}
}
