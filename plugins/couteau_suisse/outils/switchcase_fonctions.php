<?php
/*
Balises SPIP génériques supplémentaires
@copyright  2015-2016
@author     JLuc chez no-log.org
@licence    GPL
 
Outil accessible par plugin séparé : https://contrib.spip.net/Balises-et-filtre-switch-case
*/

// gestion du niveau d'imbrication
function switchcase_niveau($add = false) {
	static $niveau = 0;
	if ($add) $niveau += $add;
	if ($niveau < 0) $niveau = 0;
	return $niveau;
}
	
function balise_SWITCH_dist($p) {
	$_val = interprete_argument_balise(1, $p);
	if ($_val === NULL) {
		// sans argument, renvoie la valeur testée (ou vide hors contexte)
		$p->code = "(isset(\$Pile['vars']['_switch_']) ? \$Pile['vars']['_switch_'] : '')";
//		$err = array('zbug_balise_sans_argument', array('balise' => ' #SWITCH'));
//		erreur_squelette($err, $p);
	}
	else {
		$p->code = 
			  "(vide(\$Pile['vars']['_switch_'] = \$Pile['vars']['_switch_' . switchcase_niveau(1)] = $_val)"
			. " . vide(\$Pile['vars']['_switch_matched_'] = \$Pile['vars']['_switch_matched_' . switchcase_niveau()] = ''))";
		// #GET{_switch_} renvoie maintenant la valeur testée
		// et #GET{_switch_matched_} indique si un test #CASE a déjà été satisfait
	}
	$p->interdire_script = false;
	return $p;
}

function balise_CASE_dist($p) {
	$tested = interprete_argument_balise(1, $p);
	if ($tested === NULL) {
		// sans argument, renvoie ' ' si la valeur a été trouvée, '' sinon
		$p->code = "(isset(\$Pile['vars']['_switch_matched_']) ? \$Pile['vars']['_switch_matched_'] : '')";
		// $err = array('zbug_balise_sans_argument', array('balise' => ' #CASE'));
		// erreur_squelette($err, $p);
	}
	else {
		$p->code = "(($tested == \$Pile['vars']['_switch_'])"
			. " ? ' ' . vide(\$Pile['vars']['_switch_matched_'] = \$Pile['vars']['_switch_matched_' . switchcase_niveau()] = ' ')"
			. " : '')";
	}; 
	$p->interdire_script = false;
	return $p;
}

function balise_CASE_DEFAULT_dist($p) {
	$p->code = "(\$Pile['vars']['_switch_matched_' . switchcase_niveau()] ? '' : ' ')";
	$p->interdire_script = false;
	return $p;
}

function balise_SWITCH_END_dist($p) {
	$p->code = 
			"(vide(\$Pile['vars']['_switch_'] = \$Pile['vars']['_switch_' . switchcase_niveau(-1)])"
		. " . vide(\$Pile['vars']['_switch_matched_'] = \$Pile['vars']['_switch_matched_' . switchcase_niveau()]))";
	$p->interdire_script = false;
	return $p;
}

/**
 * @param string $switch
 * @param array ...$cases  tableau suite des cas testés et valeurs renvoyées
 * @return mixed            la valeur correspondant au switch reçu
 *
 * Filtre |switchcase : comme |? mais pour plus de 2 valeurs
 * La valeur par défaut doit être spécifiée en dernier par 'defaut', 'default' ou 'case_default'
 *          [(#TRUC|switchcase{
 * 			    banane,jaune,
 * 			    orange,orange,
 * 			    ciel,bleu,
 * 			    case_default,inconnue
 * 				})]
 * Ou bien : [(#TRUC|switchcase{
 * 			    banane,jaune,
 * 			    orange,orange,
 * 			    ciel,bleu,
 * 			    inconnue
 * 				})]
 */
function filtre_switchcase($switch, ... $cases) {
	$last_case = $case = $val = '';
	$default_sans_case = (count($cases) % 2);

	while ($case = array_shift($cases)) {
		$val = array_shift ($cases);
		if ($switch == $case) {
			return $val;
		}
		$last_case = $case;
	}
	// dernier cas : case_default, <default_value>
	if ($last_case == 'case_default') {
		return $val;
	}
	// pas de value : case est la <default_value>
	if ($default_sans_case) {
		return $last_case;
	}
	return '';
}
?>