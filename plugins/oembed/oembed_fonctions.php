<?php

/**
 * Plugin oEmbed
 * Licence GPL3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// renvoyer un mim_type text/oembed pour les videos oembed
function mime_type_oembed($id_document) {
	if (!($id_document = intval($id_document))) {
		return '';
	}
	$extension = sql_getfetsel(
		'extension',
		'spip_documents',
		'id_document = ' . intval($id_document)
	);
	$mime_type = sql_getfetsel(
		'mime_type',
		'spip_types_documents',
		'extension = ' . sql_quote($extension)
	);
	if (
		$mime_type == 'text/html'
		and sql_getfetsel('oembed', 'spip_documents', "id_document=$id_document")
	) {
		$mime_type = 'text/oembed';
	}
	return $mime_type;
}

// balise #MIME_TYPE pour oembed
function balise_MIME_TYPE_dist($p) {
	$b = $p->nom_boucle ?: $p->id_boucle;
	$key = $p->boucles[$b]->primary;
	/**
	 * Si la clé est extension, on est dans une boucle sur la table spip_documents
	 */
	if ($key == 'extension') {
		$p->code = champ_sql('mime_type', $p);
	} else {
		/* explorer la pile memoire pour atteindre le 'vrai' champ */
		$id_document = champ_sql('id_document', $p);
		/* le code php qui sera execute */
		$p->code = 'mime_type_oembed(' . $id_document . ')';
	}
	return $p;
}

/**
 * un filtre pour json_encode avec les bonnes options, pour l'export json des modeles
 * @param $texte
 * @return string
 */
function json_encode_html($texte) {
	#$texte = json_encode($texte,JSON_HEX_TAG);
	$texte = json_encode($texte, JSON_THROW_ON_ERROR);
	$texte = str_replace(['<', '>'], ['\u003C', '\u003E'], $texte);
	return $texte;
}

/**
 * Filtre utilisable dans un squelette
 * |oembed{550,300}
 *
 * @param string $url
 * @param int $maxwidth
 * @param int $maxheight
 * @return string
 */
function oembed($url, $maxwidth = 0, $maxheight = 0) {
	include_spip('inc/config');
	include_spip('inc/oembed');

	if (oembed_provider_from_url($url)) {
		$fond = recuperer_fond(
			'modeles/oembed',
			['url' => $url, 'lien' => '', 'maxwidth' => $maxwidth, 'maxheight' => $maxheight]
		);
		if ($fond = trim($fond)) {
			return $fond;
		}
	}

	return $url;
}

/**
 * Determiner le media en fonction du oembed type
 * @param $type
 * @return mixed|string
 */
function oembed_media_from_type($type) {
	$medias = ['photo' => 'image', 'video' => 'video', 'sound' => 'audio', 'rich' => 'video'];
	if (!empty($medias[$type])) {
		return $medias[$type];
	}
	return  $type;
}

/**
 * Modifier l'iframe d'une video pour la passer en autoplay
 * quand on l'injecte en async dans le html
 * @param $html
 * @return mixed
 */
function oembed_force_video_autoplay($html) {
	if (
		$e = extraire_balise($html, 'iframe')
		and $src = extraire_attribut($e, 'src')
	) {
		$src_amp = parametre_url($src, 'dummy', null);
		if (strpos($src, 'soundcloud') !== false) {
			$src_autoplay = parametre_url($src, 'auto_play', '1', '&');
		} else {
			$src_autoplay = parametre_url($src, 'autoplay', '1', '&');
		}
		if (strpos($html, (string) $src_amp)) {
			$html = str_replace($src_amp, $src_autoplay, $html);
		} else {
			$html = str_replace($src, $src_autoplay, $html);
		}
	}
	return $html;
}

include_spip('inc/ressource');
if (function_exists('inc_ressource_dist')) {
	// plugin ressource + SPIP 3.1+
	function inc_ressource($rac) {
		$html = oembed_traiter_ressource($rac);
		if (is_null($html)) {
			$html = inc_ressource_dist($rac);
		}
		return $html;
	}
} else {
	// SPIP 3.0 ou pas de plugin ressource
	function inc_ressource_dist($rac) {
		return oembed_traiter_ressource($rac);
	}
}

function oembed_traiter_ressource($rac) {
	static $null_allowed = null;

	include_spip('inc/lien');
	$url = explode(' ', trim($rac, '<>'));
	$url = $url[0];

	$texte = null;
	# <http://url/absolue>
	if (preg_match(',^https?://,i', $url)) {
		include_spip('inc/oembed');
		$lien = PtoBR(propre('[->' . $url . ']'));
		// null si pas embedable
		$texte = oembed_embarquer_lien($lien);
		if ($texte) {
			$texte = "<html>$texte</html>";
		}
	}

	return $texte;
}

/**
 * Securiser la vignette utilisee pour les videos oembed en mode async :
 * si c'est une image locale il faut
 * - en faire une copie dans local/ via image_reduire pour le cas ou acces_retreint
 * - appliquer url_absolue dessus car si on est sur une page avec url arbo le <base> ne s'appliquera pas dans le style inline
 * @param string $img
 * @return string
 */
function oembed_safe_thumbnail($img) {

	if ($img and !tester_url_absolue($img) and file_exists($img)) {
		if (!function_exists('image_filtrer')) {
			include_spip('inc/filtres');
		}
		$img = image_filtrer(['image_reduire', $img, 1200, 1200]);
		$img = image_filtrer(['image_graver', $img]);
		$img = extraire_attribut($img, 'src');
		$img = url_absolue($img);
	}

	return $img;
}

/**
 * Ajouter un lien vers l'URL d'origine du document oembed quand on utilise un titre personalise
 * @param string $titre
 * @param string $url
 * @return string
 */
function oembed_lier_titre($titre, $url) {
	if (
		strpos($titre, '[') !== false
		and strpos($titre, '->') !== false
		and strpos($titre, ']') !== false
	) {
		if (strpos(propre($titre), '<a') !== false) {
			return $titre;
		}
	}
	if (strpos($titre, '<a') !== false) {
		return $titre;
	}
	if (!$url) {
		return $titre;
	}
	$titre = str_replace(['[', ']'], ['&#91;','&#93;'], $titre);
	return "[ $titre -> $url ]";
}


/**
 * Styliser le modele media : si ça concerne un document oembed, on force l'utilisation de <emb> car les autres modeles ne sont pas pertinents
 * @param string $modele
 * @param int $id
 * @return string
 */
function oembed_medias_modeles_styliser($modele, $id) {
	switch ($modele) {
		case 'img':
		case 'doc':
		case 'emb':
		case 'video':
			if ($oembed = sql_getfetsel('oembed', 'spip_documents', 'id_document=' . intval($id))) {
				$modele = 'emb';
			}
			break;
	}
	return medias_modeles_styliser($modele, $id);
}