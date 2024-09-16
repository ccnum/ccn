<?php

if (!defined("_ECRIRE_INC_VERSION")) return;    #securite

##
## Dire aux crayons si les visiteurs anonymes ont des droits
##
if (test_plugin_actif('crayons')
  and !empty($GLOBALS['meta']['autorite'])
  and (is_array($GLOBALS['autorite'] = unserialize($GLOBALS['meta']['autorite']))) AND
    (
        (isset($GLOBALS['autorite']['espace_wiki']) AND 
			isset($GLOBALS['autorite']['espace_wiki_anonyme'])
		) 
		OR 
		(isset($GLOBALS['autorite']['espace_wiki_motsclef']) AND 
			isset($GLOBALS['autorite']['espace_wiki_motsclef_anonyme'])
		)
    )) {
	if (!function_exists('analyse_droits_rapide')) {
		function analyse_droits_rapide() {
			return true;
		}
	} else {
		$autorite_erreurs[] = 'analyse_droits_rapide';
    }
}

