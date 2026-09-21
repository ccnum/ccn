<?php
if(defined('_SPIP20100')) include_spip('images_fonctions');

// Fonction de pipeline
function titres_typo_pre_typo($texte) {
	if (strpos($texte, '{{{')===false) return $texte;
	// appeler titres_typo_rempl() une fois que certaines balises ont ete protegees
	return cs_echappe_balises('', 'titres_typo_rempl', $texte);
}

 // Fonction de remplacement
 // Transforme les intertitres d'un texte en image typo
function titres_typo_rempl($texte){
	static $callback;
	if(!isset($callback)) {
		$arguments = str_replace("'", '', _titres_typo_ARG);
		include_spip('outils/couleurs');
		list($couleurs, $html) = couleurs_constantes();
		if(preg_match(',couleur=#?([\w\s]+),', $arguments, $regs)) {
			$c = trim($regs[1]);
			if(($i=array_search($c, $couleurs[0]))!==false || ($i=array_search($c, $couleurs[1]))!==false)
				$c = $html[$couleurs[1][$i]];
			$arguments .= ',couleur='.$c;
		}
		$arguments = explode(',', $arguments);
		$callback = function($match) use($arguments) {
			$arg = array_merge(array($match[2]), $arguments);
			return $match[1] . call_user_func_array('image_typo', $arg) . "}}}";
		};
	}
	return preg_replace_callback(',(\{\{\{)\*?([^*].*?)\}\}\},is', $callback, $texte);
}

?>