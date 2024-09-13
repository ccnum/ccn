<?php
#-----------------------------------------------------#
#  Plugin  : Couteau Suisse - Licence : GPL           #
#  Auteur  : Patrice Vanneufville, 2007               #
#  Contact : patrice¡.!vanneufville¡@!laposte¡.!net   #
#  Infos : https://contrib.spip.net/?article2166      #
#-----------------------------------------------------#
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('cout_define');

function exec_cs_version_dist() {
	cs_minipres();
	$version = _request('version');
	$force = _request('force') == 'oui';

	// pour la version disponible, on regarde toutes les 8h00
	$maj = isset($GLOBALS['meta']['tweaks_maj']) ? unserialize($GLOBALS['meta']['tweaks_maj']) : array(0, '');
	if (!$force && $maj[1] && (time()-$maj[0] < 8 * 3600))
    $distant = $maj[1];
	else {
		include_spip('cout_utils');
		$distant = cs_recuperer_page(_URL_CS_PLUGIN_XML);
		if ($distant)
      $distant = $maj[1] = preg_match(',<version>([0-9.]+)</version>,', $distant, $regs) ? $regs[1] : '';
		$maj[0] = time();
		if($distant) ecrire_meta('tweaks_maj', serialize($maj));
		if(!defined('_SPIP19300')) ecrire_metas(); # Pour SPIP 1.92
	}
	include_spip('inc/texte');
	include_spip('couteau_suisse_fonctions'); // fonctions pour les pipelines, cs_lien, etc.
	if (!$distant)
		return ajax_retour('<span style="color: red;">'
      . _T('couteauprive:erreur:probleme', array('pb' => cs_lien(_URL_CS_PLUGIN_XML, _T('couteauprive:erreur:distant'))))
      . '</span>');
	ajax_retour(ptobr(propre($distant == $version
    ? _T('couteauprive:version_a_jour')
    : ($distant
       ? _T('couteauprive:version_nouvelle', array('version' => "[{$distant}->" . _URL_CS_PLUGIN_ZIP . ']'))
       : '')
	)));
}
?>