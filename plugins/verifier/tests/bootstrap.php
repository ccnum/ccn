<?php

require_once __DIR__ . '/../vendor/autoload.php';

// decorate SPIP…
if (!function_exists('include_spip')) {
	function include_spip() {
	}
	define('_ECRIRE_INC_VERSION', true);
}

if (!function_exists('spip_log')) {
	define('_LOG_CRITIQUE', false);
	define('_LOG_ERREUR', false);
	function spip_log() {
	}
}


if (!function_exists('find_in_path')) {

	/**
	 * Pseudo find_in_path()
	 * Recherche uniquement dans le dossier tests/
	*/
	function find_in_path($file) {
		return __DIR__.'/'.$file;
	}
	function charger_fonction($nom, $dossier) {
		// C'est minimaliste mais ca fait l'affaire
		if (strlen($dossier) && !str_ends_with($dossier, '/')) {
			$dossier .= '/';
		}
		$f = str_replace('/', '_', $dossier) . $nom;

		if (function_exists($f)) {
			return $f;
		}
		if (function_exists($g = $f . '_dist')) {
			return $g;
		}
		return '';
	}
}


if (!function_exists('_T')) {
	function _T($i, $params = []) {
		return $i . json_encode($params);
	}
	function typo($i) {
		return $i;
	}
	function interdire_scripts($i) {
		return $i;
	}
}


if (!defined('vider_date')) {
	/**
	 * Pompé depuis SPIP 5 dev
	 *
	 * @param string $letexte
	 * @param bool $verif_format_date
	 * @return string
	 *     - La date entrée (si elle n'est pas considérée comme nulle)
	 *     - Une chaine vide
	 **/
	function vider_date($letexte, $verif_format_date = false): string {
		$letexte ??= '';
		if (
			!$verif_format_date
			|| in_array(strlen($letexte), [10,19]) && preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $letexte)
		) {
			if (strncmp('0000-00-00', $letexte, 10) == 0) {
				return '';
			}
			if (strncmp('0001-01-01', $letexte, 10) == 0) {
				return '';
			}
			if (strncmp('1970-01-01', $letexte, 10) == 0) {
				return '';
			}  // eviter le bug GMT-1
		}
		return $letexte;
	}
}
