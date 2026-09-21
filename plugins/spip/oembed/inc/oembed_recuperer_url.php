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
 * Recuperer une URL oembed, si possible via curl et IPv4 pour contourner le bug de Youtube sur les IPv6
 *
 * @param string $oembed_url
 * @param string $url
 * @param string $format
 * @return bool|mixed|string
 */
function inc_oembed_recuperer_url($oembed_url, $url, $format) {
	$erreur = '';

	// on recupere le contenu de la page
	// si possible via curl en IPv4 car youtube bug en IPv6
	if (function_exists('curl_init')) {
		spip_log('Requete oembed (curl) pour ' . $url . ' : ' . $oembed_url, 'oembed.' . _LOG_DEBUG);
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $oembed_url);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$browser = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0';
		curl_setopt($c, CURLOPT_USERAGENT, $browser);
		//curl_setopt($c, CURLOPT_SSLVERSION, 1);

		// essayer d'eviter l'erreur 35 sur le protocole SSL
		// https://stackoverflow.com/questions/58342699/php-curl-curl-error-35-error1414d172ssl-routinestls12-check-peer-sigalgwr
		// non supporte partout, provoque une erreur 59
		// curl_setopt($c, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');

		// indiquer un referer : si jamais la diffusion du contenu est limitee au site, ca permet d'en recuperer les infos
		// ou en tout cas ca donne plus de chance...
		$referer = $GLOBALS['meta']['adresse_site'] . '/';
		curl_setopt($c, CURLOPT_REFERER, $referer);

		if (!empty($GLOBALS['meta']['http_proxy'])) {
			include_spip('inc/distant');
			$host = @parse_url($url, PHP_URL_HOST);
			if ($http_proxy = need_proxy($host)) {
				curl_setopt($c, CURLOPT_PROXY, $http_proxy);
			}
		}

		// the real trick for Youtube :
		// http://stackoverflow.com/questions/26089067/youtube-oembed-api-302-then-503-errors
		curl_setopt($c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		$data = curl_exec($c);

		if(!curl_errno($c) && (200 !== curl_getinfo($c,CURLINFO_HTTP_CODE))){
			$erreur = $data; // sauvegarder la réponse raw pour le log
			$data = null;
		}

		/** code mort : vieux patch introduit par https://git.spip.net/spip-contrib-extensions/oembed/commit/9146311a
		 *  pour fixer les erreurs SSL sur certains serveurs masto mais pas très viable en prod
		$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
		if (!$data or intval($status / 100) == 4) {
			$errno = curl_errno($c);
			$erreur = "Status $status Error $errno " . curl_error($c);

			// si c'est une erreur de protocole SSL, on tente avec un exec mechant car ca peut venir de la version de CURL PHP
			// (ca marche au moins en local)
			if (!$data and $errno == 35 and function_exists('exec')) {
				exec('curl --silent --location ' . escapeshellarg($oembed_url), $output);
				$data = implode("\n", $output);
			} else {
				$data = '';
			}
		}
		*/

		curl_close($c);
	} else {
		spip_log('Requete oembed (recuperer_page / recuperer_url) pour ' . $url . ' : ' . $oembed_url, 'oembed.' . _LOG_DEBUG);
		include_spip('inc/distant');
		// recuperer_page utilise par defaut l'adresse du site comme $referer
		$data = recuperer_url($oembed_url);
		if(200 === $data['status']){
			$data = $data['page'];
		} else {
			$erreur = $data['page'];
			$data = null;
		}
	}

	if (!$data) {
		spip_log('infos oembed brutes pour ' . "$url | $oembed_url" . ' : ' . "ECHEC $erreur", 'oembed.' . _LOG_INFO_IMPORTANTE);
	} else {
		spip_log('infos oembed brutes pour ' . "$url | $oembed_url" . ' : ' . (($format == 'html') ? substr($data, 0, 100) : $data), 'oembed.' . _LOG_DEBUG);
	}

	if ($data) {
		if ($format == 'json') {
			try {
				$data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
				$data['oembed_url_source'] = $url;
				$data['oembed_url'] = $oembed_url;
			} catch (JsonException $e) {
				$data = null;
				spip_log('Failed to parse Json data : ' . $e->getMessage(), 'oembed.' . _LOG_ERREUR);
			}
		}
		// TODO : format xml
		//if ($format == 'xml')
		//	$cache[$oembed_url] = false;
	}
	return $data;
}
