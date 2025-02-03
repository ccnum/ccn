<?php

/**
 * Crayons
 * plugin for spip
 * (c) Fil, toggg 2006-2019
 * licence GPL
 *
 * @package SPIP\Crayons\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_DEBUG_CRAYONS')) {
	/**
	 * Débuguer les crayons
	 *
	 * Mettre a true dans mes_options pour avoir les crayons non compresses
	 */
	define('_DEBUG_CRAYONS', false);
}

$GLOBALS['marqueur_skel'] = ($GLOBALS['marqueur_skel'] ?? '') . ':crayons';

/**
 * Dire rapidement si ca vaut le coup de chercher des droits
 *
 * @return bool
**/
function analyse_droits_rapide_dist() {
	include_spip ('inc/session');
	return !is_null(session_get('lang'));
}

/**
 * Vérifier si un exec du privé est crayonnable
 *
 * @param string $exec
 *
 * @return bool
 **/
function test_exec_crayonnable($exec) {
	if ($exec_autorise = lire_config('crayons/exec_autorise')) {
		$execs = explode(',', $exec_autorise);
		foreach ($execs as $key => $value) {
			$execs[$key] = trim($value);
		}
		if ($exec_autorise == '*' || in_array($exec, $execs)) {
			return true;
		}
	}

	return false;
}

/**
 * Ajouter la gestion des crayons dans l'espace privé
 *
 * @pipeline header_prive
 * @uses inc_crayons_preparer_page()
 *
 * @param string $head
 *     Contenu du header
 * @return string
 *     Contenu du header
**/
function Crayons_insert_head($head) {
	// verifie la presence d'une meta crayons, si c'est vide
	// on ne cherche meme pas a traiter l'espace prive
	if (empty($GLOBALS['meta']['crayons'])) {
		return $head;
	}
	$config_espace_prive = @unserialize($GLOBALS['meta']['crayons']);
	if (empty($config_espace_prive)) {
		return $head;
	}

	// verifie que l'edition de l'espace prive est autorisee
	// determine les pages (exec) crayonnables
	if (
		isset($config_espace_prive['espaceprive'])
		&& $config_espace_prive['espaceprive'] == 'on'
		&& test_exec_crayonnable(_request('exec'))
	) {
		// Calcul des droits
		include_spip('inc/crayons');
		$Crayons_preparer_page = charger_fonction('Crayons_preparer_page', 'inc');
		$head = $Crayons_preparer_page($head, '*', wdgcfg(), 'head');
	}

	// retourne l'entete modifiee
	return $head;
}

/**
 * Ajouter la gestion des crayons dans l'espace public
 *
 * @pipeline affichage_final
 * @uses analyse_droits_rapide_dist()
 * @uses inc_crayons_preparer_page()
 * @note
 *     Le pipeline affichage_final est executé à chaque hit sur toute la page
 *
 * @param string $page
 *     Contenu de la page à envoyer au navigateur
 * @return string
 *     Contenu de la page à envoyer au navigateur
**/
function Crayons_affichage_final($page) {

	// ne pas se fatiguer si le visiteur n'a aucun droit
	if (!(function_exists('analyse_droits_rapide') ? analyse_droits_rapide() : analyse_droits_rapide_dist())) {
		return $page;
	}

	// sinon regarder rapidement si la page a des classes crayon
	if (strpos($page, 'crayon') === false) {
		return $page;
	}

	// voir un peu plus precisement lesquelles
	include_spip('inc/crayons');
	if (!preg_match_all(_PREG_CRAYON, $page, $regs, PREG_SET_ORDER)) {
		return $page;
	}

	$wdgcfg = wdgcfg();

	// calculer les droits sur ces crayons
	include_spip('inc/autoriser');
	$droits = [];
	$droits_accordes = 0;
	foreach ($regs as $reg) {
		[, $crayon, $type, $champ, $id] = $reg;
		if (_DEBUG_CRAYONS) {
			spip_log("autoriser('modifier', $type, $id, NULL, array('champ'=>$champ))", 'crayons_distant');
		}
		if (autoriser('modifier', $type, $id, null, ['champ' => $champ])) {
			if (!isset($droits['.' . $crayon])) {
				$droits['.' . $crayon] = 0;
			}
			$droits['.' . $crayon]++;
			$droits_accordes++;
		}
	}

	// et les signaler dans la page
	$Crayons_preparer_page = charger_fonction('Crayons_preparer_page', 'inc');
	if ($droits_accordes === count($regs)) { // tous les droits
		$page = $Crayons_preparer_page($page, '*', $wdgcfg);
	} elseif ($droits) { // seulement certains droits, preciser lesquels
		$page = $Crayons_preparer_page($page, implode(',', array_keys($droits)), $wdgcfg);
	}

	return $page;
}

/**
 * Balise indiquant un champ SQL crayonnable
 *
 * @note
 *   Si cette fonction est absente, `balise_EDIT_dist()` déclarée par SPIP
 *   ne retourne rien
 *
 * @example
 *     ```
 *     <div class="#EDIT{texte}">#TEXTE</div>
 *     <div class="#EDIT{ps}">#PS</div>
 *     ```
 *
 * @param Champ $p
 *   Pile au niveau de la balise
 * @return Champ
 *   Pile complétée par le code à générer
**/
function balise_EDIT($p) {

	// le code compile de ce qui se trouve entre les {} de la balise
	$label = interprete_argument_balise(1, $p);

	// Verification si l'on est dans le cas d'une meta
	// #EDIT{meta-descriptif_site} ou #EDIT{meta-demo/truc}
	if (preg_match('/meta-(.*)\'/', $label, $meta)) {
		$type = 'meta';
		$label = 'valeur';
		$primary = $meta[1];
		$p->code = "classe_boucle_crayon('"
			. $type
			. "','"
			. $label
			. "',"
			. "str_replace('/', '__', '$primary')" # chaque / doit être remplacé pour CSS.
			. ").' '";
		$p->interdire_scripts = false;
		return $p;
	}

	$i_boucle = $p->nom_boucle ?: $p->id_boucle;
	// #EDIT hors boucle? ne rien faire
	if (!isset($p->boucles[$i_boucle]) || !($type = ($p->boucles[$i_boucle]->type_requete))) {
		$p->code = "''";
		$p->interdire_scripts = false;
		return $p;
	}

	// crayon sur une base distante 'nua__article-intro-5'
	if ($distant = $p->boucles[$i_boucle]->sql_serveur) {
		$type = $distant . '__' . $type;
	}

	$primary = $p->boucles[$i_boucle]->primary;
	// On rajoute ici un debug dans le cas où aucune clé primaire n'est trouvée.
	// Cela peut se présenter par exemple si on utilise #EDIT{monchamp} directement
	// dans une boucle CONDITION sans faire référence au nom de la boucle d'au dessus.
	if (!$primary) {
		erreur_squelette(_T('crayons:absence_cle_primaire'), $p);
	}

	$primary = explode(',', $primary);
	$id = [];
	foreach ($primary as $key) {
		$id[] = champ_sql(trim($key), $p);
	}
	$primary = implode(".'-'.", $id);

	$p->code = "classe_boucle_crayon('"
		. $type
		. "',"
		. sinon($label, "''")
		. ','
		. $primary
		. ").' '";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Balise indiquant une configuration crayonnable
 *
 * @example
 *     ```
 *     <div class="#EDIT_CONFIG{descriptif_site}">#DESCRIPTIF_SITE_SPIP</div>
 *     <div class="#EDIT_CONFIG{demo/truc}">#CONFIG{demo/truc}</div>
 *     ```
 *
 * @param Champ $p
 *   Pile au niveau de la balise
 * @return Champ
 *   Pile complétée par le code à générer
**/
if (!function_exists('balise_EDIT_CONFIG_dist')) {
	function balise_EDIT_CONFIG_dist($p) {

		// le code compile de ce qui se trouve entre les {} de la balise
		$config = interprete_argument_balise(1, $p);
		if (!$config) {
			return $p;
		}

		// chaque / du nom de config doit être transformé pour css.
		// nous utiliserons '__' à la place.

		$type = 'meta';
		$label = 'valeur';

		$p->code = "classe_boucle_crayon('"
			. $type
			. "','"
			. $label
			. "',"
			. "str_replace('/', '__', $config)"
			. ").' '";
		$p->interdire_scripts = false;
		return $p;
	}
}

/**
 * Crée le controleur du crayon indiqué par la classe CSS
 *
 * @param string $class
 *   Class CSS de crayon tel que créé par #EDIT
 * @return string
 *   HTML du crayon, sinon texte d'erreur
**/
function creer_le_crayon($class) {
	include_spip('inc/crayons');
	include_spip('action/crayons_html');
	$a = affiche_controleur($class, ['w' => 485, 'h' => 300, 'wh' => 500]);
	return $a['$erreur'] ?: $a['$html'];
}

/**
 * Balise `#CRAYON` affichant un formulaire de crayon
 *
 * SI `?edit=1;`
 *
 * @example
 *    ```
 *    #CRAYON{ps}
 *    ```
 *
 * @param Champ $p
 *   Pile au niveau de la balise
 * @return Champ
 *   Pile complétée par le code à générer
**/
function balise_CRAYON($p) {
	$p = balise_EDIT($p);
	$p->code = 'creer_le_crayon(' . $p->code . ')';
	return $p;
}


/**
 * Donne la classe CSS crayon
 *
 * En fonction :
 * - du type de la boucle
 *   (attention aux exceptions pour `#EDIT` dans les boucles HIERARCHIE et SITES)
 * - du champ demande (vide, + ou se terminant par + : (+)classe type--id)
 * - de l'id courant
 *
 * @param string $type
 *   Type d'objet, ou "meta" pour un champ de configuration
 * @param string $champ
 *   Champ SQL concerné
 * @param int|string $id
 *   Identifiant de la ligne sql
 * @return string
 *   Classes CSS (à ajouter dans le HTML à destination du javascript de Crayons)
**/
function classe_boucle_crayon($type, $champ, $id) {
	// $type = objet_type($type);
	$type = $type[strlen($type) - 1] == 's' ?
		substr($type, 0, -1) :
		str_replace(
			['hierarchie','syndication'],
			['rubrique','site'],
			$type
		);

	$plus = (substr($champ, -1) == '+' && ($champ = substr($champ, 0, -1)))
		? " $type--$id"
		: '';

	// test rapide pour verifier que l'id est valide (a-zA-Z0-9)
	if (false !== strpos($id, ' ')) {
		spip_log("L'identifiant ($id) ne pourra être géré ($type | $champ)", 'crayons');
		return 'crayon_id_ingerable';
	}

	return 'crayon ' . $type . '-' . $champ . '-' . $id . $plus;
}
