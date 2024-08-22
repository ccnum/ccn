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
	define('_SITES_ADMIN_MUTUALISATION', 'ccn-archives.ddev.site');
} else {
	define('_SITES_ADMIN_MUTUALISATION', 'thematiques.laclasse.com');
	define('_AUTORISER_TELECHARGER_PLUGINS', false);
	date_default_timezone_set('Europe/Paris');
}

define('_DOC_MAX_SIZE', 1000000);
if (!is_readable(_DIR_RACINE . 'mutualisation/mutualiser.php')) {
	echo _L("Fichier 'mutualisation/mutualiser.php' manquant dans la racine " . _DIR_RACINE);
	exit;
}

require _DIR_RACINE . 'mutualisation/mutualiser.php';

/* placer dans ce tableau les sites ou l'on ne veut pas la redirection canonique */
$www = array();

$site = str_replace('www.', '', $_SERVER['HTTP_HOST']);
if ($site != $_SERVER['HTTP_HOST'] and !in_array($site, $www)) {
	include_spip('inc/headers');
	$req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
	if (
		isset($_SERVER['HTTPS'])
		and test_valeur_serveur($_SERVER['HTTPS'])
	) {
		$protocole = 'https';
	} elseif (!isset($_SERVER["SCRIPT_URI"]) or !($p = strpos($_SERVER["SCRIPT_URI"], '://'))) {
		$protocole = 'http';
	} else {
		$protocole = substr($_SERVER["SCRIPT_URI"], 0, $p);
	}
	redirige_par_entete($protocole . '://' . $site . $req);
}

// Compatibilite avec le ":" de $dossier_squelettes
// Si l'url indique explicitement un port (grace a ":")
// tout eliminer s'il s'agit du port 80
// et remplacer ":" par _ pour les autres ports

if (strpos($site, ':')) {
	if (preg_match('/:80$/', $site)) {
		$site = substr($site, -3);
	} else {
		$site = str_replace(':', '_', $site);
	}
}

define('_INSTALL_SITE_PREF', prefixe_mutualisation($site));
demarrer_site(
	$site,
	array(
		'creer_site' => false,        // Creer ou non le site s'il n'existe pas (defaut: false)
		'creer_base' => false,        // Creer ou non la base de donnee si elle n'existe pas (false)
		'creer_user_base' => false,  // Creer ou non un utilisateur pour la nouvelle base de donnee (false)
		'mail' => 'pracine@erasme.org', // Adresse mail pour recevoir un mail lors d'une creation de site mutualise ('')
		'code' => 'u8!96b',        // Code d'activation ('ecureuil')
		'table_prefix' => false,     // Definir automatiquement le prefixe de table (false) ... mettre true si tous les sites dans la meme base
		'cookie_prefix' => false,     // Definir automatiquement le prefixe de cookie (false)
		'repertoire' => 'sites',     // Nom du repertoire contenant les sites mutualises ('sites')
		'url_img_courtes' => false,   // Utiliser la redirection des URL d'images courtes dans la partie publique (false)
		// /!\ il faut qu'apache ait le droit d'ecrire dans les dossiers IMG/ et local/ a la racine du site !
		// C'est la que la mutualisation va ecrire les regles de redirection automatiques pour les images de chaque site
		# 'utiliser_panel' => false, // Utiliser une table externe pour recuperer des identifiants ... (code, user, pass) permettant a un utilisateur d'installer le site (false)
		# 'annonce' => '<p>Un service propos&eacute; par <a href="http://www.spip.net/">la communaut&eacute; SPIP</a></p>', // Texte a afficher en bas du formulaire d'activation de la mutualisation
		'url_creer_base' => ''       // Creer la base de donnees via une URL (methode AlternC)
	)
);


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
