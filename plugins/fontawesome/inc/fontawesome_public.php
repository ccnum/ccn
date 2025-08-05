<?php
/**
 * Fonctions utiles au plugin Font Awesome
 *
 * @plugin     Font Awesome
 * @copyright  2020
 * @author     Nursit
 * @licence    GNU/GPL
 * @package    SPIP\Fontawesome\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!function_exists('balise_ICON_dist')) {
	// code issu du fichier zcore_options si on a pas le plugin z-core

	/**
	 * utiliser une icone standard du sprite par defaut :
	 * #ICON{search,icon-sm,Rechercher}
	 *
	 * utiliser une icone #search definie dans un svg inline de la page
	 * #ICON{#search,icon-sm,Rechercher}
	 *
	 * utiliser une l'icone #search definie dans un svg externe (qui sera resolu via #CHEMIN)
	 * #ICON{img/sprite.svg#search,icon-sm,Rechercher}
	 *
	 * utiler une icone svg du path, sans connaitre son id
	 * #ICON{img/mon_icone_search.svg,icon-sm,Rechercher}
	 *
	 * @param $p
	 * @return mixed
	 */
	function balise_ICON_dist($p) {
		$_name = interprete_argument_balise(1, $p);
		if (!$_name) {
			// compat avec les champs #ICON utilises dans composition et noizetier : pas d'argument = champ sql (ou DATA)
			$_icon = champ_sql('icon', $p);
			$p->code = $_icon;
		}
		else {
			$_class = interprete_argument_balise(2, $p);
			if (!$_class) {
				$_class = "''";
			}
			$_alt = interprete_argument_balise(3, $p);
			if (!$_alt) {
				$_alt = "''";
			}
			$p->code = "afficher_icone_svg($_name, $_class, $_alt)";
		}

		$p->interdire_scripts = false;
		return $p;
	}

	/**
	 * Fonction interne utilisee par la balise #ICON
	 * @param string $name
	 * @param string $class
	 * @param string $alt
	 * @return string
	 */
	function afficher_icone_svg($name, $class = '', $alt = '') {
		$icone_href_class_from_name = chercher_filtre("icone_href_class_from_name");
		list($href, $class_base) = $icone_href_class_from_name($name);
		if (!$name) {
			return $href;
		}

		if ($href) {
			if ($class_base = trim($class_base)) {
				$class_base = ' icon-' . $class_base;
			}
			if ($class = trim($class)) {
				$class = preg_replace(",[^\w\s\-],", "", $class);
			}

			if (strpos($href, '#') === false) {
				$id = "icon-title-" . substr(md5("$name:$alt:$href"),0,4);
				$svg = afficher_icone_inline_svg(supprimer_timestamp($href), $id, $alt);
			}
			else {
				/*
					<svg aria-labelledby="my-icon-title" role="img">
					<title id="my-icon-title">Texte alternatif</title>
					<use xlink:href="bytesize-symbols.min.svg#search"></use>
				</svg>
					<svg aria-hidden="true" role="img">
					<use xlink:href="bytesize-symbols.min.svg#search"></use>
					</svg>
				*/
				// width="0" height="0" -> rien ne s'affiche si on a pas la CSS icons.css
				$svg = "<svg role=\"img\" width=\"0\" height=\"0\"";
				if ($alt) {
					$id = "icon-title-" . substr(md5("$name:$alt:$href"),0,4);
					$svg .= " aria-labelledby=\"$id\"><title id=\"$id\">" . entites_html($alt)."</title>";
				}
				else {
					$svg .= ">";
				}
				$svg .= "<use xlink:href=\"$href\"></use>";
				$svg .= "</svg>";
			}

			if ($svg) {
				return "<i class=\"icon{$class_base}" . ($class ? " $class" : "") . "\">$svg</i> ";
			}
		}
		return "";
	}

	/**
	 * function qui permet d'afficher une icone svg inline
	 * La fonction supprime tout ce qui se trouve au dessus de la balise <svg>
	 * et force un width=0 et un height=0 car ils seront definis en CSS
	 * l'image sera toujours affichee au format carre
	 *
	 * @param string $svg_file
	 *   chemin du fichier
	 * @param string $id
	 * @param string $title
	 * @return string
	 *  le code svg
	 */
	function afficher_icone_inline_svg($svg_file, $id = '', $title = ''){

		if (!file_exists($svg_file) or !$svg = file_get_contents($svg_file)) {
			return;
		}

		$svg = explode("<svg", $svg);
		array_shift($svg);
		array_unshift($svg, "");
		$svg = implode("<svg", $svg);

		$svg = explode(">", $svg, 2);
		$balise_svg = array_shift($svg);

		if ($title) {
			// on ajoute le aria-labelledby si besoin
			$balise_svg .= ' aria-labelledby="'.$id.'"';
			$title = "<title id='".$id."'>".entites_html($title)."</title>";
		}
		// on supprime id, width et height du svg
		$balise_svg = preg_replace('/(\s+(id|width|height)=["\'].*?["\'])/s', '', $balise_svg);
		// on ajoute le role, width et height
		// width="0" height="0" -> rien ne s'affiche si on a pas la CSS icons.css
		$balise_svg .= ' role="img" width="0" height="0">' . $title;

		$svg = $balise_svg . end($svg);

		return $svg;
	}

	/**
	 * filtre surchargeable pour determiner le href et la class en fonction du nom de l'icone demandee
	 * @param string $name
	 * @return array
	 */
	function filtre_icone_href_class_from_name_dist($name) {
		static $sprite_files = array();

		if (strpos($name,'#') !== false or strpos($name,'/') !== false or strpos($name,'.svg') !== false) {
			// l'ancre est fournie explicitement (sprite inline)
			// voire le nom du fichier sprite svg
			list($filename, $anchor) = array_pad(explode('#', trim($name), 2), 2, null);
			// sanitizer l'ancre pour la class
			if ($anchor) {
				$class = preg_replace(",[^\w\-],", "", $anchor);
			}
			else {
				$class = preg_replace(",[^\w\-],", "", basename($filename, '.svg'));
			}

			if ($filename) {
				if (!isset($sprite_files[$filename])) {
					$sprite_files[$filename] = timestamp(find_in_path($filename));
				}
				$filename = $sprite_files[$filename];
				return array($filename . ($anchor ? '#' . $anchor : ''), $class);
			}
			else {
				return array($name, $class);
			}
		}
		else {
			// c'est le sprite par defaut avec un name qui correspond a l'ancre abregee
			// et la gestion de quelques historiques de nommage/renommage
			if (!isset($sprite_files[''])) {
				if (!defined('_ICON_SPRITE_SVG_FILE')) {
					define('_ICON_SPRITE_SVG_FILE', "css/bytesize/bytesize-symbols.min.svg");
					define('_ICON_SPRITE_SVG_ID_PREFIX', "i-");
				}
				$sprite_files[''] = timestamp(find_in_path(_ICON_SPRITE_SVG_FILE));
			}
			// sanitizer l'ancre pour la class
			$class = preg_replace(",[^\w\-],", "", $name);
			if (_ICON_SPRITE_SVG_ID_PREFIX) {
				$class .= " " . _ICON_SPRITE_SVG_ID_PREFIX . "icon";
			}
			if (!$name) {
				return array($sprite_files[''], $class);
			}
			$icone_anchor_from_name = chercher_filtre("icone_anchor_from_name");
			$anchor = $icone_anchor_from_name($name);
			return array($sprite_files[''] . '#' . $anchor, $class);
		}
	}

	/**
	 * Filtre surchargeable pour renommer les icones a la volee quand on adapte le jeu d'icone
	 * @param string $name
	 * @return string
	 */
	function filtre_icone_anchor_from_name_dist($name) {
		if (_ICON_SPRITE_SVG_ID_PREFIX) {
			if (strpos($name, _ICON_SPRITE_SVG_ID_PREFIX) === 0) {
				$name = substr($name, strlen(_ICON_SPRITE_SVG_ID_PREFIX));
			}
		}
		switch ($name) {
			case "comment":
				$ancre = 'msg';
				break;
			case "ok-circle":
				$ancre = 'compose';
				break;
			default:
				$ancre = $name;
				break;
		}
		return _ICON_SPRITE_SVG_ID_PREFIX . $ancre;
	}

	/**
	 * Fonction utilisee par la page demo/icons
	 * liste tous les ids d'un sprite svg
	 * @param string $sprite_file
	 * @return array
	 */
	function lister_icones_svg($sprite_file = '') {
		$trim_prefix = false;
		if (!$sprite_file) {
			$sprite_file = afficher_icone_svg('');
			$trim_prefix = true;
		}

		if ($sprite_file
			and $sprite_file = supprimer_timestamp($sprite_file)
		and $sprite = file_get_contents($sprite_file)
		and preg_match_all(',id="([\w\-]+)",', $sprite, $matches, PREG_PATTERN_ORDER)) {
			$icons = $matches[1];
			if ($trim_prefix and _ICON_SPRITE_SVG_ID_PREFIX){
				foreach ($icons as $k => $name){
					if (strpos($name, _ICON_SPRITE_SVG_ID_PREFIX)===0){
						$icons[$k] = substr($name, strlen(_ICON_SPRITE_SVG_ID_PREFIX));
					}
				}
			}
			sort($icons);
			return $icons;
		}
		return array();
	}
}

