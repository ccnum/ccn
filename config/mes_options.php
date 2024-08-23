<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

// Dossier plugins en plus
define('_DIR_PLUGINS_SUPPL', _DIR_RACINE . 'plugins-ccn');

if (preg_match('/ddev.site/', $_SERVER['HTTP_HOST'])) {
	define('_NO_CACHE', -1);
	define('_INTERDIRE_COMPACTE_HEAD_ECRIRE', true);
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set("display_errors", "On");
	define('SPIP_ERREUR_REPORT', E_ALL);
	define('_TEST_EMAIL_DEST', 'pierrekuhn@ik.me');
} else {
	define('_AUTORISER_TELECHARGER_PLUGINS', false);
	$GLOBALS['spip_header_silencieux'] = 1;
}

define('_DOC_MAX_SIZE', 3000);

if (preg_match('/fictions./', $_SERVER['HTTP_HOST'])) {
	// PIPELINES
	//$GLOBALS['marqueur'] .= ':'.$_COOKIE['mobile'];
	//$GLOBALS['spip_pipeline']['pre_propre'] .= '|post_autobr';
	//$GLOBALS['spip_pipeline']['formulaire_traiter'] .= "|traiter_article_jeu";
	//define('_DEBUG_AUTORISER', true);
	//define('_AUTOBR', true);
	define('_cookie_annee_scolaire', 'laclasse_annee_scolaire');

	//Cas de la publication du chapitre d'une histoire (JEU)
	function traiter_article_jeu0($flux) {
		if (($flux['args']['form'] == 'editer_article')) {
			//Article
			$id_article = $flux['data']['id_article'];
			$id_rubrique = $flux['data']['id_rubrique'];
			//Publication
			sql_update('spip_articles', array("`statut`" => "'publie'"), 'id_article=' . intval($id_article));

			//Si 5ème chapitre
			if ($res = sql_select('titre', 'spip_articles', 'id_rubrique=' . $id_rubrique)) $n = sql_count($res);
			if ($n >= 5) {
				$id_parent = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));
				$mail = $flux['data']['soustitre'];
				creer_histoire($id_parent);
				$message = "http://air.laclasse.com/spip.php?page=lecture&id_rubrique=" . $id_rubrique;
				mail('pvincent@erasme.org', 'air.laclasse.com', $message);
			}
		}
		return $flux;
	}

	function annee_rub1($idr) {

		$date = sql_getfetsel("id_rubrique", "spip_rubriques", "id_rubrique=" . intval($idr));

		if ($date != '') {
			$annee_scolaire = intval(substr($date, 0, 4));
			$mois_scolaire = intval(substr($date, 5, 2));
			if ($mois_scolaire < 9) $annee_scolaire--;
		}

		return $annee_scolaire;
	}

	// ANNEE
	if (
		(isset($_COOKIE['_cookie_annee_scolaire']))
		&& ($_COOKIE['_cookie_annee_scolaire'] != 0)
		&& ($_COOKIE['_cookie_annee_scolaire'] != '')
		&& ($_COOKIE['_cookie_annee_scolaire'] > 2011)
	) {
		$annee_scolaire = $_COOKIE['_cookie_annee_scolaire'];
	} else {
		if (date('m') >= '08') {
			$annee_scolaire = date('Y');
		} else {
			$annee_scolaire = date('Y') - 1;
		}
	}

	if (
		(isset($_GET['annee_scolaire']))
		&& ($_GET['annee_scolaire'] != 0)
		&& ($_GET['annee_scolaire'] != '')
	) {
		$annee_scolaire = $_GET['annee_scolaire'];
		setcookie("_cookie_annee_scolaire", $annee_scolaire);
	}

	//Hack temporaire d'indexation d'année
	if ((isset($_GET['id_rubrique']))) {
		$id_rub = $_GET['id_rubrique'];
		/*$date = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rub));

		if ($date != '')
		{
		  $annee_scolaire = intval(substr($date,0,4));
		  $mois_scolaire = intval(substr($date,5,2));
		  if ($mois_scolaire < 9) $annee_scolaire--;
		}*/
		if ($annee_scolaire == "") $annee_scolaire = 2020;
	}

	define('_annee_scolaire', $annee_scolaire);

	//SQUELETTES

	// 'nom' => 'chemin du squelette'
	$squelettes = [
		'jeu' => 'squelettes/fictions/jeu:' . $dir . 'squelettes/air:squelettes/fictions',
		'air' => 'squelettes/fictions/air:' . $dir . 'squelettes'
	];

	// Si l'on demande un squelette particulier qui existe,  on pose un cookie, sinon suppression du cookie
	if (isset($_GET['scenario'])) {
		if (isset($squelettes[$_GET['scenario']])) {
			setcookie('spip_skel', $_COOKIE['spip_skel'] = $_GET['scenario'], time() + 10 * 24 * 3600, '/');
		} else {
			setcookie('spip_skel', $_COOKIE['spip_skel'] = '', -24 * 3600, '/');
		}
	}

	// Si un squelette particulier est sauve, on le definit comme dossier squelettes
	if (isset($_COOKIE['spip_skel']) and isset($squelettes[$_COOKIE['spip_skel']])) {
		$GLOBALS['dossier_squelettes'] = $squelettes[$_COOKIE['spip_skel']];
	} else {
		$GLOBALS['dossier_squelettes'] = $squelettes['air'];
	}
}
