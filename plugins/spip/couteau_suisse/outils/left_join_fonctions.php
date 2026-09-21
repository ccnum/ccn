<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

function critere_left_join_dist($idb, &$boucles, $crit) {
	// simple info en vue du pipeline 'pre_boucle' qui traitera l'ensemble des jointures (explicites ou non)
	if(!count($crit->param)) {
		$boucles[$idb]->left_join = "";
		return;
	}
	if(count($crit->param) > 1)
		return (["Critère {left_join} : 0 ou 1 paramètre attendu."]);
	if($crit->param[0][0]->type !== 'texte')
		return (["Critère {left_join} : paramètre 'texte' attendu"]);
	$boucles[$idb]->left_join = $crit->param[0][0]->texte;
}

?>