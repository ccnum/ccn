<?php
#-----------------------------------------------------#
#  Plugin  : Couteau Suisse - Licence : GPL           #
#  Auteur  : Patrice Vanneufville, 2007               #
#  Contact : patrice¡.!vanneufville¡@!laposte¡.!net   #
#  Infos : https://contrib.spip.net/?article2166      #
#-----------------------------------------------------#
if (!defined("_ECRIRE_INC_VERSION")) return;


function exec_cs_packs_dist() {
	include_spip('inc/texte');
	include_spip('couteau_suisse_fonctions'); // fonctions pour les pipelines, cs_lien, etc.
	include_spip('outils/pack_action_rapide');
	return ajax_retour(pack_liste_packs());
}
?>