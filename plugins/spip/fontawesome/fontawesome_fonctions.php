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
	include_spip('inc/fontawesome_public');
}

/**
 * utiliser une icone d'un des sprites Font Awesome :
 * par defaut utilise le sprite solid
 * #FA_ICON{beer,'',Beer}
 *
 * Mais on peut demander un autre sprite
 * #FA_ICON{brands#ubuntu,icon-sm,Ubuntu}
 * #FA_ICON{regular#kiss,icon-lg,Kiss}
 *
 * voir ?page=demo/fontawesome
 *
 * @param $p
 * @return mixed
 */
function balise_FA_ICON_dist($p) {
	$_name = interprete_argument_balise(1, $p);
	$_class = interprete_argument_balise(2, $p);
	if (!$_class) {
		$_class = "''";
	}
	$_alt = interprete_argument_balise(3, $p);
	if (!$_alt) {
		$_alt = "''";
	}
	$p->code = "fontawesome_afficher_icone_svg($_name, $_class, $_alt)";

	$p->interdire_scripts = false;
	return $p;
}

/**
 * Fonction interne utilisee par la balise
 * @param $name
 * @param $class
 * @param $alt
 * @return string
 */
function fontawesome_afficher_icone_svg($name, $class, $alt) {
	$class = trim("fa-icon $class");

	if (strpos($name,'/') !== false or strpos($name,'.svg') !== false) {
		return afficher_icone_svg($name, $class, $alt);
	}
	else {
		$name = explode('#', $name);
		$id = array_pop($name);
		$famille = "";
		if (count($name)) {
			$famille = array_pop($name);
		}

		if (strpos($id, 'fa-') === 0) {
			$id = substr($id, 3);
		}

		switch ($famille) {
			case "brands":
				$sprite = "img/fa/brands.svg";
				break;
			case "regular":
				$sprite = "img/fa/regular.svg";
				break;
			default:
				$sprite = "img/fa/solid.svg";
				break;
		}

		return afficher_icone_svg("$sprite#$id", $class, $alt);
	}
}

function fontawesome_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" type="text/css" href="'.timestamp(find_in_path('css/fa-icons.css')).'" />';
	return $flux;
}
