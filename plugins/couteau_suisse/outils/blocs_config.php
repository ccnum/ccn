<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

# Fichier de configuration pris en compte par config_outils.php et specialement dedie a la configuration des blocs depliables
# ---------------------------------------------------------------------------------------------------------------------------

function outils_blocs_config_dist() {

if(!defined('_BLOC_TITLE_SEP')) define('_BLOC_TITLE_SEP', '||');
// Ajout de l'outil 'blocs'
add_outil(array(
	'id' =>'blocs',
	'categorie'	=> 'typo-racc',
	'contrib' => 2583,
	'code:options' => "%%bloc_h4%%
if(!defined('_BLOC_TITLE_SEP')) define('_BLOC_TITLE_SEP', '"._BLOC_TITLE_SEP."');
%%blocs_couper%%",
	// fonction blocs_init() codee dans blocs.js : executee lors du chargement de la page et a chaque hit ajax
	'code:js' => "var blocs_replier_tout = %%bloc_unique%%;
var blocs_millisec = %%blocs_millisec%%;
var blocs_slide = [[%blocs_slide%]];<cs_html>
var blocs_title_sep = /[(#EVAL{_BLOC_TITLE_SEP}|preg_quote)]/g;
#SET{x,#VAL{couteau:bloc_replier}|_T}
var blocs_title_def = '<:couteau:bloc_deplier|concat{#EVAL{_BLOC_TITLE_SEP},#GET{x}}|cs_javascript:>';
</cs_html>",
	'code:jq_init' => 'blocs_init.apply(this);',
	// utilisation des cookies pour conserver l'etat des blocs numerotes si on quitte la page
	'code:jq' => 'if(%%blocs_cookie%%) { if(jQuery("div.cs_blocs").length)
		jQuery.getScript(cs_CookiePlugin, cs_blocs_cookie); }
if(%%blocs_ancre%%) { blocs_deplier_pour_ancre(); }',
	'pipeline:pre_typo' => 'blocs_pre_typo',
	'pipeline:porte_plume_cs_pre_charger' => 'blocs_CS_pre_charger',
	'pipeline:porte_plume_lien_classe_vers_icone' => 'blocs_PP_icones',
	'pipelinecode:pre_description_outil' => 
			defined('_SPIP40100') ? 'if($id=="blocs") $texte=str_replace(array("code>", "quote>"),array("cadre>", "blockquote>"),$texte);' : '', // bug SPIP 4.1 ?
));

// Ajout des variables utilisees ci-dessus
add_variables(array(
	'nom' => 'bloc_h4',
	'format' => _format_CHAINE,
	'defaut' => '"h4"',
	'code:preg_match(\',^(div|h\d)$,i\', trim(%s))' => "define('_BLOC_TITRE_H', %s);",
), array(
	'nom' => 'bloc_unique',
	'format' => _format_NOMBRE,
	'radio' => array(1 => 'item_oui', 0 => 'item_non'),
	'defaut' => 0,
), array(
	'nom' => 'blocs_cookie',
	'format' => _format_NOMBRE,
	'radio' => array(1 => 'item_oui', 0 => 'item_non'),
	'defaut' => 0,
), array(
	'nom' => 'blocs_ancre',
	'format' => _format_NOMBRE,
	'radio' => array(1 => 'item_oui', 0 => 'item_non'),
	'defaut' => 0,
), array(
	'nom' => 'blocs_slide',
	'format' => _format_CHAINE,
	'radio' => array('aucun' => 'couteauprive:jslide_aucun', 'normal' => 'couteauprive:jslide_normal', 'slow' => 'couteauprive:jslide_lent', 'rapide' => 'couteauprive:jslide_fast', 'millisec' => 'couteauprive:jslide_millisec' ),
	'radio/ligne' => 2,
	'defaut' => '"aucun"',
	// si la variable est 'millisec' alors on prend directement les millisecondes
	'code:%s==="millisec"' => "blocs_millisec",
	'code:%s!=="millisec"' => "%s",
), array(
	'nom' => 'blocs_millisec',
	'format' => _format_NOMBRE,
	'defaut' => 100,
), array(
	'nom' => 'blocs_couper',
	'format' => _format_NOMBRE,
	'defaut' => 30,
	'code:%s>0' => "define('_BLOC_TITRE_COUPER', %s);",
));

}

?>