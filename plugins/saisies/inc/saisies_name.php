<?php

/**
 * Gestion de name
 * Partie commun js/php
 *
 * @package SPIP\Saisies\Name
 **/


/**
 * Passer un nom en une valeur compatible avec une classe css
 *
 * - toto => toto,
 * - toto/truc => toto_truc,
 * - toto[truc] => toto_truc
 *
 * @param string $nom
 * @return string
 **/
function saisie_nom2classe($nom) {
	return str_replace(['/', '[', ']', '&#91;', '&#93;'], ['_', '_', '', '_', ''], $nom);
}

/**
 * Ajouter une ou des classes sur la saisie en fonction du type
 * @param $type_saisie
 * @return string
 */
function saisie_type2classe($type_saisie) {
	$class = "saisie_{$type_saisie}";
	if (strpos($type_saisie, 'selecteur') === 0) {
		$class .= ' selecteur_item';
	}
	$class = trim($class);
	return $class;
}

/**
 * Passer un nom en une valeur compatible avec un `name` de formulaire
 *
 * - toto => toto,
 * - toto/truc => toto[truc],
 * - toto/truc/ => toto[truc][],
 * - toto[truc] => toto[truc]
 *
 * @see saisie_name2nom() pour l'inverse.
 * @param string $nom
 * @return string
 **/
function saisie_nom2name($nom) {
	if (false === strpos($nom, '/')) {
		return $nom;
	}
	$nom = explode('/', $nom);
	$premier = array_shift($nom);
	$nom = implode('][', $nom);
	return $premier . '[' . $nom . ']';
}

/**
 * Passer un `name` en un format de nom compris de saisies
 *
 * - toto => toto,
 * - toto[truc] => toto/truc,
 * - toto[truc][] => toto/truc/
 * - toto/truc => toto/truc
 *
 * @see saisie_nom2name() pour l'inverse.
 * @param string $name
 * @return string
 **/
function saisie_name2nom($name) {
	if (false === strpos($name, '[')) {
		return $name;
	}
	$name = explode('[', str_replace(']', '', $name));
	return implode('/', $name);
}

/**
 * Appliquer `saisie_nom2name()` sur les clés d'un tableau
 * utilisé pour gérer les erreurs
 * @param array $tab
 * @return array
 **/
function saisies_cles_nom2name($tab) {
	if (!is_array($tab)) {
		return $tab;
	}
	foreach ($tab as $k => $v) {
		$kbis = saisie_nom2name($k);
		if ($kbis !== $k) {
			unset($tab[$k]);
			$tab[$kbis] = $v;
		}
	}
	return $tab;
}

/**
 * Suffixe
 * un name en tenant compte du fait que cela peut être potentiellement avec des []
 * @param string $name
 * @param string $suffixe (sans `_` qui sera mis automatiquement)
 * @return string nouvelle version
**/
function saisies_name_suffixer(string $name, string $suffixe): string {
	if (strpos($name, ']') !== false) {
		$name = preg_replace('#\]$#', "_{$suffixe}]", $name);
	} else {
		$name .= '_' . $suffixe;
	}
	return $name;
}

/**
 * Enelever le suffixe d'un name, en tenant compte du fait que cela peut potentiellement être avec des []
 * @param string $name
 * @param string $suffixe (sans le `_`)
 * @return string $name sans le suffixe
**/
function saisies_name_supprimer_suffixe(string $name, string $suffixe): string {
	if (strpos($name, ']') !== false) {
		$name = preg_replace("#_{$suffixe}\]$#", ']', $name);
	} else {
		$name = preg_replace("#_{$suffixe}$#", '', $name);
	}
	return $name;
}
