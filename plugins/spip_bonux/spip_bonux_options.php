<?php

/**
 * Plugin Spip-Bonux
 * Le plugin qui lave plus SPIP que SPIP
 * (c) 2008 Mathieu Marcillaud, Cedric Morin, Tetue
 * Licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


if (!defined('_PREVISU_TEMPORAIRE_ACTIVE')) {
	define('_PREVISU_TEMPORAIRE_ACTIVE', true);
}

if (
	_request('var_mode') == 'preview'
	and _PREVISU_TEMPORAIRE_ACTIVE
	and $cle = _request('var_relecture')
) {
	include_spip('spip_bonux_fonctions');
	if (previsu_verifier_cle_temporaire($cle)) {
		include_spip('inc/autoriser');
		autoriser_exception('previsualiser', '', 0);
		define('_VAR_PREVIEW_EXCEPTION', true);
	}
}

function spip_bonux_affichage_final($flux) {
	if (_PREVISU_TEMPORAIRE_ACTIVE and defined('_VAR_PREVIEW') and _VAR_PREVIEW and !empty($GLOBALS['html'])) {
		$p = stripos($flux, '</body>');
		$url_relecture = parametre_url(self(), 'var_mode', 'preview', '&');
		$js = '';
		if (!defined('_VAR_PREVIEW_EXCEPTION')) {
			include_spip('plugins/installer');
			include_spip('inc/securiser_action');
			$url_relecture = parametre_url($url_relecture, 'var_previewtoken', calculer_token_previsu(url_absolue($url_relecture)), '&');
			$label = 'Relecture temporaire';
		} else {
			$label = _T('previsualisation');
			$js = "jQuery('.spip-previsu').html('Relecture temporaire');";
		}
		$js .= "jQuery('#spip-admin').append('<a class=\"spip-admin-boutons review_link\" href=\"$url_relecture\">$label</a>');";
		$js = "jQuery(function(){ $js });";
		$js = "<script>$js</script>";
		$flux = substr_replace($flux, $js, $p, 0);
	}
	return $flux;
}

if (!function_exists('_T_ou_typo')) {
	/**
	 * une fonction qui regarde si $texte est une chaine de langue
	 * de la forme <:qqch:>
	 * si oui applique _T()
	 * si non applique typo() suivant le mode choisi
	 *
	 * @param mixed $valeur
	 *     Une valeur à tester. Si c'est un tableau, la fonction s'appliquera récursivement dessus.
	 * @param string $mode_typo
	 *     Le mode d'application de la fonction typo(), avec trois valeurs possibles "toujours", "jamais" ou "multi".
	 * @return mixed
	 *     Retourne la valeur éventuellement modifiée.
	 */
	function _T_ou_typo($valeur, $mode_typo = 'toujours', $connect = null, $env = []) {
		if (!in_array($mode_typo, ['toujours', 'multi', 'jamais'])) {
			$mode_typo = 'toujours';
		}

		// Si la valeur est bien une chaine (et pas non plus un entier déguisé)
		if (is_string($valeur) and !is_numeric($valeur)) {
			$presence_idiome = strpos($valeur, '<:');
			// Si la chaine est du type <:truc:> on passe à _T()
			if ($presence_idiome === 0
				and preg_match('/^\<:([^>]*?):\>$/', $valeur, $match)
			) {
				$valeur = _T($match[1]);
			} else {
				// Sinon on la passe a typo() si c'est pertinent
				if ($presence_idiome !== false) {
					if (class_exists(Spip\Texte\Collecteur\Idiomes::class)) {//SPIP 4.2 et >
						$idiomes = new Spip\Texte\Collecteur\Idiomes();
						$presence_idiome = $idiomes->collecter($valeur, ['detecter_presence'=>True]);
					} else {// SPIP 4.1 et <
						include_spip('inc/texte');
						$presence_idiome = preg_match(_EXTRAIRE_IDIOME, $valeur);
					}
				}

				if (
					$mode_typo === 'toujours'
					or ($mode_typo === 'multi' and ($presence_idiome or strpos($valeur, '<multi>') !== false))
				) {
					include_spip('inc/texte');
					// définir le connect pour éviter de déclencher les sécurités dans typo
					// mais si on est en GLOBALS['filtrer_javascript'] == -1 alors le résultat passera dans safehtml
					$env['espace_prive'] = false;
					$valeur = typo($valeur, true, $connect ?? '', $env);
					// et sécuriser quand même le tout
					$valeur = interdire_scripts($valeur);
				}
			}
		}
		// Si c'est un tableau, on réapplique la fonction récursivement
		elseif (is_array($valeur)) {
			foreach ($valeur as $cle => $valeur2) {
				$valeur[$cle] = _T_ou_typo($valeur2, $mode_typo, $connect, $env);
			}
		}

		return $valeur;
	}
}
/**
 * Insère toutes les valeurs du tableau $arr2 après (ou avant) $cle dans le tableau $arr1.
 * Si $cle n'est pas trouvé, les valeurs de $arr2 seront ajoutés à la fin de $arr1.
 *
 * La fonction garde autant que possible les associations entre les clés. Elle fonctionnera donc aussi bien
 * avec des tableaux à index numérique que des tableaux associatifs.
 * Attention tout de même, elle utilise array_merge() donc les valeurs de clés étant en conflits seront écrasées.
 *
 * @param array $arr1 Tableau dans lequel se fera l'insertion
 * @param unknown_type $cle Clé de $arr1 après (ou avant) laquelle se fera l'insertion
 * @param array $arr2 Tableau contenant les valeurs à insérer
 * @param bool $avant Indique si l'insertion se fait avant la clé (par défaut c'est après)
 * @return array Retourne le tableau avec l'insertion
 */
if (!function_exists('spip_array_insert')) {
function spip_array_insert($arr1, $cle, $arr2, $avant = false) {
	$index = array_search($cle, array_keys($arr1));
	if ($index === false) {
		$index = count($arr1); // insert @ end of array if $key not found
	} else {
		if (!$avant) {
			$index++;
		}
	}
	$fin = array_splice($arr1, $index);
	return array_merge($arr1, $arr2, $fin);
}
}


if (!function_exists('text_truncate')) {
/**
* Truncates text.
*
* Cuts a string to the length of $length and replaces the last characters
* with the ending if the text is longer than length.
*
* ### Options:
*
* - `ending` Will be used as Ending and appended to the trimmed string
* - `exact` If false, $text will not be cut mid-word
* - `html` If true, HTML tags would be handled correctly
*
* @param string  $text String to truncate.
* @param integer $length Length of returned string, including ellipsis.
* @param array $options An array of html attributes and options.
* @return string Trimmed string.
* @access public
* @link https://api.cakephp.org/4.0/class-Cake.Utility.Text.html#truncate
*/
function text_truncate($text, $length = 100, $options = []) {
	$default = [
		'ending' => '...', 'exact' => true, 'html' => false
	];
	$options = array_merge($default, $options);
	extract($options);

	if ($html) {
		if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		$totalLength = mb_strlen(strip_tags($ending));
		$openTags = [];
		$truncate = '';

		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($openTags, $tag[2]);
				} elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
					$pos = array_search($closeTag[1], $openTags);
					if ($pos !== false) {
						array_splice($openTags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
			if ($contentLength + $totalLength > $length) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entitiesLength <= $left) {
							$left--;
							$entitiesLength += mb_strlen($entity[0]);
						} else {
							break;
						}
					}
				}
				$truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ($totalLength >= $length) {
				break;
			}
		}
	} else {
		if (mb_strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = mb_substr($text, 0, $length - mb_strlen($ending));
		}
	}
	if (!$exact) {
		$spacepos = mb_strrpos($truncate, ' ');
		if (isset($spacepos)) {
			if ($html) {
				$bits = mb_substr($truncate, $spacepos);
				preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
				if (!empty($droppedTags)) {
					foreach ($droppedTags as $closingTag) {
						if (!in_array($closingTag[1], $openTags)) {
							array_unshift($openTags, $closingTag[1]);
						}
					}
				}
			}
			$truncate = mb_substr($truncate, 0, $spacepos);
		}
	}
	$truncate .= $ending;

	if ($html) {
		foreach ($openTags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}

	return $truncate;
}
}
