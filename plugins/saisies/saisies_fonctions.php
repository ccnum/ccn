<?php

/**
 * Déclaration de fonctions pour les squelettes
 *
 * @package SPIP\Saisies\Fonctions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/saisies');
include_spip('balise/saisie');
include_spip('inc/saisies_afficher_si_js');
// picker_selected (spip 3)
include_spip('formulaires/selecteur/generique_fonctions');

/**
 * Retourne une balise dev. Maintenu pour compatibilité historique, ne plus utiliser.
 * @deprecated
 * Comportement antérieure :
 * Retournait une balise `div` si on est en SPIP >= 3.1, sinon le texte en parametre.
 * @example `[(#VAL{ul}|saisie_balise_structure_formulaire)]`
 * @see balise_DIV_dist() pour une écriture plus courte.
 * @note Préférer `[(#DIV|sinon{ul})]` dans les squelettes, plus lisible.
 *
 * @param $tag
 *   ul ou li
 * @return string
 *   $tag initial ou div
 */
function saisie_balise_structure_formulaire($tag) {
	trigger_error('Le filtre saisie_balise_structure_formulaire est deprécié et sera supprimé en v6 du plugin saisies. Utiliser directement `<div>`.', E_USER_DEPRECATED);
	spip_log('Le filtre saisie_balise_structure_formulaire est deprécié et sera supprimé en v6 du plugin saisies. Utiliser directement `<div>`.', 'deprecated_saisies');
	return 'div';
}

if (
	!function_exists('balise_DIV_dist')
) {

	/**
	 * Compile la balise `DIV` qui retourne simplement le texte `div`
	 *
	 * Maintenu pour ne pas casser les squelettes qui s'appuient dessus, mais à ne plus utiliser.
	 * Servait à la compatibilité entre SPIP 3.0 et SPIP 3.1+
	 *
	 * Variante d'écriture, plus courte, que le filtre `saisie_balise_structure_formulaire`
	 *
	 * À partir de SPIP 3.1
	 * - ul.editer-groupe deviennent des div.editer-groupe
	 * - li.editer devient div.editer
	 * @deprecated
	 * @see saisie_balise_structure_formulaire()
	 * @example
	 *     `[(#DIV|sinon{ul})]`
	 *
	 * @param Pile $p
	 * @return Pile
	 */
	function balise_DIV_dist($p) {
		$p->code = "'div'";
		$p->interdire_scripts = false;
		trigger_error('La balise #DIV est depréciée et sera supprimée en v6 du plugin saisies. Utiliser directement `<div>`.', E_USER_DEPRECATED);
		spip_log('La balise #DIV est depréciée et sera supprimée en v6 du plugin saisies. Utiliser directement `<div>`.', 'deprecated_saisies');
		return $p;
	}
}

/**
 * Traiter la valeur de la vue en fonction du env
 * si un traitement a ete fait en amont (champs extra) ne rien faire
 * si pas de traitement defini (formidable) passer typo ou propre selon le type du champ
 *
 * @param ?string $valeur
 * @param string|array $env
 * @return string
 */
function saisie_traitement_vue(?string $valeur, $env): string {
	if (is_null($valeur)) {
		$valeur = '';
	}
	if (is_string($env)) {
		$env = unserialize($env);
	}
	if (!function_exists('propre')) {
		include_spip('inc/texte');
	}
	if (!is_array($valeur)) {
		$valeur = trim($valeur);
	}
	// si traitement est renseigne, alors le champ est deja mis en forme
	// (saisies)
	// sinon on fait une mise en forme smart
	if ($valeur && !is_array($valeur) && !isset($env['traitements'])) {
		if (in_array($env['type_saisie'], ['textarea'])) {
			$valeur = propre($valeur);
		} else {
			$valeur = '<p>' . typo($valeur) . '</p>';
		}
	}

	return $valeur;
}

/**
 * Liste les éléments du sélecteur générique triés
 *
 * Les éléments sont triés par objets puis par identifiants
 *
 * @example
 *     L'entrée :
 *     'rubrique|3,rubrique|5,article|2'
 *     Retourne :
 *     array(
 *        0 => array('objet'=>'article', 'id_objet' => 2),
 *        1 => array('objet'=>'rubrique', 'id_objet' => 3),
 *        2 => array('objet'=>'rubrique', 'id_objet' => 5),
 *     )
 *
 * @param string $selected
 *     Liste des objets sélectionnés
 * @return array
 *     Liste des objets triés
 **/
function saisies_picker_selected_par_objet($selected) {
	$res = [];
	$liste = picker_selected($selected);
	// $liste : la sortie dans le désordre
	if (!$liste) {
		return $res;
	}

	foreach ($liste as $l) {
		if (!isset($res[ $l['objet'] ])) {
			$res[ $l['objet'] ] = [];
		}
		$res[$l['objet']][] = $l['id_objet'];
	}
	// $res est trié par objet, puis par identifiant
	ksort($res);
	foreach ($res as $objet => $ids) {
		sort($res[$objet]);
	}

	// on remet tout en file
	$liste = [];
	foreach ($res as $objet => $ids) {
		foreach ($ids as $id) {
			$liste[] = ['objet' => $objet, 'id_objet' => $id];
		}
	}

	return $liste;
}


/**
 * Lister les objets qui ont une url_edit renseignée et qui sont éditables.
 *
 * @return array Liste des objets :
 *               index : nom de la table (spip_articles, spip_breves, etc.)
 *               'type' : le type de l'objet ;
 *               'url_edit' : l'url d'édition de l'objet ;
 *               'texte_objets' : le nom humain de l'objet éditorial.
 */
function lister_tables_objets_edit() {
	include_spip('base/abstract_sql');

	$objets = lister_tables_objets_sql();
	$objets_edit = [];

	foreach ($objets as $objet => $definition) {
		if (
			isset($definition['editable'])
			&& ($definition['url_edit'] ?? '')
		) {
			$objets_edit[$objet] = ['type' => $definition['type'], 'url_edit' => $definition['url_edit'], 'texte_objets' => $definition['texte_objets']];
		}
	}
	$objets_edit = array_filter($objets_edit);

	return $objets_edit;
}

/**
 * Afficher la chaine de langue traduite.
 *
 * @param string $chaine
 * @return string
 */
function saisies_label($chaine) {
	$chaine = trim($chaine);
	if (preg_match('/^(&lt;:|<:)/', $chaine)) {
		$chaine = preg_replace('/^(&lt;:|<:)/', '', $chaine);
		$chaine = preg_replace('/(:&gt;|:>)$/', '', $chaine);
		return _T($chaine);
	}

	return $chaine;
}


/**
 * Les liens ouvrants, c'est mal en général.
 * Sauf dans un cas particulier : dans les explications dans un formulaire.
 * En effet, si le lien n'est pas ouvrant, la personne en train de remplir un formulaire
 * a) lis une explication
 * b) clique sur le lien pour savoir comment remplir son formulaire
 * c) est redirigée directement vers une page
 * d) perd du coup ce qu'elle avait commencé remplir.
 * Par conséquent, en terme d'accessibilité, il vaut mieux POUR LES EXPLICATIONS DE FORMULAIRE
 * avoir des liens systématiquement ouvrant,
 * et ce que le lien pointe en interne ou en externe (ce qui distingue du filtre |liens_ouvrants).
 * D'où un filtre saisies_liens_ouvrants
 * @param string $texte
 * @return string $texte
 **/
function saisies_liens_ouvrants($texte) {
	$texte = liens_absolus($texte);
	if (
		preg_match_all(
			",(<a\s+[^>]*https?://[^>]*\b[^>]+>),imsS",
			$texte,
			$liens,
			PREG_PATTERN_ORDER
		)
	) {
			foreach ($liens[0] as $a) {
				$rel = 'noopener noreferrer ' . extraire_attribut($a, 'rel');
				$ablank = inserer_attribut($a, 'rel', $rel);
				$ablank = inserer_attribut($ablank, 'target', '_blank');
				$texte = str_replace($a, $ablank, $texte);
			}
	}
	return $texte;
}

/**
 * Afficher un statut traduit
 *
 * @param string $statut Le statut (publie, refuse, ...)
 * @param string $objet Le type d’objet (article, rubrique, ...)
 * @return string La traduction du statut, si on la trouve, sinon simplement la clé...
 */
function saisies_statut_titre($statut, $objet = '') {
	include_spip('inc/puce_statut');
	$titre = statut_titre($objet, $statut);
	return $titre ? $titre : $statut;
}

/**
 * Convertit si nécessaire une valeur au format du picker ajax du sélecteur générique
 * quand on sait que la valeur ne concerne qu'un seul type d'objet.
 *
 * Fait en quelque sorte l'inverse de picker_selected().
 * Cela évite d'avoir à modifier le charger des formulaires quand on traite des cas simples :
 * juste un plusieurs ids d'un seul type d'objet.
 *
 * @example
 * - Entrée : 10,           sortie : array('article|10')
 * - Entrée : array(10,20), sortie : array('article|10', 'article|20')
 *
 * @see picker_selected()
 *
 * @param array|string|int $valeur
 *    - Soit un tableau issu du sélecteur (dans ce cas on ne fait rien) : `array('article|1', 'article|2')`
 *    - Soit l'id d'un objet : 1
 *    - Soit un tableau d'ids : array(1,2)
 * @param string $objet
 * @return array
 *    Tableau au format du sélecteur générique
 */
function saisies_picker_preselect_objet($valeur, $objet) {

	// Nb : évitons des preg_match si possible
	$is_objet_unique   = is_numeric($valeur); // ex. : 10
	$is_objet_multiple = (is_array($valeur) && is_numeric($valeur[0])); // ex. : array(10,20)

	if ($is_objet_unique || $is_objet_multiple) {
		if ($is_objet_unique) {
			$valeur = [$valeur];
		}
		foreach ($valeur as $k => $id_objet) {
			$valeur[$k] = "$objet|$id_objet";
		}
	}

	return $valeur;
}

/**
 * Function inverse de la fonction du core `utf8_noplanes()`
 * Trouve les entités HTML numérique des hautes "planes"
 * et les remets en Unicode
 * @param string|null le texte
 * @return string
 **/
function saisies_utf8_restaurer_planes(?string $x): string {
	if (!$x) {
		return (string)$x;
	}
	preg_match_all('/&(amp;)?#(\d+);/U', $x, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		if ($m[2] > 65535) {//Uniquement les planes 1-16, pas le plane 0
			$decode = html_entity_decode($m[0], ENT_COMPAT);
			$x = str_replace($m[0], $decode, $x);
		}
	}
	return $x;
}
