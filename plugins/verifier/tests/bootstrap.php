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

if (!defined('echapper_tags')) {
	// Sauvagement copiécollé depuis SPIP 5
	function echapper_tags($texte, $rempl = '') {
		return preg_replace('/<([^>]*)>/', '&lt;\\1&gt;', $texte);
	}

}
if (!defined('email_valide')) {
	/*
	 * Copié collé depuis SPIP 5
	 *
	*/
	function email_valide($adresses) {
		if (is_array($adresses)) {
			$adresses = array_map('email_valide', $adresses);
			return array_filter($adresses);
		}

		$email_valide = 'inc_email_valide_dist';
		return $email_valide($adresses);
	}
}

if (!defined('email_valide')) {
	/**
	 * Copié collé depuis SPIP 5
	 * Vérifier la conformité d'une ou plusieurs adresses email (suivant RFC 822)
	 *
	 * @param string $adresses
	 *      Adresse ou liste d'adresse (separees pas des virgules)
	 * @return bool|string
	 *      - false si une des adresses n'est pas conforme,
	 *      - la normalisation de la dernière adresse donnée sinon
	 */
	function inc_email_valide_dist($adresses) {
		// eviter d'injecter n'importe quoi dans preg_match
		if (!$adresses || !is_string($adresses)) {
			return false;
		}


		foreach (explode(',', $adresses) as $v) {
			// nettoyer certains formats
			// "Marie Toto <Marie@toto.com>"
			$adresse = trim(preg_replace(',^[^<>"]*<([^<>"]+)>$,i', '\\1', $v));
			// RFC 822
			if (!preg_match('#^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)$#i', $adresse)) {
				return false;
			}
		}

		return $adresse;
	}

}
