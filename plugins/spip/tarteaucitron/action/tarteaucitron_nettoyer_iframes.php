<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Conversion des iframe en modèles pour TarteAuCitron
 * appelé avec ?action=tarteaucitron_nettoyer_iframes
 * autorisé pour les seuls webmestres
 */
function action_tarteaucitron_nettoyer_iframes_dist() {
	include_spip('inc/autoriser');
	include_spip('inc/filtres');
	include_spip('action/editer_objet');
	if (!autoriser('webmestre')) {
		die('Pas autorise');
	}
	echo '<h1>Conversion des &lt;iframe&gt;</h1>';
	$simu = true;
	if (_request('modif')) {
		$simu = false;
	}
	if ($simu) {
		echo "<p><strong>mode SIMULATION</strong> (ajoutez &modif=1 dans l'url pour modifier les contenus)</p>";
	}

	$tables = [
		'spip_articles' => ['descriptif', 'chapo', 'texte', 'ps'],
	];

	foreach ($tables as $table => $champs) {
		$objet = objet_type($table);
		foreach ($champs as $champ) {
			$primary = id_table_objet($table);
			$res = sql_select("$primary,$champ", $table, "$champ LIKE '%iframe%' OR $champ LIKE '%object%'");
			while ($row = sql_fetch($res)) {
				$pre = "$primary=" . $row[$primary] . ":$champ:";

				$texte = $row[$champ];
				$iframes = extraire_balises($texte, 'iframe');
				if (count($iframes)) {
					foreach ($iframes as $iframe) {
						$url = '';
						$src = extraire_attribut($iframe, 'src');
						if (strncmp($src, '//', 2) == 0) {
							$src = 'http:' . $src;
						}
						if (strpos($iframe, 'youtube') !== false) {
							if (strpos($src, '/embed/') !== false) {
								$url = str_replace('?', '&', $src);
								$url = str_replace('/embed/videoseries&', '/playlist?', $url);
								$url = str_replace('/embed/', '/watch?v=', $url);
								$url = str_replace('&feature=player_embedded', '', $url);
								preg_match(
									'%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
									$url,
									$match
								);
								$url = '<tac_youtube|id=' . $match[1] . '>';

								echo "$pre Youtube " . entites_html($url) . '<br />';
							}
							if (!$url) {
								var_dump($row);
								var_dump(entites_html($iframe));
								die('youtube inconnue');
							}
						} elseif (strpos($iframe, 'dailymotion') !== false) {
							if (strpos($src, '/embed/') !== false) {
								$url = str_replace('/embed/', '/', $src);
								$url = explode('?', $url);
								$url = reset($url);
								$url = '<tac_dailymotion|id=' . strtok(basename($url), '_') . '>';
								echo "$pre DailyMotion" . entites_html($url) . '<br />';
							}
							if (!$url) {
								var_dump($row);
								var_dump($iframe);
								die('dailymotion inconnue');
							}
						} elseif (strpos($iframe, 'player.vimeo') !== false) {
							if (strpos($src, '/video/') !== false) {
								$url = str_replace('/video/', '/', $src);
								$url = str_replace('player.vimeo', 'vimeo', $url);
								$url = explode('?', $url);
								$url = reset($url);
								preg_match(
									'%https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)%i',
									$url,
									$match
								);
								$url = '<tac_vimeo|id=' . $match[3] . '>';
								echo "$pre Vimeo " . entites_html($url) . '<br />';
							}
							if (!$url) {
								var_dump($row);
								var_dump($iframe);
								die('vimeo inconnue');
							}
						} /*elseif (strpos($iframe, 'soundcloud') !== false) {
							// un peu complique :
							// il faut faire une requete oembed sur l'url api, avec iframe=false
							// pour recuperer du html avec un lien vers la page soundcloud
							parse_str(end(explode('?', $src)), $args);
							$api_url = $args['url'];
							include_spip('inc/oembed');
							include_spip('inc/distant');
							$provider = oembed_verifier_provider($api_url);
							$data_url = parametre_url(url_absolue($provider['endpoint'], url_de_base()), 'url', $api_url, '&');
							$data_url = parametre_url($data_url, 'format', 'json', '&');
							$data_url = parametre_url($data_url, 'iframe', 'false', '&');
							$json = recuperer_page($data_url);
							$json = json_decode($json, true);
							$link = extraire_balise($json['html'], 'a');
							if ($url = extraire_attribut($link, 'href')) {
								echo "$pre SoundCloud $url<br />";
							}
							if (!$url) {
								var_dump($row);
								var_dump($iframe);
								die('soundcloud inconnue');
							}
						} */ elseif (strpos($iframe, 'www.google.com/calendar') !== false) {
							if (strpos($src, '/embed') !== false) {
								preg_match('#(src=)(\w+)$#', $src, $match);
								$url = '<tac_gagenda|id=' . $match[2] . '>';

								echo "$pre Google Agenda " . entites_html($url) . '<br />';
							}
						} elseif (strpos($iframe, 'www.canal-u.tv/video') !== false) {
							if (strpos($src, '/embed') !== false) {
								preg_match('#(/embed.1/)(.+)(\?)#', $src, $match);
								$url = '<tac_canalu|id=' . $match[2] . '>';
								$divs = extraire_balises($texte, 'div');
								foreach ($divs as $div) {
									if (strpos($div, $match[2]) !== false) {
										$iframe = $div;
									}
								}

								echo "$pre Canal-U.tv " . entites_html($url) . '<br />';
							}
						} elseif (strpos($iframe, 'webtv.normandie-univ.fr') !== false) {
							if (strpos($src, '/permalink/') !== false) {
								preg_match('#(/permalink/)(\w+)(/iframe)#', $src, $match);
								$url = '<tac_webtvnu|id=' . $match[2] . '>';
								echo "$pre WebTV Normandie Université " . entites_html($url) . '<br />';
							}
						} else {
							echo "$pre iframe inconnue : " . entites_html($iframe) . '<br />';
						}
						if ($url) {
							$texte = str_replace($iframe, "\n\n" . $url . "\n\n", $texte);
							if (preg_match(',<center>\s*' . preg_quote($url, ',') . '.*</center>,Uims', $texte, $m)) {
								$texte = str_replace($m[0], "\n\n" . $url . "\n\n", $texte);
							}
							$texte = preg_replace(',\s+' . preg_quote($url, ',') . '\s+,ims', "\n\n" . $url . "\n\n", $texte);
						}
					}
					if ($texte !== $row[$champ]) {
						echo "$pre Corrige $champ <br />";
						if (!$simu) {
							echo "$pre Corrige $champ <br />";
							objet_modifier($objet, $row[$primary], [$champ => $texte]);
						} else {
							echo "SIMU : $pre Corrige $champ <br />";
						}
					}
				}
				$objects = extraire_balises($texte, 'object');
				if (count($objects)) {
					foreach ($objects as $object) {
						$url = '';
						$src = extraire_attribut($object, 'data');
						if (strncmp($src, '//', 2) == 0) {
							$src = 'http:' . $src;
						}
						if (strpos($src, 'youtube') !== false) {
							if (strpos($src, '/v/') !== false) {
								$url = str_replace('?', '&', $src);
								$url = str_replace('/v/', '/watch?v=', $url);
							} elseif (strpos($src, '/embed/') !== false) {
								$url = str_replace('?', '&', $src);
								$url = str_replace('/embed/', '/watch?v=', $url);
							}
							if (!$url) {
								var_dump($row);
								var_dump(entites_html($object));
								die('youtube inconnue');
							}
							preg_match(
								'%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
								$url,
								$match
							);
							$url = '<tac_youtube|id=' . $match[1] . '>';
							echo "$pre Youtube " . entites_html($url) . '<br />';

						} elseif (strpos($src, 'dailymotion') !== false) {
							if (strpos($src, '/swf/video/') !== false) {
								$url = str_replace('/swf/video/', '/video/', $src);
								$url = explode('?', $url);
								$url = reset($url);
							} elseif (strpos($src, '/swf/') !== false) {
								$url = str_replace('/swf/', '/video/', $src);
								$url = explode('?', $url);
								$url = reset($url);
							}
							if (!$url) {
								var_dump($row);
								var_dump($object);
								die('dailymotion inconnue');
							}
							$url = '<tac_dailymotion|id=' . strtok(basename($url), '_') . '>';
							echo "$pre DailyMotion" . entites_html($url) . '<br />';

						} elseif (strpos($src, 'vimeo.com') !== false) {
							if (strpos($src, 'moogaloop') !== false
								and $id = parametre_url($src, 'clip_id')) {
								$url = "https://vimeo.com/$id";
							} elseif (strpos($src, '/video/') !== false) {
								$url = $src;
							}
							if (!$url) {
								var_dump($row);
								var_dump(entites_html($object));
								die('vimeo inconnue');
							}
							preg_match(
								'%https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)%i',
								$url,
								$match
							);
							$url = '<tac_vimeo|id=' . $match[3] . '>';
							echo "$pre Vimeo " . entites_html($url) . '<br />';

						} else {
							echo "$pre object inconnue : " . entites_html($object) . '<br />';
						}
						if ($url) {
							$texte = str_replace($object, "\n\n" . $url . "\n\n", $texte);
							if (preg_match(',<center>\s*' . preg_quote($url, ',') . '.*</center>,Uims', $texte, $m)) {
								$texte = str_replace($m[0], "\n\n" . $url . "\n\n", $texte);
							}
							$texte = preg_replace(',\s+' . preg_quote($url, ',') . '\s+,ims', "\n\n" . $url . "\n\n", $texte);
						}
					}
					if ($texte !== $row[$champ]) {
						if (!$simu) {
							echo "$pre Corrige $champ <br />";
							objet_modifier($objet, $row[$primary], [$champ => $texte]);
						} else {
							echo "SIMU : $pre Corrige $champ <br />";
						}
					}
				}
			}
		}
	}
}
