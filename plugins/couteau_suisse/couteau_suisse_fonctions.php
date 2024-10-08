<?php
// Ce fichier est charge a chaque recalcul
// Attention, ici il se peut que le plugin ne soit pas initialise (cas des .js/.css par exemple)
if (!defined("_ECRIRE_INC_VERSION"))
	return;

// pour voir les erreurs ?
if (defined('_CS_REPORT'))
	error_reporting(E_ALL ^ E_NOTICE);
elseif (defined('_CS_REPORTALL'))
	error_reporting(E_ALL);

$GLOBALS['cs_fonctions_essai'] = 1;
//if (defined('_LOG_CS')) cs_log("INIT : couteau_suisse_fonctions ($GLOBALS[cs_spip_options]/$GLOBALS[cs_options]/$GLOBALS[cs_fonctions]/$GLOBALS[cs_init])");

// balises par defaut que le plugin echappe avant tout traitement typo ou propre
// cette fonction peut etre augmentee/modifiee par d'autres plugins ou un squelette
$GLOBALS['cs_echapper'][] = array('html|pre', 'code|cadre', 'frame', 'script|style', 'math');

cs_charge_fonctions();

function cs_charge_fonctions() {
	// initialisation si couteau_suisse_options est OK (fin de compilation par exemple)
	if (!isset($GLOBALS['cs_init']) || !$GLOBALS['cs_init']) {
		if (isset($GLOBALS['cs_options']) && $GLOBALS['cs_options']) {
			if (!isset($GLOBALS['cs_fonctions']) || !$GLOBALS['cs_fonctions']) {
				// inclusion des fonctions pre-compilees
				if (defined('_LOG_CS'))
					cs_log("INCL : " . _DIR_CS_TMP . 'mes_fonctions.php');
				@include(_DIR_CS_TMP . 'mes_fonctions.php');
				//if (defined('_LOG_CS')) cs_log("FIN INCL : "._DIR_CS_TMP.'mes_fonctions.php');
			} // else cs_log(' FIN : couteau_suisse_fonctions deja inclus');
		} else {
			$cs_log = function_exists('cs_log') ? 'cs_log' : 'spip_log';
			$cs_log('ESSAI : couteau_suisse_fonctions, mais couteau_suisse_options n\'est pas inclus');
			include_spip('cout_options');
		}
	} else {
		$cs_log = function_exists('cs_log') ? 'cs_log' : 'spip_log';
		$cs_log('ESSAI : couteau_suisse_fonctions, mais initialisation en cours');
	}
}

// raccourci pour le JavaScript
function cs_javascript($chaine) {
	return unicode_to_javascript(addslashes(html2unicode($chaine)));
}

// Fonction propre() sans echapper_html_suspect() (SPIP >= 3.1)
function cs_propre_sain($texte) {
	if (!$texte || !defined('_SPIP30100') || strpos($texte, '<') === false || strpos($texte, '=') === false)
		return propre($texte);
	$texte = propre(str_replace('=', '@CSEGAL@', $texte));
	$texte = str_replace('<p><q></p>', '<q>', $texte); // bug SPIP 4.1
	return str_replace('@CSEGAL@', '=', $texte);
}

// Fonction propre() sans paragraphage et sans echapper_html_suspect() (SPIP >= 3.1)
function cs_propre($texte) {
	include_spip('inc/texte');
	return trim(PtoBR(cs_propre_sain($texte)));
}

// Fonction _T() sans echapper_html_suspect() (SPIP >= 3.1)
function _T_sain($texte, $args = array(), $options = array()) {
	if (defined('_SPIP30100'))
		$options['sanitize'] = false;
	if (defined('_SPIP20100'))
		return _T($texte, $args, $options);
	return _T($texte, $args);
}

// Fonction couper() au plus simple
function cs_couper($texte, $nb = 25) {
	return strlen($texte) <= $nb ? $texte : substr($texte, 0, $nb)." (...)";
}

// Filtre creant un lien <a> sur un texte
// Exemple d'utilisation : [(#EMAIL*|cs_lien{#NOM})]
function cs_lien($lien, $texte='') {
	if(!$lien) return $texte;
	return cs_propre("[{$texte}->{$lien}]");
}

?>