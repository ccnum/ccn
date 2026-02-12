<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */


/**
 * Lister les providers connus
 * @return array
 */
function oembed_lister_providers($avec_provider_interdits = false) {

	// liste des providers par defaut

	// voir
	// http://oembed.com/
	// http://code.google.com/p/oohembed/source/browse/app/provider/endpoints.json
	// https://github.com/starfishmod/jquery-oembed-all/blob/master/jquery.oembed.js
	// https://github.com/panzi/oembedendpoints/blob/master/endpoints-simple.json
	// voir aussi http://embed.ly/providers qui donne les scheme mais pas les endpoint
	$providers = [
		// source https://core.trac.wordpress.org/browser/trunk/src/wp-includes/class-wp-oembed.php
		'#https?://((m|www)\.)?youtube\.com/watch.*#i' => 'https://www.youtube.com/oembed',
		'#https?://((m|www)\.)?youtube\.com/playlist.*#i' => 'https://www.youtube.com/oembed',
		'#https?://((m|www)\.)?youtube\.com/shorts/*#i' => 'https://www.youtube.com/oembed',
		'#https?://((m|www)\.)?youtube\.com/live/*#i'  => 'https://www.youtube.com/oembed',
		'#https?://youtu\.be/.*#i'                     => 'https://www.youtube.com/oembed',
		'#https?://(.+\.)?vimeo\.com/.*#i'             => 'https://vimeo.com/api/oembed.json',
		'#https?://(www\.)?dailymotion\.com/.*#i'      => 'https://www.dailymotion.com/services/oembed',
		'#https?://dai\.ly/.*#i'                       => 'https://www.dailymotion.com/services/oembed',
		'#https?://(www\.)?flickr\.com/.*#i'           => 'https://www.flickr.com/services/oembed/',
		'#https?://flic\.kr/.*#i'                      => 'https://www.flickr.com/services/oembed/',
		'#https?://(.+\.)?smugmug\.com/.*#i'           => 'https://api.smugmug.com/services/oembed/',
		'#https?://(www\.)?scribd\.com/(doc|document)/.*#i' => 'https://www.scribd.com/services/oembed',
		'#https?://wordpress\.tv/.*#i'                 => 'https://wordpress.tv/oembed/',
		'#https?://(.+\.)?crowdsignal\.net/.*#i'       => 'https://api.crowdsignal.com/oembed',
		'#https?://(.+\.)?polldaddy\.com/.*#i'         => 'https://api.crowdsignal.com/oembed',
		'#https?://poll\.fm/.*#i'                      => 'https://api.crowdsignal.com/oembed',
		'#https?://(.+\.)?survey\.fm/.*#i'             => 'https://api.crowdsignal.com/oembed',
		'#https?://(www\.)?twitter\.com/\w{1,15}/status(es)?/.*#i' => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?twitter\.com/\w{1,15}$#i'   => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?twitter\.com/\w{1,15}/likes$#i' => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?twitter\.com/\w{1,15}/lists/.*#i' => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?twitter\.com/\w{1,15}/timelines/.*#i' => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?twitter\.com/i/moments/.*#i' => 'https://publish.twitter.com/oembed',
		'#https?://(www\.)?soundcloud\.com/.*#i'       => 'https://soundcloud.com/oembed',
		'#https?://(.+?\.)?slideshare\.net/.*#i'       => 'https://www.slideshare.net/api/oembed/2',
		'#https?://(open|play)\.spotify\.com/.*#i'     => 'https://embed.spotify.com/oembed/',
		'#https?://(.+\.)?imgur\.com/.*#i'             => 'https://api.imgur.com/oembed',
		'#https?://(www\.)?issuu\.com/.+/docs/.+#i'    => 'https://issuu.com/oembed_wp',
		'#https?://(www\.)?mixcloud\.com/.*#i'         => 'https://app.mixcloud.com/oembed/',
		'#https?://(www\.|embed\.)?ted\.com/talks/.*#i' => 'https://www.ted.com/services/v1/oembed.json',
		'#https?://(www\.)?(animoto|video214)\.com/play/.*#i' => 'https://animoto.com/oembeds/create',
		'#https?://(.+)\.tumblr\.com/.*#i'             => 'https://www.tumblr.com/oembed/1.0',
		'#https?://(www\.)?kickstarter\.com/projects/.*#i' => 'https://www.kickstarter.com/services/oembed',
		'#https?://kck\.st/.*#i'                       => 'https://www.kickstarter.com/services/oembed',
		'#https?://cloudup\.com/.*#i'                  => 'https://cloudup.com/oembed',
		'#https?://(www\.)?reverbnation\.com/.*#i'     => 'https://www.reverbnation.com/oembed',
		'#https?://videopress\.com/v/.*#'              => 'https://public-api.wordpress.com/oembed/',
		'#https?://(www\.)?reddit\.com/r/[^/]+/comments/.*#i' => 'https://www.reddit.com/oembed',
		'#https?://(www\.)?speakerdeck\.com/.*#i'      => 'https://speakerdeck.com/oembed.json',
		'#https?://(www\.)?screencast\.com/.*#i'       => 'https://api.screencast.com/external/oembed',
		'#https?://([a-z0-9-]+\.)?amazon\.(com|com\.mx|com\.br|ca)/.*#i' => 'https://read.amazon.com/kp/api/oembed',
		'#https?://([a-z0-9-]+\.)?amazon\.(co\.uk|de|fr|it|es|in|nl|ru)/.*#i' => 'https://read.amazon.co.uk/kp/api/oembed',
		'#https?://([a-z0-9-]+\.)?amazon\.(co\.jp|com\.au)/.*#i' => 'https://read.amazon.com.au/kp/api/oembed',
		'#https?://([a-z0-9-]+\.)?amazon\.cn/.*#i'     => 'https://read.amazon.cn/kp/api/oembed',
		'#https?://(www\.)?a\.co/.*#i'                 => 'https://read.amazon.com/kp/api/oembed',
		'#https?://(www\.)?amzn\.to/.*#i'              => 'https://read.amazon.com/kp/api/oembed',
		'#https?://(www\.)?amzn\.eu/.*#i'              => 'https://read.amazon.co.uk/kp/api/oembed',
		'#https?://(www\.)?amzn\.in/.*#i'              => 'https://read.amazon.in/kp/api/oembed',
		'#https?://(www\.)?amzn\.asia/.*#i'            => 'https://read.amazon.com.au/kp/api/oembed',
		'#https?://(www\.)?z\.cn/.*#i'                 => 'https://read.amazon.cn/kp/api/oembed',
		'#https?://www\.someecards\.com/.+-cards/.+#i' => 'https://www.someecards.com/v2/oembed/',
		'#https?://www\.someecards\.com/usercards/viewcard/.+#i' => 'https://www.someecards.com/v2/oembed/',
		'#https?://some\.ly\/.+#i'                     => 'https://www.someecards.com/v2/oembed/',
		'#https?://(www\.)?tiktok\.com/.*/video/.*#i'  => 'https://www.tiktok.com/oembed',
		'#https?://(www\.)?tiktok\.com/@.*#i'          => 'https://www.tiktok.com/oembed',
		'#https?://([a-z]{2}|www)\.pinterest\.com(\.(au|mx))?/.*#i' => 'https://www.pinterest.com/oembed.json',
		'#https?://(www\.)?wolframcloud\.com/obj/.+#i' => 'https://www.wolframcloud.com/oembed',
		'#https?://pca\.st/.+#i'                       => 'https://pca.st/oembed.json',
		'#https?://((play|www)\.)?anghami\.com/.*#i'   => 'https://api.anghami.com/rest/v1/oembed.view',
		'#https?://bsky.app/profile/.*/post/.*#i'      => 'https://embed.bsky.app/oembed',
		// les notres
		'http://huffduffer.com/*/*' => 'http://huffduffer.com/oembed',
		'http://nfb.ca/film/*' => 'http://www.nfb.ca/remote/services/oembed/',
		'http://dotsub.com/view/*' => 'http://dotsub.com/services/oembed',
		'http://clikthrough.com/theater/video/*' => 'http://clikthrough.com/services/oembed',
		'http://kinomap.com/*' => 'http://www.kinomap.com/oembed',
		'http://photobucket.com/albums/*' => 'http://api.photobucket.com/oembed',
		'http://photobucket.com/groups/*' => 'http://api.photobucket.com/oembed',
		'http://meetup.com/*' => 'https://api.meetup.com/oembed',
		'http://meetup.ps/*' => 'http://api.meetup.com/oembed',
		'http://techcrunch.com/*' => 'http://public-api.wordpress.com/oembed/1.0/',
		'http://my.opera.com/*' => 'http://my.opera.com/service/oembed',
		'http://www.collegehumor.com/video/*' => 'http://www.collegehumor.com/oembed.json',
		'http://*.onf.ca/*' => 'http://www.onf.ca/remote/services/oembed/',
		'http://vine.co/v/*' => 'https://vine.co/oembed.json',
		'http://*.calameo.com/books/*' => 'https://www.calameo.com/services/oembed',
		'http://*.calameo.com/read/*' => 'https://www.calameo.com/services/oembed',
		'http://*.arte.tv/*/videos/*' => 'https://api.arte.tv/api/player/v1/oembed/',
		// les autres
		'http://egliseinfo.catholique.fr/*' => 'http://egliseinfo.catholique.fr/api/oembed',

		#'https://gist.github.com/*' => 'http://github.com/api/oembed?format=json'
	];

	// pipeline pour permettre aux plugins d'ajouter/supprimer/modifier des providers
	$providers = pipeline('oembed_lister_providers', $providers);

	// merger avec la globale pour perso mes_options dans un site
	// pour supprimer un scheme il suffit de le renseigner avec un endpoint vide
	if (isset($GLOBALS['oembed_providers'])) {
		$providers = array_merge($providers, $GLOBALS['oembed_providers']);
	}

	if (!$avec_provider_interdits) {
		$providers = array_filter($providers);
	}

	return $providers;
}

/**
 * Reçois un tableau de providers et renvoie les expression régulières (sans marqueur)
 * correspondant aux clés
 * utilisé pour remplir _IFRAME_SAFE_DOMAINS
 * Par rapport au tableau fourni en entré, on prend que les clés, et on supprime
 *  - marqueur regexp
 *  - marqueur protocole
 *  - chemin au sein d'un domaine
 * Et on transforme si besoin les joker en joker regexp
 * @param array $providers tableau issue de oembed_lister_providers
 * @param string mark d'expression régulière à échappé
 * @return array $tableau marqué
**/
function oembed_allowed_domains(array $providers): array {
	$providers = array_keys($providers);
	$providers = array_map(function ($p) {
		$is_regexp = (substr($p, 0, 1) === '#');
		if ($is_regexp) {
			$p = preg_replace(',#.*$u,', '#', $p);
			$p = trim($p, '#');
		} else {
			$p = str_replace('*', '(.+)', $p);
		}
		$p = str_replace(['https://' ,'http://', 'https?://', 'http\:', 'https\:'], '', $p);
		$part = explode('/', $p);
		$p = $part[0];
		return $p;
	}, $providers);
	return $providers;
}


/**
 * @param string $url
 * @param bool|null $detecter_lien
 *    false : bloque la detection des liens quelle que soit la config du plugin
 *    true : force la detection des liens quelle que soit la config du plugin
 *    null : utilise la config du plugin
 * @return array|null
 */
function oembed_provider_from_url($url, ?bool $detecter_lien = null) {
	static $providers = [];

	if (!isset($providers[$detecter_lien])) {
		$providers[$detecter_lien] = [];
	}

	if (isset($providers[$detecter_lien][$url])) {
		return $providers[$detecter_lien][$url];
	}

	include_spip('inc/config');
	$provider = oembed_verifier_provider($url);
	// inconnu ?
	if (!$provider and $detecter_lien !== false) {
		if ($detecter_lien or lire_config('oembed/detecter_lien', 'non') == 'oui') {
			$provider = oembed_detecter_lien($url);
		}
	}

	// inconnu ou interdit ?
	if (!$provider or empty($provider['endpoint'])) {
		return $providers[$detecter_lien][$url] = null;
	}

	return $providers[$detecter_lien][$url] = $provider;
}

// Merci WordPress :)
// http://core.trac.wordpress.org/browser/trunk/wp-includes/class-oembed.php

/**
 * Récupérer les données oembed d'une url
 *
 * @param string $url url de la page qui contient le document à récupérer avec oembed
 * @param int $maxwidth largeur max du document
 *   null : la valeur configuree par defaut ou pour le provider est utilisee
 *   '' : pas de valeur max
 * @param int $maxheight hauteur max du document
 *   null : la valeur configuree par defaut ou pour le provider est utilisee
 *   '' : pas de valeur max
 * @param string $format format à utiliser pour la requete oembed (json ou xml)
 * @param bool|null $detecter_lien tenter la détection automatique de lien oembed dans la page indiquée
 *    false : bloque la detection des liens quelle que soit la config du plugin
 *    true : force la detection des liens quelle que soit la config du plugin
 *    null : utilise la config du plugin
 * @param bool $force_reload forcer le rechargement de l'oembed depuis la source sans utiliser le cache local
 * @return bool|array false si aucun retour ou erreur ; tableau des éléménents de la réponse oembed
 */
function oembed_recuperer_data($url, $maxwidth = null, $maxheight = null, $format = 'json', $detecter_lien = null, $force_reload = false) {
	static $cache = [];

	// compatibilite avec l'ancienne signature de la fonction : non equivalait a 'auto' (c'est a dire on applique la config du plugin)
	if ($detecter_lien === 'non') {
		$detecter_lien = null;
	}
	elseif ($detecter_lien) {
		$detecter_lien = true;
	}

	$provider = oembed_provider_from_url($url, $detecter_lien);
	// inconnu ou interdit ?
	if (!$provider) {
		return false;
	}

	$data_url = url_absolue($provider['endpoint'], url_de_base());
	// certains oembed fournissent un endpoint qui contient deja l'URL, parfois differente de celle de la page
	if (!parametre_url($data_url, 'url')) {
		$data_url = parametre_url($data_url, 'url', $url, '&');
	}

	if (!$maxwidth) {
		$maxwidth = lire_config('oembed/maxwidth', '600');
	}
	if (!$maxheight) {
		$maxheight = lire_config('oembed/maxheight', '400');
	}

	$data_url = parametre_url($data_url, 'maxwidth', $maxwidth, '&');
	$data_url = parametre_url($data_url, 'maxheight', $maxheight, '&');
	$data_url = parametre_url($data_url, 'format', $format, '&');

	if (isset($provider['provider_name']) and $provider['provider_name']) {
		$provider_name = $provider['provider_name'];
	} else {
		// pre-traitement du provider si besoin
		$provider_name = explode('//', $provider['endpoint']);
		$provider_name = explode('/', $provider_name[1]);
		$provider_name = reset($provider_name);
	}
	$provider_name = preg_replace(',\W+,', '_', strtolower($provider_name));
	if ($oembed_endpoint_pretraite = charger_fonction("pretraite_$provider_name", 'oembed/input', true)) {
		$a = func_get_args();
		$args = ['url' => array_shift($a)];
		if (count($a)) {
			$args['maxwidth'] = array_shift($a);
		}
		if (count($a)) {
			$args['maxheight'] = array_shift($a);
		}
		if (count($a)) {
			$args['format'] = array_shift($a);
		}
		$args['endpoint'] = $provider['endpoint'];
		$data_url = $oembed_endpoint_pretraite($data_url, $args);
	}

	if (isset($cache[$data_url])) {
		return $cache[$data_url];
	}

	$oembed_cache = sous_repertoire(_DIR_CACHE, 'oembed') . md5($data_url) . '.' . $format;
	// si cache oembed dispo et pas de recalcul demande, l'utiliser (perf issue)
	if (!$force_reload and file_exists($oembed_cache) and _VAR_MODE !== 'recalcul') {
		lire_fichier($oembed_cache, $cache[$data_url]);
		$cache[$data_url] = unserialize($cache[$data_url]);
		return $cache[$data_url];
	}

	$oembed_recuperer_url = charger_fonction('oembed_recuperer_url', 'inc');
	$cache[$data_url] = $oembed_recuperer_url($data_url, $url, $format);
	// si une fonction de post-traitement est fourni pour ce provider+type, l'utiliser
	if ($cache[$data_url]) {
		if (isset($cache[$data_url]['provider_name']) and $cache[$data_url]['provider_name']) {
			$provider_name2 = str_replace(' ', '_', strtolower($cache[$data_url]['provider_name']));
		} else {
			$provider_name2 = '';
		}
		$type = strtolower($cache[$data_url]['type'] ?? '');
		// securisons le nom de la fonction (provider peut contenir n'importe quoi)
		$f1 = preg_replace(',\W,', '', "posttraite_{$provider_name2}_$type");
		$f2 = preg_replace(',\W,', '', "posttraite_{$provider_name2}");
		$f3 = preg_replace(',\W,', '', "posttraite_{$provider_name}_$type");
		$f4 = preg_replace(',\W,', '', "posttraite_{$provider_name}");
		if (
			$oembed_provider_posttraite = charger_fonction($f1, 'oembed/input', true)
			or $oembed_provider_posttraite = charger_fonction($f2, 'oembed/input', true)
			or $oembed_provider_posttraite = charger_fonction($f3, 'oembed/input', true)
			or $oembed_provider_posttraite = charger_fonction($f4, 'oembed/input', true)
		) {
			$cache[$data_url] = $oembed_provider_posttraite($cache[$data_url], $url);
		}
		ecrire_fichier($oembed_cache, serialize($cache[$data_url]));
	}
	spip_log('infos oembed pour ' . $url . ' : ' . var_export($cache[$data_url], true), 'oembed.' . _LOG_DEBUG);

	return $cache[$data_url];
}

/**
 * Vérfier qu'une url est dans la liste des providers autorisés
 *
 * @param string $url l'url à tester
 * @return bool|array
 *   false si l'url n'est pas dans la liste (provider inconnu) ; details du
 *   provider dans un tabeau associatif si oui (avec un endpoint vide si
 *   le provider est explicitement interdit)
 */
function oembed_verifier_provider($url) {
	static $base = null;
	if (is_null($base)) {
		$base = url_de_base();
	}
	if (strpos($url, (string) $GLOBALS['meta']['adresse_site']) === 0 or strpos($url, (string) $base) === 0) {
		return ['endpoint' => ''];
	}

	$providers = oembed_lister_providers(true);
	foreach ($providers as $scheme => $endpoint) {
		if (!str_starts_with($scheme, '#')) {
			$regex = '#' . str_replace('\*', '(.+)', preg_quote($scheme, '#')) . '#';
			$regex = preg_replace('|^#http\\\://|', '#https?\://', $regex);
		} else {
			$regex = $scheme;
		}
		if (preg_match($regex, $url)) {
			return ['endpoint' => $endpoint];
		}
	}
	return false;
}

/**
 * Détecter les liens oembed dans le head d'une page web
 *
 * @param string $url url de la page à analyser
 * @return bool|array false si pas de lien ; description du provider oembed associe au lien
 */
function oembed_detecter_lien($url) {
	$providers = [];

	// s'assurer que l'URL n'est pas une url direct vers un media mais ressemble bien à l'URL d'une page HTML
	// pour eviter de faire des
	$parts = parse_url($url);
	// si on trouve une extension, verifier que c'est html ou htm, le reste ne nous interesse pas
	if (!empty($parts['path'])) {
		if (preg_match(',\.(\w+)$,', $parts['path'], $m)) {
			if (!in_array(strtolower($m[1]), ['html', 'htm'])) {
				spip_log("oembed_detecter_lien $url : on ignore l'extension " . $m[1], 'oembed' . _LOG_DEBUG);
				return false;
			}
		}
	}

	// on recupere le contenu de la page
	// on utilise recuperer_url_cache() avec un cache 24h pour ne pas passer son temps à faire de la detection sur les memes liens
	include_spip('inc/distant');
	$options = [
		'delai_cache' => in_array(_VAR_MODE, ['preview', 'recalcul']) ? 0 : 24 * 3600,
		'taille_max' => min(_INC_DISTANT_MAX_SIZE, 256000),
	];
	$res = recuperer_url_cache($url, $options);
	$html = '';
	if ($res and intval($res['status'] / 100) < 4 and !empty($res['page'])) {
		$html = $res['page'];
		// types de liens oembed à détecter
		$linktypes = [
			'application/json+oembed' => 'json',
			'text/json+oembed' => 'json', // ex de 500px
			'text/xml+oembed' => 'xml',
			'application/xml+oembed' => 'xml', // uniquement pour Vimeo
		];

		// on ne garde que le head de la page
		$head = substr($html, 0, stripos($html, '</head>'));

		// un test rapide...
		$tagfound = false;
		foreach ($linktypes as $linktype => $format) {
			if (stripos($head, $linktype)) {
				$tagfound = true;
				break;
			}
		}

		if ($tagfound && preg_match_all('/<link([^<>]+)>/i', $head, $links)) {
			if (!function_exists('extraire_attribut')) {
				include_spip('inc/filtres');
			}
			foreach ($links[0] as $link) {
				$type = extraire_attribut($link, 'type');
				$href = extraire_attribut($link, 'href');
				if (!empty($type) and !empty($linktypes[$type]) and !empty($href)) {
					$providers[$linktypes[$type]] = $href;
					// on a le json, ça nous suffit
					if ('json' == $linktypes[$type]) {
						break;
					}
				}
			}
		}
	}

	$res = [];

	// on préfère le json au xml
	if (!empty($providers['json'])) {
		$res['endpoint'] = $providers['json'];
	} elseif (!empty($providers['xml'])) {
		$res['endpoint'] = $providers['xml'];
	} else {
		return false;
	}

	// detecter certains providers specifiques : ex mastodon, chaque instance a son nom et on peut pas l'identifier par son URL
	if (strpos($html, '//github.com/tootsuite/mastodon') !== false or strpos($html, '//joinmastodon.org') !== false) {
		$res['provider_name'] = 'Mastodon';
	}

	return $res;
}


/**
 * Embarquer un lien oembed si possible
 * @param string $lien
 * @return string
 */
function oembed_embarquer_lien($lien) {

	$url = extraire_attribut($lien, 'href');
	$texte = null;
	if ($url and oembed_provider_from_url($url)) {
		$fond = recuperer_fond('modeles/oembed', ['url' => $url, 'lien' => $lien]);
		if ($fond = trim($fond)) {
			$texte = $fond;
		}
	}

	return $texte;
}
