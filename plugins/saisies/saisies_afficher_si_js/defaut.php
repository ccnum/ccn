<?php

/** Gestion de l'affichage conditionnelle des saisies.
 * Partie spécifique js
 *
 * @package SPIP\Saisies\afficher_si_js\defaut
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
/**
 * Generation du js d'afficher_si par défaut
 * @param array $parse analyse syntaxique du tests à effectuer (sous tableau de résultat de saisies_parser_condition_afficher_si())
 * @param array $saisies_form ensemble des saisies du formulaire, listées par nom
 **/
function saisies_afficher_si_js_defaut($parse, $saisies_form) {
	$negation = $parse['negation'];
	unset($parse['negation']);

	// Compatibilité historique de syntaxe, avant que l'on mette tout en JSON, on envoyait directement RegExp(valeur), il fallait donc que les // soient dans valeur. Mais désormais on envoie en JSON, donc on a un string, donc il faut enlever les slashs avant d'envoyer au JS
	if (in_array($parse['operateur']  ?? '', ['MATCH', '!MATCH'])) {
		include_spip('inc/saisies_afficher_si_commun');

		$m = saisies_afficher_si_parser_valeur_MATCH($parse['valeur']);

		$parse['valeur'] = $m['regexp'];
		$parse['regexp_modif'] = $m['regexp_modif'];
	}
	$parse['champ'] = saisie_nom2name($parse['champ']);
	$parse = json_encode($parse);
	return $negation . 'afficher_si(' . $parse . ')';
}
