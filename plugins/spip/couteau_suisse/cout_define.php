<?php

#-----------------------------------------------------#
#  Plugin  : Couteau Suisse - Licence : GPL           #
#  Auteur  : Patrice Vanneufville, 2006               #
#  Contact : patrice¡.!vanneufville¡@!laposte¡.!net   #
#  Infos : https://contrib.spip.net/?article2166      #
#-----------------------------------------------------#
if (!defined("_ECRIRE_INC_VERSION")) return;

// Ici se definissent les constantes du Couteau Suisse

// Ressources distantes
@define('_URL_CS_GIT', 'https://git.spip.net/');
@define('_URL_CS_GIT_EXT', _URL_CS_GIT . 'spip-contrib-extensions/');
@define('_URL_ZONE_EXTRACT', 'https://zone.spip.net/trac/spip-zone/export/');
@define('_CS_RSS_SOURCE', _URL_CS_GIT_EXT . 'couteau_suisse/-/commits/master?format=atom');
@define('_CS_GIT_COMMITS', _URL_CS_GIT . 'api/v1/repos/spip-contrib-extensions/couteau_suisse/commits'); // TODO; v4 ?
@define('_URL_GIT_CS', _URL_CS_GIT . 'spip-contrib-extensions/couteau_suisse');
// Doc de contrib.spip.net
@define('_URL_CONTRIB', 'https://contrib.spip.net/?article');
// Dernieres revisions du CS
@define('_URL_CS_PLUGIN_XML', _URL_CS_GIT_EXT . 'couteau_suisse/-/raw/master/plugin.xml');
@define('_URL_CS_PLUGIN_ZIP', _URL_CS_GIT_EXT . 'couteau_suisse/-/archive/master/couteau_suisse-master.zip');

// Mise a jour du flux RSS toutes les 4 heures, sur les 15 derniers commits
define('_CS_RSS_UPDATE', 4 * 3600);
define('_CS_RSS_COUNT', 15);
// Fichier cache
define('_CS_TMP_RSS', _DIR_TMP . 'rss_couteau_suisse.html');
// Traductions des modules
define('_CS_TRAD_ACCUEIL', 'https://trad.spip.net/');
define('_CS_TRAD_MODULE', _CS_TRAD_ACCUEIL . 'tradlang_module/');

define('_MAJ_ECRAN_SECU', _URL_CS_GIT . 'spip-contrib-outils/securite/-/raw/master/ecran_securite.php');

// Puces vertes et rouges pour les outils
if(defined('_SPIP40000')) { // SPIP >= 4.0 ou 3.3dev
	define('_CS_PUCE_VERTE', chemin_image('puce-publier-8.png'));
	define('_CS_PUCE_ROUGE', chemin_image('puce-refuser-8.png'));
} else {
	define('_CS_PUCE_VERTE', _DIR_IMG_PACK . 'puce-verte.gif');
	define('_CS_PUCE_ROUGE', _DIR_IMG_PACK . 'puce-rouge.gif');
}

// Qui sont les webmestres et les administrateurs ?
function get_liste_administrateurs() {
	include_spip('inc/autoriser');
	include_spip('inc/texte');
	$admins = $webmestres = array();
	$select = function_exists('sql_select') ? sql_select('*', 'spip_auteurs', "statut='0minirezo'")
	  : spip_query("SELECT * FROM spip_auteurs WHERE statut='0minirezo'"); // compatibilite SPIP 1.92
	$fetch  = function_exists('sql_fetch') ? 'sql_fetch' : 'spip_fetch_array'; // compatibilite SPIP 1.92
	while ($qui = $fetch($select)) {
		$nom = '<a href="' . generer_url_ecrire(defined('_SPIP30000') ? 'auteur' : 'auteur_infos', "id_auteur=$qui[id_auteur]") . '">' . typo($qui['nom']) . "</a> (id_auteur=$qui[id_auteur])";
		if (autoriser('webmestre', '', '', $qui))
			$webmestres[$qui['id_auteur']] = $nom;
		elseif (autoriser('configurer', 'plugins', '', $qui))
			$admins[$qui['id_auteur']] = $nom;
	}
	return array(
		count($webmestres) ? implode(', ', $webmestres) : _T('couteauprive:variable_vide'),
		count($admins) ? implode(', ', $admins) : _T('couteauprive:variable_vide')
	);
}

// Polices disponibles
function get_liste_fonts() {
	return array_keys(find_all_in_path('polices/', '\w+\.ttf$'));
}

// Montrer le fichier mes_options.php en cours
function show_file_options() {
	return cs_root_canonicalize(cs_spip_file_options(3));
}

?>