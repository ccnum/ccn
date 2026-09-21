<?php
if (!defined('_ECRIRE_INC_VERSION')){
	return;
}


/**
 * Ajoute les scripts css et js nécessaires aux crayons dans le code HTML
 *
 * @uses crayons_var2js()
 *
 * @param string $page
 *     Code HTML de la page complète ou du header seulement
 * @param string $droits
 *     - Liste de css définissant les champs crayonnables
 *       (séparés par virgule) dont l'édition est autorisée
 *     - "*" si tous sont autorisés
 * @param array $wdgcfg
 *     Description de la configuration des crayons (attribut => valeur)
 * @param string $mode
 *     - page : toute la page est présente dans `$page`
 *     - head : seul le header est présent dans `$page`
 * @return
**/
function inc_crayons_preparer_page_dist(&$page, $droits, $wdgcfg = [], $mode = 'page') {
	/**
	 * Si pas forcer_lang, on charge le contrôleur dans la langue que l'utilisateur a dans le privé
	 */
	if (!isset($GLOBALS['forcer_lang']) || !$GLOBALS['forcer_lang'] || $GLOBALS['forcer_lang'] === 'non') {
		include_spip ('inc/session');
		if (!is_null($lang = session_get('lang'))) {
			lang_select($lang);
		}
	}

	$jsSkel = find_in_path('crayons.js.html');
	$contexte = ['callback' => 'startCrayons'];
	if (_DEBUG_CRAYONS) {
		$contexte['debug_crayons'] = 1;
	}
	$hash = substr(md5($jsSkel . json_encode($contexte)), 0, 7);
	$jsFile = _DIR_VAR . "cache-js/crayons-{$hash}.js";
	if (!file_exists($jsFile) || _VAR_MODE === 'recalcul') {
		include_spip('inc/filtres'); // pour produire_fond_statique()
		sous_repertoire(_DIR_VAR, 'cache-js');
		$jsFondStatique = supprimer_timestamp(produire_fond_statique('crayons.js', $contexte));
		@copy($jsFondStatique, $jsFile);
	}
	$jsFile .=  '?' . filemtime($jsFile);

	$cssFile = find_in_path('css/crayons.css');
	if (lang_dir() === 'rtl') {
		include_spip('inc/filtres'); // pour direction_css()
		$cssFile = direction_css($cssFile, 'rtl');
	}
	$cssFile .= '?' . filemtime($cssFile);

	$config = crayons_var2js([
		'imgPath' => dirname(find_in_path('css/images/crayon.svg')), // ne sert visiblement plus ?
		'droits' => $droits,
		'dir_racine' => _DIR_RACINE,
		'self' => self('&'),
		'txt' => [
			'error' => _U('crayons:svp_copier_coller'),
			'sauvegarder' => $wdgcfg['msgAbandon'] ? _U('crayons:sauvegarder') : ''
		],
		'img' => [
			'searching' => [
				'txt' => _U('crayons:veuillez_patienter')
			],
			'crayon' => [
				'txt' => _U('crayons:editer')
			],
			'edit' => [
				'txt' => _U('crayons:editer_tout')
			],
			'img-changed' => [
				'txt' => _U('crayons:deja_modifie')
			]
		],
		'cfg' => $wdgcfg
	]);


	// Est-ce que PortePlume est la ?
	$meta_crayon = (isset($GLOBALS['meta']['crayons']) ? unserialize($GLOBALS['meta']['crayons']) : []);
	$pp = '';
	if (isset($meta_crayon['barretypo']) && $meta_crayon['barretypo'] && test_plugin_actif('porte_plume')) {
		$pp = <<<EOF
cQuery(function() {
	if (typeof onAjaxLoad === 'function' && typeof jQuery.fn.barre_outils === 'function') {
		function barrebouilles_crayons() {jQuery('.formulaire_crayon textarea.crayon-active').barre_outils('edition');}
		onAjaxLoad(barrebouilles_crayons);
	}
});
EOF;
	}


	$incCSS = "<link rel=\"stylesheet\" href=\"{$cssFile}\" type=\"text/css\" media=\"all\" />";
	$incJS = <<<EOH
<script type="text/javascript">/* <![CDATA[ */
var configCrayons;
function startCrayons() {
	configCrayons = new cQuery.prototype.cfgCrayons({$config});
	cQuery.fn.crayonsstart();
{$pp}
}
var cr = document.createElement('script');
cr.type = 'text/javascript'; cr.async = true;
cr.src = '{$jsFile}';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(cr, s);
/* ]]> */</script>

EOH;

	if ($mode == 'head') {
		//js inline avant les css, sinon ca bloque le chargement
		$page = $page . $incJS . $incCSS;
		return $page;
	}

	$pos_head = strpos($page, '</head>');
	if ($pos_head === false) {
		return $page;
	}

	// js inline avant la premiere css, ou sinon avant la fin du head
	$pos_link = strpos($page, '<link ');
	if (!$pos_link) {
		$pos_link = $pos_head;
	}
	$page = substr_replace($page, $incJS, $pos_link, 0);

	// css avant la fin du head
	$pos_head = strpos($page, '</head>');
		$page = substr_replace($page, $incCSS, $pos_head, 0);

	return $page;

}
