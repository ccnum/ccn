<?php
/**
 * Utilisations de pipelines par Select2
 *
 * @plugin     Select2
 * @copyright  2019
 * @author     Matthieu Marcillaud
 * @licence    GNU/GPL
 * @package    SPIP\Select2\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Ajoute Select2 aux plugins JS chargés
 *
 * @param array $flux
 *     Liste des js chargés
 * @return array
 *     Liste complétée des js chargés
 **/
function select2_jquery_plugins($flux) {
	$active = test_espace_prive();
	if (!$active) {
		include_spip('inc/config');
		$active = (lire_config('select2/active', 'non') === 'oui');
	}
	if ($active) {
		$flux[] = 'javascript/select2.fork.full.js'; # lib (presque) originale
		$lang = $GLOBALS['spip_lang'];
		if (!find_in_path("lib/select2/js/i18n/$lang.js")) {
			$lang = 'en';
		}
		$flux[] = "lib/select2/js/i18n/$lang.js";
		$flux[] = 'javascript/SpipSelect2.js'; # lib pour SPIP
		$flux[] = 'javascript/SpipSelect2Loader.js'; # chargements SPIP automatiques
	}
	return $flux;
}

/**
 * Ajoute Select2 aux css chargées dans le privé
 *
 * @param string $flux Contenu du head HTML concernant les CSS
 * @return string       Contenu du head HTML concernant les CSS
 */
function select2_header_prive($flux) {
	include_spip('inc/config');
	$config = lire_config('select2', []);
	$selector = trim($config['selecteur_commun'] ?? '');
	$flux .= <<<JAVASCRIPT
	<script type="text/javascript">
		window.spipConfig ??= {};
		spipConfig.select2 ??= {};
		spipConfig.select2.selector ??= '$selector';
	</script>
	JAVASCRIPT;
	return $flux;
}

/**
 * Ajoute Select2 aux css chargées dans le public
 *
 * @param string $flux  Contenu du head HTML concernant les CSS
 * @return string       Contenu du head HTML concernant les CSS
 **/
function select2_insert_head($flux) {
	include_spip('inc/config');
	if (lire_config('select2/active') === 'oui') {
		$flux = select2_header_prive($flux);
	}
	return $flux;
}


/**
 * Ajoute Select2 aux css chargées dans le privé
 *
 * @param string $flux Contenu du head HTML concernant les CSS
 * @return string       Contenu du head HTML concernant les CSS
 */
function select2_header_prive_css($flux) {

	$css = sinon(find_in_path('css/select2.css'), find_in_path('lib/select2/css/select2.css'));
	$flux .= "<link rel='stylesheet' type='text/css' media='all' href='".direction_css($css)."' />\n";
	$css = find_in_path('css/spip.select2.css');
	$flux .= "<link rel='stylesheet' type='text/css' media='all' href='".direction_css($css)."' />\n";

	return $flux;
}

/**
 * Ajoute Select2 aux css chargées dans le public
 *
 * @return string       Contenu du head HTML concernant les CSS
 **/
function select2_insert_head_css($flux) {
	include_spip('inc/config');
	$config = lire_config('select2', array());
	if (isset($config['active']) and $config['active']=='oui') {
		$css = sinon(find_in_path('css/select2_public.css'), sinon(find_in_path('css/select2.css'), find_in_path('lib/select2/css/select2.css')));
		$flux .= '<link rel="stylesheet" href="'.direction_css($css).'" type="text/css" media="all" />';
		$css = find_in_path('css/spip.select2.css');
		$flux .= '<link rel="stylesheet" href="'.direction_css($css).'" type="text/css" media="all" />';
	}
	return $flux;
}
