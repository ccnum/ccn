<?php

/**
 * Plugin Authentification CAS
 * Copyright (c) Christophe IMBERTI
 * Licence Creative commons by-nc-sa
 */

include_spip('inc/cookie');
include_spip('inc/filtres');


/**
 * Determination du HOST
 *
 * @param : aucun
 * @return : host
 */
function cicas_url_host() {

	$ci_host = "";
	$hosts = "";

	// ordre de recherche personnalise dans le fichier de parametrage config/_config_cas.php
	// sinon ordre de recherche par defaut
	$cicashostordre = cicas_lire_cicashostordre();

	foreach ($cicashostordre as $valeur) {
		if (isset($_SERVER[$valeur])) {
			if ($_SERVER[$valeur]) {
				$ci_host = $_SERVER[$valeur];

				// cas de valeurs multiples
				if (in_array($valeur, array('HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'))) {
					if (strpos($ci_host, ',') !== false) {
						$hosts = explode(',', $ci_host);
						$ci_host = trim(reset($hosts));
					}
				}

				break;
			}
		}
	}

	return $ci_host;
}


/**
 * Determination de l'url de retour
 *
 * @param : demande de redirection (url)
 * @return : url de retour
 */
function cicas_url_retour($url_relative = '') {

	$ci_url = "";

	// Protocole
	if (cicas_is_https()) {
		$protocole = "https://";
	} else {
		$protocole = "http://";
	}

	// determination du HOST
	$ci_url = cicas_url_host();

	// cas particulier d'une adresse du type www.monserveur.com/repertoire_du_site/article.php3...
	if (isset($_SERVER['REQUEST_URI'])) {
		// le cas echeant, ne pas tenir compte du repertoire "ecrire"
		$ci_request_uri = str_replace("/ecrire/", "/", $_SERVER['REQUEST_URI']);

		$ci_pos = strrpos($ci_request_uri, "/");
		// ne pas tenir compte du premier "/" dans la recherche
		if ($ci_pos and $ci_pos > 0) {
			$ci_url .= substr($ci_request_uri, 0, $ci_pos);
		}
	}

	// demande de redirection
	// (ne pas tenir compte du repertoire "ecrire" ni de "./")
	if ($url_relative and substr($url_relative, 0, 6) != "ecrire" and substr($url_relative, 0, 1) != ".") {
		$ci_url .= "/" . $url_relative;
	}

	return $protocole . $ci_url;
}

/**
 * Determination du client_service_name
 *
 * @return : url du client_service_name
 */
function cicas_client_service_name() {

	$ci_url = '';
	$ci_http_header_are_not_secure = false;

	// Protocole
	if (cicas_is_https()) {
		$protocole = "https://";
	} else {
		$protocole = "http://";
	}


	if (defined('_CICAS_HTTP_HEADER_ARE_NOT_SECURE') and _CICAS_HTTP_HEADER_ARE_NOT_SECURE == 'oui') {
		$ci_http_header_are_not_secure = true;
	}


	if ($ci_http_header_are_not_secure and isset($GLOBALS['meta']['adresse_site'])) {
		// Pour éviter une modification malveillante du host.
		// Inconvénients de cette approche : la méta (adresse_site)
		// peut être fausse (sites avec plusieurs noms d’hôtes, déplacements, erreurs)
		$ci_url = $GLOBALS['meta']['adresse_site'];
		$ci_url = cicas_enlever_protocole($ci_url);
	} else {
		// If your PHP's HTTP header input X-Forwarded-Host, X-Forwarded-Server, Host,
		//  X-Forwarded-Proto, X-Forwarded-Protocol are sanitized before reaching PHP
		//
		// Pour memoire, une fois l'utilisateur authentifié
		// CICAS vérifie si cet utilisateur a un compte dans SPIP
		$ci_url = cicas_url_host();
	}

	// phpCAS ne veut pas de slash de fin
	if (substr($ci_url, -1) == '/') {
		$ci_url = substr($ci_url, 0, -1);
	}

	// cas particulier d'une adresse du type www.monserveur.com/repertoire_du_site
	$ci_pos = strrpos($ci_url, "/");
	if ($ci_pos and $ci_pos > 0) {
		$ci_url = substr($ci_url, 0, $ci_pos);
	}

	// Filtrer
	$ci_url = filter_var($ci_url, FILTER_SANITIZE_URL);
	if (!$ci_url) {
		$ci_url = '';
	}

	// Ajouter le port
	if (!strpos($ci_url, ':')) {
		if (empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
			$port = $_SERVER['SERVER_PORT'];
		} else {
			$ports = explode(',', $_SERVER['HTTP_X_FORWARDED_PORT']);
			$port = $ports[0];
		}

		$ci_url .= ':';
		$ci_url .= $port;
	}

	return $protocole . $ci_url;
}

function cicas_is_https() {
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		return ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTOCOL'])) {
		return ($_SERVER['HTTP_X_FORWARDED_PROTOCOL'] === 'https');
	} elseif (
		isset($_SERVER['HTTPS'])
		&& !empty($_SERVER['HTTPS'])
		&& strcasecmp($_SERVER['HTTPS'], 'off') !== 0
	) {
		return true;
	}
	return false;
}

/**
 * Determiner l'URL du serveur CAS (intranet, internet, ...)
 * correspondant à l'origine de l'appel (.i2 ou .ader.gouv.fr ou .gouv.fr ou .agri)
 * Les correspondances figurent dans le fichier de parametrage
 * sinon l'adresse par defaut est utilisee
 *
 * @param :
 * @return :  URL du serveur CAS correspondant à l'origine de l'appel
 */
function cicas_url_serveur_cas($id_serveur = 0, $forcer = false, $en_session = false) {

	$ciurlcas = '';

	// lire la configuration du plugin
	$tableau_config = cicas_lire_meta($id_serveur, $forcer, $en_session);

	// Pour la solution hybride utilisation d'un cookie
	if ($tableau_config['cicas'] == 'oui' or $tableau_config['cicas'] == 'hybride') {

		$ci_host = cicas_url_host();

		// adresse par defaut du serveur CAS
		$ciurlcas = $tableau_config['cicasurldefaut'];

		// autre adresse du serveur CAS	selon le type de terminaison de l'adresse d'appel du site SPIP
		if (isset($tableau_config['cicasurls'])) {
			if (is_array($tableau_config['cicasurls'])) {
				foreach ($tableau_config['cicasurls'] as $terminaison => $valcasurl) {
					if (substr($ci_host, -strlen($terminaison)) == $terminaison) {
						$ciurlcas = $valcasurl;
						break;
					}
				}
			}
		}
	}

	return cicas_enlever_protocole($ciurlcas);
}


/**
 * Determination du code de langue de phpCAS qui correspond au code de langue de SPIP
 *
 * @param : code de langue de SPIP
 * @return : code de langue de phpCAS
 */
function cicas_lang_phpcas() {

	$return = PHPCAS_LANG_FRENCH;

	if (isset($_GET['lang'])) {
		switch ($_GET['lang']) {
			case 'en':
				$return = PHPCAS_LANG_ENGLISH;
				break;
			case 'de':
				$return = PHPCAS_LANG_GERMAN;
				break;
			case 'es':
				$return = PHPCAS_LANG_SPANISH;
				break;
		}
	}

	return $return;
}


/**
 * Cible de l'operation de connexion
 *
 * @param : $prive
 * @return : URL
 */
function cicas_url_cible($prive = null) {

	$cible = "";

	// La cible de notre operation de connexion
	$url = _request('url');
	$cible = isset($url) ? $url : _DIR_RESTREINT;

	// cas du formulaire login_public dans un autre squelette que la page login
	if (!$url) {
		if (isset($_SERVER['REQUEST_URI'])) {
			$cible = $_SERVER['REQUEST_URI'];

			// enlever TARGET, etc.
			$ci_pos = strrpos($cible, "&TARGET=");
			if ($ci_pos and $ci_pos > 0)
				$cible = substr($cible, 0, $ci_pos);

			$cible = str_replace('&cicas=oui', '', $cible);
		} else {
			$cible = cicas_url_host();
		}
	}

	// Si on se connecte dans l'espace prive,
	// ajouter "bonjour" (repere a peu pres les cookies desactives)
	if (is_null($prive) ? cicas_is_url_prive($cible) : $prive) {
		$cible = parametre_url($cible, 'bonjour', 'oui', '&');
	}
	if ($cible == '@page_auteur')
		$cible = generer_url_entite($GLOBALS['auteur_session']['id_auteur'], 'auteur');

	if ($cible) {
		$cible = parametre_url($cible, 'var_login', '', '&');
	}

	// transformer la cible absolue en cible relative
	// pour pas echouer quand la meta adresse_site est foireuse
	if (strncmp($cible, $u = url_de_base(), strlen($u)) == 0) {
		$cible = "./" . substr($cible, strlen($u));
	}

	return $cible;
}


/**
 * L'URL correspond-t-elle a l'espace prive de SPIP ?
 *
 * @param : URL
 * @return : true ou false
 */
function cicas_is_url_prive($cible) {
	$parse = parse_url($cible);
	return strncmp(substr($parse['path'], -strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS)) == 0;
}


/**
 * Verification de l'existence de l'identifiant dans la table des auteurs
 *
 * @param : identifiant de l'utilisateur renvoye par CAS
 * @return : tableau vide ou contenant la ligne de l'auteur dans spip_auteurs
 */
function cicas_verifier_identifiant($ci_cas_userid) {

	$return = array();

	// lire la configuration du plugin
	$tableau_config = cicas_lire_meta(0, false, true);

	// Interdire un email vide
	if (empty($ci_cas_userid)) {
		$return = array();
	} else {
		// Eviter l'injection SQL
		$ci_cas_userid = addslashes($ci_cas_userid);

		$select = "*";
		$from = "spip_auteurs";
		$where = "(email='" . $ci_cas_userid . "' OR email='" . addslashes(strtolower($ci_cas_userid)) . "') AND statut<>'5poubelle'";
		$groupby = "";
		$orderby = "nom";

		if ($tableau_config['cicasuid'] == "login")
			$where = "(login='" . $ci_cas_userid . "') AND statut<>'5poubelle'";


		$cinumrows = sql_countsel($from, $where);

		if ($cinumrows == 0) {
			$return = array();
		} else if ($cinumrows == 1) {
			$result = sql_select($select, $from, $where, $groupby, $orderby);
			if ($row = sql_fetch($result)) {
				$return = $row;
			}
		} else if ($cinumrows > 1) {
			$ci_statut = "";
			$result = sql_select($select, $from, $where, $groupby, $orderby);
			while ($row = sql_fetch($result)) {
				$cistocker = true;
				if ($ci_statut) {
					switch ($row['statut']) {
						case '0minirezo':
							if ($ci_statut == '0minirezo') {
								// garder le pr�c�dent si le suivant est un admin restreint
								$cinewid = $row['id_auteur'];
								$cirestreint = sql_countsel("spip_auteurs_liens", "objet='rubrique' AND id_auteur=" . $cinewid);

								if ($cirestreint > 0) $cistocker = false;
							}
							break;
						case 'ciredval':
							if ($ci_statut == '0minirezo') $cistocker = false;
							break;
						case '1comite':
							if (preg_match("/^(0minirezo|ciredval)$/", $ci_statut)) $cistocker = false;
							break;
						case '6forum':
							if (preg_match("/^(0minirezo|ciredval|1comite)$/", $ci_statut)) $cistocker = false;
							break;
					}
				}

				if ($cistocker) {
					$ci_statut = $row['statut'];
					$return = $row;
				}
			}
		}
	}


	if (isset($return['id_auteur'])) {
		// Pour la solution hybride
		if (_request('cicas')) {
			if (_request('cicas') == "oui" or intval(_request('cicas')) >= 1) {
				if (!isset($_COOKIE['cicas_sso'])) {
					$ci_id_random = mt_rand(1, 999999);
					if (!$ci_id_random) $ci_id_random = rand(1, 999999);
					spip_setcookie('cicas_sso', $ci_id_random);
				}
			}
		}
	}

	return $return;
}

function cicas_lire_meta($id_serveur = 0, $force = false, $en_session = false) {
	static $configs = array();

	if ($en_session) {
		if (
			isset($GLOBALS['visiteur_session']['cicas_id_serveur'])
			and is_numeric($GLOBALS['visiteur_session']['cicas_id_serveur'])
		)
			$id_serveur = $GLOBALS['visiteur_session']['cicas_id_serveur'];
	}

	if (!isset($configs[$id_serveur]) or $force) {

		$vars_serveur = array(
			'cicasurldefaut',
			'cicasrepertoire',
			'cicasport',
			'cicasuid',
			'cicas_creer_auteur',
			'cicasurls',
			'cicasmailcompatible',
			'cicas_svu_url',
			'cicas_svu_repertoire',
			'cicas_svu_port'
		);
		$vars_autres = array('cicas', 'cicas_serveurs_additionnels');
		$vars = array_merge($vars_serveur, $vars_autres);
		// Ne pas mettre cicashostordre, car il ne faut pas le memoriser dans spip_meta.
		// En effet, seul le parametrage par fichier doit pouvoir l'ajouter

		// Initialisation
		foreach ($vars as $var) {
			$GLOBALS['ciconfig'][$var] = '';
		}

		// Exceptions
		$GLOBALS['ciconfig']['cicasurls'] = array();
		$GLOBALS['ciconfig']['cicasmailcompatible'] = array();
		$GLOBALS['ciconfig']['cicas_serveurs_additionnels'] = array();


		// parametrage par fichier
		$GLOBALS['ciconfig'] = cicas_tableau_parametrage_par_fichier();

		if (!$GLOBALS['ciconfig']) {
			// configuration du plugin
			if (isset($GLOBALS['meta']['cicas'])) {
				$tableau = array();
				$tableau = @unserialize($GLOBALS['meta']['cicas']);
				reset($vars);
				foreach ($vars as $var) {
					if (isset($tableau[$var])) {
						$GLOBALS['ciconfig'][$var] = $tableau[$var];
					}
				}
			}
		}

		// valeur par defaut
		if (!isset($GLOBALS['ciconfig']['cicas']))
			$GLOBALS['ciconfig']['cicas'] = 'non';
		elseif ($GLOBALS['ciconfig']['cicas'] == '')
			$GLOBALS['ciconfig']['cicas'] = 'non';

		// Cas d'un serveur additionnel
		// on bascule les valeurs du serveur additionnel dans les valeurs de base
		if ($id_serveur >= 1) {
			// reset(vars);
			foreach ($vars_serveur as $var) {
				if (isset($GLOBALS['ciconfig']['cicas_serveurs_additionnels'][$id_serveur][$var])) {
					$GLOBALS['ciconfig'][$var] =  $GLOBALS['ciconfig']['cicas_serveurs_additionnels'][$id_serveur][$var];
				} else {
					if (in_array($var, array('cicasurls', 'cicasmailcompatible'))) {
						$GLOBALS['ciconfig'][$var] = array();
					} else {
						$GLOBALS['ciconfig'][$var] = '';
					}
				}
			}
		}

		// On memorise dans $configs[$id_serveur]
		// reset(vars);
		foreach ($vars as $var) {
			if (isset($GLOBALS['ciconfig'][$var])) {
				$configs[$id_serveur][$var] = $GLOBALS['ciconfig'][$var];
			}
		}
	}

	return $configs[$id_serveur];
}

function cicas_tableau_parametrage_par_fichier() {
	static $tableau;

	if (!isset($tableau)) {
		$tableau = array();

		// chemin d'un eventuel fichier de parametrage
		$f = 'config/_config_cas.php';

		if (@file_exists($f)) {
			// parametrage par fichier
			include_once($f);

			// compatibilite ascendante
			if ($GLOBALS['ciconfig']['cicasport'] == '')
				$GLOBALS['ciconfig']['cicasport'] = '443';

			$tableau = $GLOBALS['ciconfig'];
		}
	}

	return $tableau;
}


/**
 * A-t-on un parametrage par fichier ?
 *
 * @param : aucun
 * @return : true si parametrage par fichier, sinon false
 */
function cicas_parametrage_par_fichier() {
	static $return;

	if (!isset($return)) {
		$return = false;

		// compatibilite ascendante
		if (cicas_tableau_parametrage_par_fichier())
			$return = true;
	}

	return $return;
}

/**
 * Lecture des serveurs additionnels
 *
 * @return : array
 */
function cicas_lire_serveurs_additionnels() {
	$serveurs = array();

	$tableau_config = cicas_lire_meta();

	if (isset($tableau_config['cicas_serveurs_additionnels']))
		$serveurs = $tableau_config['cicas_serveurs_additionnels'];

	return $serveurs;
}

/**
 * Nombre de serveurs additionnels
 *
 * @return : int
 */
function cicas_nombre_serveurs_additionnels() {
	$nombre = 0;

	$serveurs = cicas_lire_serveurs_additionnels();
	if (is_array($serveurs)) {
		$nombre = count(cicas_lire_serveurs_additionnels());
	}

	return $nombre;
}


/**
 * Lecture d'un serveur additionnel
 *
 * @return : array
 */
function cicas_lire_serveur_additionnel($id_serveur) {
	$id_serveur = intval($id_serveur);
	$data = array();

	if ($id_serveur >= 1) {
		$tableau_config = cicas_lire_meta();

		if (isset($tableau_config['cicas_serveurs_additionnels'][$id_serveur]))
			$data = $tableau_config['cicas_serveurs_additionnels'][$id_serveur];
	}
	return $data;
}

/**
 * Ecrire un serveur additionnel
 */
function cicas_ecrire_serveur_additionnel($id_serveur = 0, $data = array()) {
	$id_serveur = intval($id_serveur);

	if ($id_serveur >= 1 and $data and is_array($data)) {
		include_spip('inc/meta');
		$tableau_config = cicas_lire_meta();
		$tableau_config['cicas_serveurs_additionnels'][$id_serveur] = $data;
		ecrire_meta('cicas', @serialize($tableau_config));
	}
}

/**
 * Supprimer un serveur additionnel
 */
function cicas_supprimer_serveur_additionnel($id_serveur = 0) {
	$id_serveur = intval($id_serveur);

	if ($id_serveur >= 1) {
		include_spip('inc/meta');
		$tableau_config = cicas_lire_meta();

		if (isset($tableau_config['cicas_serveurs_additionnels'][$id_serveur])) {
			unset($tableau_config['cicas_serveurs_additionnels'][$id_serveur]);
			ecrire_meta('cicas', @serialize($tableau_config));
		}
	}
}


function cicas_icone_verticale($lien, $texte, $fond, $fonction = "", $class = "", $javascript = "") {
	return icone_base($lien, $texte, $fond, $fonction, "verticale $class", $javascript);
}

function cicas_filtrer_url($texte, $verif_only = false) {
	$safe = '';

	$t = trim($texte);
	include_spip('inc/filtres');
	$t = filtrer_entites($t);

	if ($t) {
		// supprimer les espaces
		$t = trim(str_replace(' ', '', $t));

		// interdire les caracteres dangereux
		// limiter à a-zA-Z0-9 et aux slash, deux points, =, &, diese, underscores, tirets, points, point interrogation
		$tableau = array('/', ":", "=", "&", '#', "_", '-', '.', ',', '?');
		$safe = cicas_filtrer_caracteres($t, $tableau, $verif_only);

		// supprimer les espaces
		if (!$verif_only)
			$safe = trim(str_replace(' ', '', $safe));
	}

	return $safe;
}



function cicas_filtrer_caracteres($t, $tableau = array(), $verif_only = false) {
	$safe = '';
	$verif = true;

	if ($t and is_array($tableau)) {

		// supprimer les accents
		include_spip('inc/charsets');
		$tsa = translitteration($t);

		$tscs = str_replace($tableau, '', $tsa);
		if (!ctype_alnum($tscs)) {
			$verif = false;

			// passer le cas echeant en iso pour avoir la meme longueur avec ou sans accent en utf-8
			if ($GLOBALS['meta']['charset'] != 'iso-8859-1')
				$tiso = iconv(strtoupper($GLOBALS['meta']['charset']), "ISO-8859-1", $t);
			else
				$tiso = $t;

			// enlever les caracteres speciaux
			$longueur = strlen($tsa);
			for ($i = 0; $i < $longueur; $i++) {
				if (ctype_alnum($tsa[$i]) or in_array($tsa[$i], $tableau))
					$safe .= $tiso[$i];
				else
					$safe .= ' ';
			}
			// repasser le cas echeant dans le charset du site
			if ($GLOBALS['meta']['charset'] != 'iso-8859-1')
				$safe = iconv("ISO-8859-1", strtoupper($GLOBALS['meta']['charset']), $safe);
		} else {
			$safe = $t;
		}
	}

	if ($verif_only)
		return $verif;

	return $safe;
}

function cicas_lire_cicashostordre() {
	static $done = false;
	static $tableau = array();

	if (!$done) {
		$done = true;

		// parametrage par fichier
		$GLOBALS['ciconfig'] = cicas_tableau_parametrage_par_fichier();

		// On memorise dans $tableau
		if (isset($GLOBALS['ciconfig']['cicashostordre']))
			$tableau = $GLOBALS['ciconfig']['cicashostordre'];
		else
			// ordre de recherche par defaut (celui de phpCAS)
			$tableau = array('HTTP_HOST', 'SERVER_NAME', 'HTTP_X_FORWARDED_SERVER');
	}

	return $tableau;
}


/**
 * Configure le client CAS
 *
 * @param : id du serveur CAS le cas echeant
 */
function cicas_configure_phpCAS($id_serveur = 0) {

	$tableau_config = cicas_lire_meta($id_serveur, true);

	// Determiner l'origine de l'appel (intranet, internet, ...)
	// .i2 ou .ader.gouv.fr ou .gouv.fr ou .agri
	$ciurlcas = cicas_url_serveur_cas($id_serveur);

	$cirep = '';
	$ciport = intval($tableau_config['cicasport']);
	if (isset($tableau_config['cicasrepertoire'])) $cirep = $tableau_config['cicasrepertoire'];

	phpCAS::client(CAS_VERSION_2_0, $ciurlcas, $ciport, $cirep, cicas_client_service_name());

	// si url differente pour la validation du ticket
	if (isset($tableau_config['cicas_svu_url']) and $tableau_config['cicas_svu_url']) {
		$ci_svu = 'https://' . $tableau_config['cicas_svu_url'];

		if (isset($tableau_config['cicas_svu_port']) and $tableau_config['cicas_svu_port'] != 443)
			$ci_svu .= ':' . $tableau_config['cicas_svu_port'];

		if (isset($tableau_config['cicas_svu_repertoire']))
			$ci_svu .= $tableau_config['cicas_svu_repertoire'];

		$ci_svu .= '/serviceValidate';

		phpCAS::setServerServiceValidateURL($ci_svu);
	}

	// langue
	phpCAS::setLang(cicas_lang_phpcas());

	// enlever le pied de page de CAS
	phpCAS::SetHTMLFooter('<hr>');

	// Pour les versions recentes de phpCAS
	phpCAS::setNoCasServerValidation();
}

function cicas_enlever_protocole($texte) {
	if (!empty($texte)) {
		if (strtolower(substr($texte, 0, 7)) == "http://") {
			$texte = substr($texte, 7);
		} elseif (strtolower(substr($texte, 0, 8)) == "https://") {
			$texte = substr($texte, 8);
		}
	}
	return $texte;
}
