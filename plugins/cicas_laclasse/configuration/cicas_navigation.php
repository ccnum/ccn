<?php
/**
 * Plugin cicas
 * Copyright (c) Christophe IMBERTI
 * Licence Creative commons by-nc-sa
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');

function configuration_cicas_navigation(){
	
        $rac = icone_horizontale (_T('cicas:titre'), generer_url_ecrire("cicas_config"), "configuration-24.png", "", false);
        $rac .= icone_horizontale (_T('cicas:serveurs'), generer_url_ecrire("cicas_serveurs"), "article-24.png", "", false);
	
	return bloc_des_raccourcis($rac);
}

?>