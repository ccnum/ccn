<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

include_spip('inc/cookie');
include_spip('inc/filtres');

/**
 * Determination du HOST du site web
 *
 * @return string
 */
function cioidc_url_host() {

	$ci_host = '';
	$hosts = '';

	// ordre de recherche personnalise dans le fichier de parametrage config/_config_cioidc.php
	// sinon ordre de recherche par defaut
	$cioidc_host_ordre = cioidc_lire_host_ordre();

	foreach ($cioidc_host_ordre as $valeur) {
		if (isset($_SERVER[$valeur])) {
			if ($_SERVER[$valeur]) {
				$ci_host = $_SERVER[$valeur];

				// cas de valeurs multiples
				if (in_array($valeur, ['HTTP_X_FORWARDED_HOST'])) {
					if (strpos($ci_host, ',') !== false) {
						$hosts = explode(',', $ci_host);
						$ci_host = trim(reset($hosts));
					}
				}

				// securite sur le contenu de l'entete
				$ci_host = strtr($ci_host, "<>?\"\{\}\$'` \r\n", '____________');
				
				break;
			}
		}
	}

	return $ci_host;
}

/**
 * Determination de l'url de retour
 *
 * @param string $url_relative
 * @return string
 */
function cioidc_url_retour($url_relative = '') {

	$ci_url = '';

	// Protocole
	if (cioidc_is_https()) {
		$protocole = 'https://';
	} else {
		$protocole = 'http://';
	}

	// determination du HOST
	$ci_url = cioidc_url_host();

	// cas particulier d'une adresse du type www.monserveur.com/repertoire_du_site/article.php...
	if (isset($_SERVER['REQUEST_URI'])) {
		// le cas echeant, ne pas tenir compte du repertoire 'ecrire'
		$ci_request_uri = str_replace('/ecrire/', '/', $_SERVER['REQUEST_URI']);

		$ci_pos = strrpos($ci_request_uri, '/');
		// ne pas tenir compte du premier '/' dans la recherche
		if ($ci_pos && $ci_pos > 0) {
			$ci_url .= substr($ci_request_uri, 0, $ci_pos);
		}
	}

	// demande de redirection
	// (ne pas tenir compte du repertoire 'ecrire' ni de './')
	if ($url_relative && substr($url_relative, 0, 6) != 'ecrire' && substr($url_relative, 0, 1) != '.') {
		$ci_url .= '/' . $url_relative;
	}

	return $protocole . $ci_url;
}

/**
 * Est-on en https ?
 *
 * @return boolean
 */
function cioidc_is_https() {
	$return = false;

	if (
		(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) === 'https')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && (strcasecmp($_SERVER['HTTP_X_FORWARDED_SSL'], 'off') !== 0))
		|| (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME']) && strtolower($_SERVER['HTTP_X_FORWARDED_SCHEME']) === 'https')
		|| (!empty($_SERVER['HTTP_X_PROTO']) && strtolower($_SERVER['HTTP_X_PROTO']) === 'https')
		|| (!empty($_SERVER['HTTP_X_PROTO']) && strtolower($_SERVER['HTTP_X_PROTO']) === 'ssl')
		|| (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https')
		|| (!empty($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'off') !== 0))
		|| (!empty($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) == 443)		
	) {
		$return = true;
	}

	return $return;
}

/**
 * Determiner l'URL du serveur OpenID Connect (intranet, internet, ...)
 * correspondant à l'origine de l'appel (.rie.gouv.fr ou .gouv.fr ou .agri)
 * Les correspondances figurent dans le fichier de parametrage
 * sinon l'adresse par defaut est utilisee
 *
 * @param int $id_serveur
 * @param bool $forcer pour contourner la mémorisation en variable statique
 * @param bool $en_session permet de lire l'id_serveur mémorisé dans la session de l'utilisateur
 * @return  string URL du serveur OpenID Connect correspondant à l'origine de l'appel
 */
function cioidc_url_serveur_oidc($id_serveur = 0, $forcer = false, $en_session = false) {

	$ci_url_oidc = '';

	// lire la configuration du plugin
	$tableau_config = cioidc_lire_meta($id_serveur, $forcer, $en_session);

	if ($tableau_config && ($tableau_config['mode_auth'] == 'oidc' || $tableau_config['mode_auth'] == 'hybride')) {
		$ci_host = cioidc_url_host();

		// adresse par defaut du serveur OpenID Connect
		if (isset($tableau_config['url_serveur'])) {
			$ci_url_oidc = $tableau_config['url_serveur'];
		}

		// autre adresse du serveur OpenID Connect selon le type de terminaison de l'adresse d'appel du site SPIP
		$id_serveur = intval($id_serveur);
		$cioidc_urls = [];
		if (defined('_CIOIDC_URLS')) {
			$cioidc_urls = _CIOIDC_URLS;
		}

		if (
				$cioidc_urls
				&& is_array($cioidc_urls)
				&& isset($cioidc_urls[$id_serveur])
				&& is_array($cioidc_urls[$id_serveur])
		) {
				foreach ($cioidc_urls[$id_serveur] as $terminaison => $valeur_url) {
					if (substr($ci_host, -strlen($terminaison)) == $terminaison) {
						$ci_url_oidc = $valeur_url;
						// Pas de slash à la fin de l'url du serveur
						if (substr($ci_url_oidc, -1) == '/') {
							$ci_url_oidc = substr($ci_url_oidc, 0, -1);
						}

						break;
					}
				}
		}
	}

	return $ci_url_oidc;
}

/**
 * Cible de l'operation de connexion (page du site web ou espace privé)
 *
 * @param bool $prive (si on se connecte dans l'espace prive, permet d'ajouter "bonjour")
 * @return string URL
 */
function cioidc_url_cible($prive = null) {

	$cible = '';

	// La cible de notre operation de connexion
	$url = '';
	if (isset($_COOKIE['cioidc_redirect']) && $_COOKIE['cioidc_redirect']) {
		$uri_redirect = urldecode($_COOKIE['cioidc_redirect']);
		$tableau_uri = explode('url=', $uri_redirect);
		if (isset($tableau_uri[1])) {
			$uri_redirect = urldecode($tableau_uri[1]);
			$url = str_replace('&amp;cioidc=oui', '', $uri_redirect);
			$url = str_replace('&cioidc=oui', '', $url);
		}
	}

	$cible = $url ? $url : _DIR_RESTREINT;

	// Si on se connecte dans l'espace prive,
	// ajouter "bonjour" (repere a peu pres les cookies desactives)
	if (is_null($prive) ? cioidc_is_url_prive($cible) : $prive) {
		$cible = parametre_url($cible, 'bonjour', 'oui', '&');
	}

	if ($cible) {
		$cible = parametre_url($cible, 'var_login', '', '&');

		// transformer la cible absolue en cible relative
		if (strncmp($cible, $u = url_de_base(), strlen($u)) == 0) {
			$cible = './' . substr($cible, strlen($u));
		}
	}

	return $cible;
}

/**
 * Teste si une URL est une URL de l'espace privé (administration de SPIP)
 * ou de l'espace public
 *
 * @param string $cible URL
 * @return bool
 *     true si espace privé, false sinon.
 * */
function cioidc_is_url_prive($cible) {
	include_spip('inc/filtres_mini');
	$path = parse_url(tester_url_absolue($cible) ? $cible : url_absolue($cible));
	$path = ($path['path'] ?? '');

	return strncmp(substr($path, -strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS)) == 0;
}

/**
 * Verification de l'existence de l'identifiant dans la table des auteurs
 *
 * @param string $ci_oidc_userid Identifiant de l'utilisateur renvoye par le serveur OIDC
 * @return array Tableau vide ou contenant la ligne de l'auteur dans spip_auteurs
 */
function cioidc_verifier_identifiant($ci_oidc_userid) {

	$return = [];

	// lire la configuration du plugin
	$tableau_config = cioidc_lire_meta(0, false, true);

	// Interdire un email vide
	if (empty($ci_oidc_userid)) {
		$return = [];
	} else {
		// Eviter l'injection SQL
		$ci_oidc_userid = addslashes($ci_oidc_userid);

		$select = '*';
		$from = 'spip_auteurs';
		$where = "(email='" . $ci_oidc_userid . "' OR email='" . strtolower($ci_oidc_userid) . "') AND statut<>'5poubelle'";
		$groupby = '';
		$orderby = 'nom';

		if (isset($tableau_config['uid_champ_spip']) && $tableau_config['uid_champ_spip'] == 'login') {
			$where = "(login='" . $ci_oidc_userid . "') AND statut<>'5poubelle'";
		}

		$cinumrows = sql_countsel($from, $where);

		if ($cinumrows == 0) {
			$return = [];
		} elseif ($cinumrows == 1) {
			$result = sql_select($select, $from, $where, $groupby, $orderby);
			if ($row = sql_fetch($result)) {
				$return = $row;
			}
		} elseif ($cinumrows > 1) {
			$ci_statut = '';
			$result = sql_select($select, $from, $where, $groupby, $orderby);
			while ($row = sql_fetch($result)) {
				$cistocker = true;
				if ($ci_statut) {
					switch ($row['statut']) {
						case '0minirezo':
							if ($ci_statut == '0minirezo') {
								// garder le précédent si le suivant est un admin restreint
								$cinewid = $row['id_auteur'];
								$cirestreint = sql_countsel('spip_auteurs_liens', "objet='rubrique' AND id_auteur=" . $cinewid);

								if ($cirestreint > 0) {
									$cistocker = false;
								}
							}
							break;
						case 'ciredval':
							if ($ci_statut == '0minirezo') {
								$cistocker = false;
							}
							break;
						case '1comite':
							if (preg_match('/^(0minirezo|ciredval)$/', $ci_statut)) {
								$cistocker = false;
							}
							break;
						case '6forum':
							if (preg_match('/^(0minirezo|ciredval|1comite)$/', $ci_statut)) {
								$cistocker = false;
							}
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
		if (_request('cioidc')) {
			if (_request('cioidc') == 'oui' || intval(_request('cioidc')) >= 1) {
				if (!isset($_COOKIE['cioidc_sso'])) {
					$ci_id_random = mt_rand(1, 999999);
					// SPIP 3.2 n'accepte pas l'option 'httponly'
					if ($GLOBALS['spip_version_branche'] >= 4.2) {
						spip_setcookie('cioidc_sso', $ci_id_random, ['httponly' => true]);
					} else {
						spip_setcookie('cioidc_sso', $ci_id_random);
					}
				}
			}
		}
	}

	return $return;
}

/**
 * Lire la configuration du plugin
 *
 * @param int $id_serveur
 * @param bool $force Pour contourner la mémorisation en variable statique
 * @param bool $en_session Permet de lire l'id_serveur mémorisé dans la session de l'utilisateur
 * @return array Tableau contenant la configuration du plugin
 */
function cioidc_lire_meta($id_serveur = 0, $force = false, $en_session = false) {
	static $configs = [];
	$config = [];

	if ($en_session) {
		if (
			isset($GLOBALS['visiteur_session']['cioidc_id_serveur'])
			&& is_numeric($GLOBALS['visiteur_session']['cioidc_id_serveur'])
		) {
			$id_serveur = $GLOBALS['visiteur_session']['cioidc_id_serveur'];
		}
	}

	if (!isset($configs[$id_serveur]) || $force) {
		$vars_serveur = cioidc_tableau_des_champs();

		// Ne pas mettre cioidc_host_ordre, ..., car il ne faut pas le memoriser dans spip_meta.
		// En effet, seul le parametrage par fichier doit pouvoir l'ajouter
		$vars_autres = ['mode_auth', 'titre_se_connecter', 'nom_serveur_spip', 'serveurs_additionnels'];

		$vars = array_merge($vars_serveur, $vars_autres);

		// Initialisation
		foreach ($vars as $var) {
			$config[$var] = '';
		}

		// Exception
		$config['serveurs_additionnels'] = [];

		// configuration du plugin
		// renseignée manuellement dans le formulaire de configutation
		if (isset($GLOBALS['meta']['cioidc'])) {
			$tableau = [];
			$tableau = (array) unserialize($GLOBALS['meta']['cioidc']);
			$config = array_merge($config, $tableau);
		}


		// parametrage par fichier (ou par constante dans un fichier d'options)
		cioidc_charger_fichier_parametrage();

		if (defined('_CIOIDC_MODE_AUTH')) {
			if (in_array(_CIOIDC_MODE_AUTH, ['oidc', 'hybride', 'spip'])) {
				$config['mode_auth'] = _CIOIDC_MODE_AUTH;
			}
		}
		if (defined('_CIOIDC_TITRE_SE_CONNECTER')) {
			if (cioidc_filtrer_titre_se_connecter(_CIOIDC_TITRE_SE_CONNECTER, true)) {
				$config['titre_se_connecter'] = _CIOIDC_TITRE_SE_CONNECTER;
			}
		}
		if (defined('_CIOIDC_NOM_SERVEUR_SPIP')) {
			if (cioidc_filtrer_nom_serveur_spip(_CIOIDC_NOM_SERVEUR_SPIP, true)) {
				$config['nom_serveur_spip'] = _CIOIDC_NOM_SERVEUR_SPIP;
			}
		}


		foreach ($vars_serveur as $var) {
			$constante = '_CIOIDC_' . strtoupper($var);
			if (defined($constante)) {
				if (cioidc_verifier($var, constant($constante))) {
					$config[$var] = constant($constante);
				}
			}
		}


		if (defined('_CIOIDC_SERVEURS_ADDITIONNELS')) {
			$serveurs_additionnels = _CIOIDC_SERVEURS_ADDITIONNELS;
			if (is_array($serveurs_additionnels)) {
				foreach ($serveurs_additionnels as $cle => $serveur_additionnel) {
					$serveur_verifie = [];
					foreach ($vars_serveur as $var) {
						if (isset($serveur_additionnel[$var])) {
							if (cioidc_verifier($var, $serveur_additionnel[$var])) {
								$serveur_verifie[$var] = $serveur_additionnel[$var];
							}
						}
					}

					if ($serveur_verifie) {
						$config['serveurs_additionnels'][$cle] = $serveur_verifie;
					}
				}
			}
		}



		// valeurs par defaut
		$valeurs_par_defaut = cioidc_tableau_des_valeurs_par_defaut();
		foreach ($valeurs_par_defaut as $cle_defaut => $valeur_defaut) {
			if (!isset($config[$cle_defaut]) || $config[$cle_defaut] == '') {
				$config[$cle_defaut] = $valeur_defaut;
			}
		}


		// Cas d'un serveur additionnel
		// on bascule les valeurs du serveur additionnel dans les valeurs de base
		// Si cet id_serveur n'existe pas, on reste sur le serveur de base.
		if ($id_serveur >= 1 && isset($config['serveurs_additionnels'][$id_serveur])) {
			foreach ($vars_serveur as $var) {
				if (isset($config['serveurs_additionnels'][$id_serveur][$var])) {
					$config[$var] = $config['serveurs_additionnels'][$id_serveur][$var];
				} else {
					$config[$var] = '';
				}
			}
		}

		// Pas de slash à la fin de l'url du serveur
		if (isset($config['url_serveur']) && substr($config['url_serveur'], -1) == '/') {
			$config['url_serveur'] = substr($config['url_serveur'], 0, -1);
		}

		// On memorise dans $configs[$id_serveur]
		foreach ($vars as $var) {
			if (isset($config[$var])) {
				$configs[$id_serveur][$var] = $config[$var];
			}
		}
	}

	return $configs[$id_serveur];
}

/**
 * Charger l'éventuel fichier de paramétrage
 */
function cioidc_charger_fichier_parametrage() {
	static $done = false;

	if (!$done) {
		$done = true;
		$f = _DIR_ETC . '_config_cioidc.php';
		if (@file_exists($f)) {
			include_once($f);
		}
	}
}

/**
 * Tableau des champs de configuration d'un serveur OIDC
 *
 * @return array
 */
function cioidc_tableau_des_champs() {
	$champs = [
		'nom_serveur',
		'url_serveur',
		'client_nom',
		'client_secret',
		'uid_champ_spip',
		'uid_claim',
		'scopes',
		'acr',
		'authorization_endpoint',
		'token_endpoint',
		'userinfo_endpoint',
		'end_session_endpoint',
		'jwks_uri',
		'token_endpoint_auth_methods_supported',
		'serveur_sans_userinfo',
		'http_proxy',
		'creer_auteur',
		'redirect_url_avec_pi'
	];

	return $champs;
}

/**
 * Tableau des valeurs par défaut de certains champs de configuration
 *
 * @return array
 */
function cioidc_tableau_des_valeurs_par_defaut() {
	// valeurs par defaut
	$valeurs_par_defaut = [
		'mode_auth' => 'spip',
		'titre_se_connecter' => _T('cioidc:titre_se_connecter_defaut'),
		'nom_serveur_spip' => _T('cioidc:nom_serveur_spip_defaut'),
		'serveur_sans_userinfo' => 'non',
		'http_proxy' => 'non',
		'redirect_url_avec_pi' => 'oui',
		'uid_champ_spip' => 'email',
		'uid_claim' => 'email',
	];

	return $valeurs_par_defaut;
}

/**
 * Tableau des champs obligatoires de configuration d'un serveur OIDC
 *
 * @return array
 */
function cioidc_tableau_des_champs_obligatoires() {
	$champs = [
		'nom_serveur',
		'url_serveur',
		'client_nom',
		'client_secret',
		'uid_champ_spip',
		'uid_claim'
	];

	return $champs;
}

/**
 * Fonction qui centralise la vérification des champs
 *
 * @param string $champ Nom du champ
 * @param string $texte Valeur renseignée pour ce champ
 * @param bool|string
 * @return bool|string
 */
function cioidc_verifier($champ, $texte) {
	if (empty($champ)) {
		return false;
	}

	if (ctype_alnum(str_replace('_', '', $champ)) && function_exists($f = 'cioidc_filtrer_' . $champ)) {
		return $f($texte, true);
	} else {
		return false;
	}
}

/**
 * Fonction qui centralise le filtrage des champs
 *
 * @param string $champ (nom du champ)
 * @param string $texte (valeur renseignée pour ce champ)
 * @return bool|string
 */
function cioidc_filtrer($champ, $texte) {
	if (empty($champ)) {
		return false;
	}

	if (ctype_alnum(str_replace('_', '', $champ)) && function_exists($f = 'cioidc_filtrer_' . $champ)) {
		return $f($texte, false);
	} else {
		return false;
	}
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_titre_se_connecter($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux espaces, quote, double quote, underscores, tirets, points
	$tableau = [' ', "'", '"', '_', '-', '.', '[', ']'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_nom_serveur($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux espaces, quote, double quote, underscores, tirets, points
	$tableau = [' ', "'", '"', '_', '-', '.'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_nom_serveur_spip($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux espaces, quote, double quote, underscores, tirets, points
	$tableau = [' ', "'", '"', '_', '-', '.'];

	return cioidc_filtrer_nom_serveur($texte, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_url_serveur($url = '', $verif_only = false) {
	$safe_url = '';
	$verif = false;

	// Enlever les caracteres illegaux
	$safe_url = filter_var($url, FILTER_SANITIZE_URL);

	if ($safe_url == $url) {
		// Verifier l'url
		if (!filter_var($safe_url, FILTER_VALIDATE_URL) === false) {
			$verif = true;
		}
	}

	if ($verif_only) {
		return $verif;
	}

	return $safe_url;
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_authorization_endpoint($url = '', $verif_only = false) {
	return cioidc_filtrer_url_serveur($url, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_token_endpoint($url = '', $verif_only = false) {
	return cioidc_filtrer_url_serveur($url, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_userinfo_endpoint($url = '', $verif_only = false) {
	return cioidc_filtrer_url_serveur($url, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_end_session_endpoint($url = '', $verif_only = false) {
	return cioidc_filtrer_url_serveur($url, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $url
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_jwks_uri($url = '', $verif_only = false) {
	return cioidc_filtrer_url_serveur($url, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_token_endpoint_auth_methods_supported($texte = '', $verif_only = false) {
	$texte = trim($texte);
	if (substr($texte,0,1)!='[') {
		return false;
	}
	if (substr($texte,-1)!=']') {
		return false;
	}
	
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores, quote, double quote, virgule, crochets
	$tableau = [',', '_', "'", '"', '[', ']', ' '];
	$tscs = str_replace($tableau, '', $texte);

	return ctype_alnum($tscs);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_client_nom($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores, tirets, points
	$tableau = ['_', '-', '.'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_client_secret($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores, tirets, points
	$tableau = ['_', '-', '.'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_uid_champ_spip($texte, $verif_only = false) {
	return in_array($texte, ['email', 'login']);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_uid_claim($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores
	$tableau = ['_'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_scopes($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores, tirets, virgule
	$tableau = ['_', '-', ','];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_acr($texte, $verif_only = false) {
	// interdire les caracteres dangereux
	// limiter à a-zA-Z0-9 et aux underscores
	$tableau = ['_'];

	return cioidc_filtrer_caracteres($texte, $tableau, $verif_only);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_serveur_sans_userinfo($texte, $verif_only = false) {
	return in_array($texte, ['oui', 'non']);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_http_proxy($texte, $verif_only = false) {
	return in_array($texte, ['oui', 'non']);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_redirect_url_avec_pi($texte, $verif_only = false) {
	return in_array($texte, ['oui', 'non']);
}

/**
 * Fonction de filtrage
 *
 * @param string $texte
 * @param bool $verif_only
 * @return bool|string
 */
function cioidc_filtrer_creer_auteur($texte, $verif_only = false) {
	return in_array($texte, ['0minirezo','1comite', '6forum']);
}

/**
 * Fonction qui teste si le texte est de type a-zA-Z0-9
 * une fois que l'on a retiré les caractères spéciaux autorisés
 *
 * @param string $texte
 * @param array<string> $tableau (tableau des caractères spéciaux autorisés)
 * @param bool $verif_only
 * @return bool|string (bool si verif_only ou bien texte sans caractères spéciaux, autres que ceux autorisés)
 */
function cioidc_filtrer_caracteres($texte, $tableau = [], $verif_only = false) {
	$safe = '';
	$verif = true;

	$t = trim($texte);
	$t = filtrer_entites($t);

	if ($t && is_array($tableau)) {
		// supprimer les espaces
		$t = trim(str_replace(' ', '', $t));

		// supprimer les accents
		include_spip('inc/charsets');
		$tsa = translitteration($t);

		$tscs = str_replace($tableau, '', $tsa);
		if (!ctype_alnum($tscs)) {
			$verif = false;
			// passer le cas echeant en iso pour avoir la meme longueur avec ou sans accent en utf-8
			if ($GLOBALS['meta']['charset'] != 'iso-8859-1') {
				$tiso = iconv(strtoupper($GLOBALS['meta']['charset']), 'ISO-8859-1', $t);
			} else {
				$tiso = $t;
			}

			// enlever les caracteres speciaux
			$longueur = strlen($tsa);
			for ($i = 0; $i < $longueur; $i++) {
				if (ctype_alnum($tsa[$i]) || in_array($tsa[$i], $tableau)) {
					$safe .= $tiso[$i];
				} else {
					$safe .= ' ';
				}
			}

			// repasser le cas echeant dans le charset du site
			if ($GLOBALS['meta']['charset'] != 'iso-8859-1') {
				$safe = iconv('ISO-8859-1', strtoupper($GLOBALS['meta']['charset']), $safe);
			}
		} else {
			$safe = $t;
		}
	}

	// supprimer les espaces
	if (!$verif_only) {
		$safe = trim(str_replace(' ', '', $safe));
	}

	if ($verif_only) {
		return $verif;
	}

	return $safe;
}

/**
 * Tableau des en-têtes HTTP à lire pour trouver le host
 *
 * @return array<string>
 */
function cioidc_lire_host_ordre() {
	static $done = false;
	static $tableau = [];

	if (!$done) {
		$done = true;

		// parametrage par fichier (ou par constante dans un fichier d'options)
		if (defined('_CIOIDC_HOST_ORDRE')) {
			$tableau = _CIOIDC_HOST_ORDRE;
		} else {
			// ordre de recherche par defaut
			$tableau = ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_SERVER', 'HTTP_HOST', 'SERVER_NAME'];
		}
	}

	return $tableau;
}

/**
 * Lecture du mode d'authentification du plugin
 *
 * @return string
 */
function cioidc_mode_auth() {
	$return = 'non';

	// lire la configuration du plugin
	$tableau_config = cioidc_lire_meta();

	if (isset($tableau_config['mode_auth']) && $tableau_config['mode_auth']) {
		$return = $tableau_config['mode_auth'];
	}

	return $return;
}

/**
 * Lecture du titre du choix entre plusieurs serveurs
 *
 * @return string
 */
function cioidc_titre_se_connecter() {
	$return = '';

	// lire la configuration du plugin
	$tableau_config = cioidc_lire_meta();

	if (isset($tableau_config['titre_se_connecter']) && $tableau_config['titre_se_connecter']) {
		$return = $tableau_config['titre_se_connecter'];
		if (strpos($return,'[') !== false) {
			include_spip('inc/texte');
			$nom_site_spip = textebrut(typo($GLOBALS['meta']['nom_site']));
			if (!$nom_site_spip) {
				$nom_site_spip = _T('info_mon_site_spip');
			}
			$return = str_replace('[nom_site]', $nom_site_spip, $return);
		}
	}

	return $return;
}

/**
 * Lecture de l'intitulé de l'authentification avec SPIP
 *
 * @return string
 */
function cioidc_nom_serveur_spip() {
	$return = '';

	// lire la configuration du plugin
	$tableau_config = cioidc_lire_meta();

	if (isset($tableau_config['nom_serveur_spip']) && $tableau_config['nom_serveur_spip']) {
		$return = $tableau_config['nom_serveur_spip'];
	}

	return $return;
}

/**
 * Configuration du serveur OIDC
 *
 * @param int $id_serveur id du serveur OIDC le cas echeant
 * @return array<mixed>
 */
function cioidc_configuration_serveur_oidc($id_serveur = 0) {
	$return = [];

	$tableau_config = cioidc_lire_meta($id_serveur, true);

	$config_complete = true;
	$tableau_champs = cioidc_tableau_des_champs();
	$tableau_champs_obligatoires = cioidc_tableau_des_champs_obligatoires();
	foreach ($tableau_champs_obligatoires as $champ) {
		if (!isset($tableau_config[$champ]) || !$tableau_config[$champ]) {
			$config_complete = false;
			break;
		}
	}

	if (!$config_complete) {
		return [];
	}

	foreach ($tableau_champs as $champ) {
		if (isset($tableau_config[$champ])) {
			$return[$champ] = $tableau_config[$champ];
		}
	}

	// Cas particulier de l'URL du serveur OpenID Connect
	$return['url_serveur'] = cioidc_url_serveur_oidc($id_serveur, false, true);

	// Cas particulier de uid_champ_spip
	if (!isset($return['uid_champ_spip']) || !$return['uid_champ_spip']) {
		$return['uid_champ_spip'] = 'email';
	}

	// Cas particulier de uid_claim
	if (!isset($return['uid_claim']) || !$return['uid_claim']) {
		$return['uid_claim'] = 'email';
	}

	// Cas particulier des scopes
	if (isset($return['scopes']) && $return['scopes']) {
		$tableau_scopes_cible = [];
		$tableau_scopes = explode(',', $return['scopes']);

		foreach ($tableau_scopes as $scope) {
			$scope = trim($scope);
			$scope = str_replace(["'", '"'], '', $scope);
			if ($scope != 'openid') {
				$tableau_scopes_cible[] = $scope;
			}
		}

		if ($tableau_scopes_cible) {
			$return['scopes'] = $tableau_scopes_cible;
		}
	}

	return $return;
}

/**
 * Le serveur OIDC est-il agentconnect ?
 *
 * @param string $cioidc_url_serveur url du serveur OIDC
 * @return boolean
 */
function cioidc_test_agentconnect($cioidc_url_serveur = '') {
	$return = false;

	if ($cioidc_url_serveur) {
		$host = parse_url($cioidc_url_serveur, PHP_URL_HOST);
		
		if ($host){
			$host = trim($host);
		
			$liste_blanche_fin_de_host = [
				'.agentconnect.rie.gouv.fr',
				'.agentconnect.gouv.fr',
				'.dev-agentconnect.fr'
				];

			$longueur_host = strlen($host);
			$longueur_fin_de_host = 0;

			if (substr($host, -3) == '.fr') {
				foreach ($liste_blanche_fin_de_host as $fin_de_host) {
					$longueur_fin_de_host = strlen($fin_de_host);

					if ($longueur_host > $longueur_fin_de_host) {
						if (substr($host, -$longueur_fin_de_host) == $fin_de_host){
							$return = true;
							break;
						}
					}
				}
			}
		}
	}
	return $return;
}

/**
 * Le serveur OIDC est-il proconnect ?
 *
 * @param string $cioidc_url_serveur url du serveur OIDC
 * @return boolean
 */
function cioidc_test_proconnect($cioidc_url_serveur = '') {
	$return = false;

	if ($cioidc_url_serveur) {
		$host = parse_url($cioidc_url_serveur, PHP_URL_HOST);
		
		if ($host){
			$host = trim($host);
		
			$liste_blanche_fin_de_host = [
				'.proconnect.rie.gouv.fr',
				'.proconnect.gouv.fr',
				'.dev-proconnect.fr'
				];

			$longueur_host = strlen($host);
			$longueur_fin_de_host = 0;

			if (substr($host, -3) == '.fr') {
				foreach ($liste_blanche_fin_de_host as $fin_de_host) {
					$longueur_fin_de_host = strlen($fin_de_host);

					if ($longueur_host > $longueur_fin_de_host) {
						if (substr($host, -$longueur_fin_de_host) == $fin_de_host){
							$return = true;
							break;
						}
					}
				}
			}
		}
	}
	return $return;
}

/**
 * Le serveur OIDC est-il franceconnect ?
 *
 * @param string $cioidc_url_serveur url du serveur OIDC
 * @return boolean
 */
function cioidc_test_franceconnect($cioidc_url_serveur = '') {
	$return = false;

	if ($cioidc_url_serveur) {
		$host = parse_url($cioidc_url_serveur, PHP_URL_HOST);
		
		if ($host){
			$host = trim($host);
		
			$liste_blanche_fin_de_host = [
			'.franceconnect.gouv.fr',
			'.dev-franceconnect.fr'
				];

			$longueur_host = strlen($host);
			$longueur_fin_de_host = 0;

			if (substr($host, -3) == '.fr') {
				foreach ($liste_blanche_fin_de_host as $fin_de_host) {
					$longueur_fin_de_host = strlen($fin_de_host);

					if ($longueur_host > $longueur_fin_de_host) {
						if (substr($host, -$longueur_fin_de_host) == $fin_de_host){
							$return = true;
							break;
						}
					}
				}
			}
		}
	}
	return $return;
}

/**
 * Donne l'URL de base d'un lien vers "soi-meme"
 * sans les trucs inutiles et sans le paramètre cioidc
 *
 * @return string (URL vers soi-même)
 * */
function cioidc_self() {
	$return = '';

	$uri1 = str_replace('&amp;', '&', self());
	do {
		$uri = $uri1;
		$uri1 = preg_replace(
			',([?&])(cioidc)=[^&]*(&|$),i',
			'\1',
			$uri
		);
	} while ($uri <> $uri1);

	$return = preg_replace(',[?&]$,', '', $uri1);

	$return = str_replace('&', '&amp;', $return);

	return $return;
}

/**
 * Enlever le protocole (http ou https) d'une URL
 *
 * @param string $texte (URL)
 * @return string (URL sans protocole)
 * */
function cioidc_enlever_protocole($texte) {
	if (!empty($texte)) {
		if (strtolower(substr($texte, 0, 7)) == 'http://') {
			$texte = substr($texte, 7);
		} elseif (strtolower(substr($texte, 0, 8)) == 'https://') {
			$texte = substr($texte, 8);
		}
	}
	return $texte;
}

/**
 * Redirect URI (à renseigner dans le serveur OpenID Connect)
 *
 * @return string (URL de redirection)
 * */
function cioidc_redirect_url($redirect_url_avec_pi = '') {
    
	if ($redirect_url_avec_pi == 'non') {
		$url = cioidc_url_de_base() . 'cioidc.php';
	} else {
		$url = cioidc_url_de_base() . 'spip.php?action=login_cioidc';
	}
	
	$url = pipeline('cioidc_redirect_uri', ['args' => [], 'data' => $url]);

	return $url;
}

/**
 * Calcule l'url de base du site
 *
 * @param int|boo|array $profondeur
 *    - si non renseignée : retourne l'url pour la profondeur $GLOBALS['profondeur_url']
 *    - si int : indique que l'on veut l'url pour la profondeur indiquée
 *    - si bool : retourne le tableau static complet
 *    - si array : réinitialise le tableau static complet avec la valeur fournie
 * @return string|array
 */
function cioidc_url_de_base($profondeur = null) {

	static $url = [];
	if (is_array($profondeur)) {
		return $url = $profondeur;
	}
	if ($profondeur === false) {
		return $url;
	}

	if (is_null($profondeur)) {
		$profondeur = $GLOBALS['profondeur_url'] ?? (_DIR_RESTREINT ? 0 : 1);
	}

	if (isset($url[$profondeur])) {
		return $url[$profondeur];
	}


	// Protocole
	$http = 'http';
	if (cioidc_is_https()) {
		$http = 'https';
	}

	// Host
	$ci_http_header_are_not_secure = false;
	if (defined('_CIOIDC_HTTP_HEADER_ARE_NOT_SECURE') && _CIOIDC_HTTP_HEADER_ARE_NOT_SECURE == 'oui') {
		$ci_http_header_are_not_secure = true;
	}

	if ($ci_http_header_are_not_secure && isset($GLOBALS['meta']['adresse_site'])) {
		// Pour éviter une modification malveillante du host.
		// Inconvénients de cette approche : la méta (adresse_site)
		// peut être fausse (sites avec plusieurs noms d’hôtes, déplacements, erreurs)
		$ci_url = $GLOBALS['meta']['adresse_site'];
	} else {
		// If your PHP's HTTP header input X-Forwarded-Host, X-Forwarded-Server, Host,
		//  X-Forwarded-Proto, X-Forwarded-Protocol are sanitized before reaching PHP
		//
		// Pour memoire, une fois l'utilisateur authentifié
		// le plugin vérifie si cet utilisateur a un compte dans SPIP
		$ci_url = cioidc_url_host();
	}

	// Filtrer $host pour proteger d'attaques d'entete HTTP
	$ci_url = cioidc_enlever_protocole($ci_url);
	$host = (filter_var($ci_url, FILTER_SANITIZE_URL) ?: null);

	// Ajouter le port
	if (defined('_CIOIDC_REDIRECT_URI_AVEC_PORT') && _CIOIDC_REDIRECT_URI_AVEC_PORT == 'oui') {
		if (strpos($host, ':') === false) {
			if (empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
				$port = $_SERVER['SERVER_PORT'];
			} else {
				$ports = explode(',', $_SERVER['HTTP_X_FORWARDED_PORT']);
				$port = $ports[0];
			}

			$host .= ":$port";
		}
	}

	if (!$GLOBALS['REQUEST_URI']) {
		if (isset($_SERVER['REQUEST_URI'])) {
			$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		} else {
			$GLOBALS['REQUEST_URI'] = (php_sapi_name() !== 'cli') ? $_SERVER['PHP_SELF'] : '';
			if (
					!empty($_SERVER['QUERY_STRING'])
					&& !strpos($_SERVER['REQUEST_URI'], '?')
			) {
				$GLOBALS['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}

	// Et nettoyer l'url
	$GLOBALS['REQUEST_URI'] = (filter_var($GLOBALS['REQUEST_URI'], FILTER_SANITIZE_URL) ?: '');

	$url[$profondeur] = url_de_($http, $host, $GLOBALS['REQUEST_URI'], $profondeur);

	return $url[$profondeur];
}

/**
 * Compatibilite avec les anciennes adresses email
 *
 * @return array
 * */
function cioidc_mail_compatible() {

	// compatibilite par defaut
	$cioidc_mail_compatible = ['equipement.gouv.fr' => 'developpement-durable.gouv.fr'];

	// compatibilite figurant dans le parametrage par fichier
	// ou par constante dans un fichier d'options
	if (defined('_CIOIDC_MAIL_COMPATIBLE')) {
		$cioidc_constante_mail_compatible = _CIOIDC_MAIL_COMPATIBLE;
		if (is_array($cioidc_constante_mail_compatible)) {
			$cioidc_mail_compatible = $cioidc_constante_mail_compatible;
		}
	}

	return $cioidc_mail_compatible;
}

/**
 * Lecture des serveurs additionnels
 *
 * @return array
 */
function cioidc_lire_serveurs_additionnels() {
	$serveurs = [];

	$tableau_config = cioidc_lire_meta();

	if (isset($tableau_config['serveurs_additionnels'])) {
		$serveurs = $tableau_config['serveurs_additionnels'];
	}
	return $serveurs;
}

/**
 * Nombre de serveurs additionnels
 *
 * @return int
 */
function cioidc_nombre_serveurs_additionnels() {
	$nombre = 0;

	$serveurs = cioidc_lire_serveurs_additionnels();
	if (is_array($serveurs)) {
		$nombre = count(cioidc_lire_serveurs_additionnels());
	}

	return $nombre;
}

/**
 * Lecture d'un serveur additionnel
 *
 * @param int $id_serveur
 * @return array
 */
function cioidc_lire_serveur_additionnel($id_serveur) {
	$id_serveur = intval($id_serveur);
	$data = [];

	if ($id_serveur >= 1) {
		$tableau_config = cioidc_lire_meta();

		if (isset($tableau_config['serveurs_additionnels'][$id_serveur])) {
			$data = $tableau_config['serveurs_additionnels'][$id_serveur];
		}
	}

	return $data;
}

/**
 * Ecrire un serveur additionnel
 *
 * @param int $id_serveur
 * @param array $data
 */
function cioidc_ecrire_serveur_additionnel($id_serveur = 0, $data = []) {
	$id_serveur = intval($id_serveur);

	if ($id_serveur >= 1 && $data && is_array($data)) {
		include_spip('inc/meta');
		$tableau_config = cioidc_lire_meta();
		$tableau_config['serveurs_additionnels'][$id_serveur] = $data;
		ecrire_meta('cioidc', @serialize($tableau_config));
	}
}

/**
 * Supprimer un serveur additionnel
 *
 * @param int $id_serveur
 */
function cioidc_supprimer_serveur_additionnel($id_serveur = 0) {
	$id_serveur = intval($id_serveur);

	if ($id_serveur >= 1) {
		include_spip('inc/meta');
		$tableau_config = cioidc_lire_meta();

		if (isset($tableau_config['serveurs_additionnels'][$id_serveur])) {
			unset($tableau_config['serveurs_additionnels'][$id_serveur]);
			ecrire_meta('cioidc', @serialize($tableau_config));
		}
	}
}

/**
 * Tableau des uid_champ_spip utilisé pour les différents serveurs oidc
 *
 * @return array
 */
function cioidc_tableau_uid_champ_spip() {
	$tableau_uid_champ_spip = [];

	$tableau_config = cioidc_lire_meta(0, false, true);
	if (isset($tableau_config['uid_champ_spip']) && $tableau_config['uid_champ_spip']) {
		$tableau_uid_champ_spip[] = $tableau_config['uid_champ_spip'];
	}

	$nombre_serveurs_additionnels = cioidc_nombre_serveurs_additionnels();
	if ($nombre_serveurs_additionnels >= 1) {
		for ($id_serveur = 1; $id_serveur <= $nombre_serveurs_additionnels; $id_serveur++) {
			$valeurs = cioidc_lire_serveur_additionnel($id_serveur);
			if (isset($valeurs['uid_champ_spip']) && $valeurs['uid_champ_spip']) {
				$tableau_uid_champ_spip[] = $valeurs['uid_champ_spip'];
			}
		}
	}

	return $tableau_uid_champ_spip;
}

/**
 * Tableau de la Well Known Config
 * (pour éviter d'interroger à chaque fois le serveur d'authentification)
 *
 * @param int $id_serveur
 * @return array
 */
function cioidc_well_known_config($id_serveur = 0) {
	$well_known_config = [];
	
	$champs = [
		'authorization_endpoint',
		'token_endpoint',
		'userinfo_endpoint',
		'end_session_endpoint',
		'jwks_uri'
	];

	$config = cioidc_lire_meta($id_serveur);

	foreach ($champs as $champ) {
		if (isset($config[$champ])) {
			$well_known_config[$champ] = $config[$champ];
		}
	}
	
	if ($well_known_config){
		$well_known_config = ['issuer' => $config['url_serveur']];
	}

	return $well_known_config;
}

/**
 * Génère le code HTML d'un lien
 * pour le choix (par l'utilisateur) entre différents serveurs
 * (pour éviter d'interroger à chaque fois le serveur d'authentification)
 *
 * @param string $lien
 * @param string $url_serveur
 * @param string $intitule
 * @param string $class
 * @return string
 */
function cioidc_html_lien_serveur($lien = '', $url_serveur = '', $intitule = '', $class = '') {
	$cioidc_test_agentconnect = cioidc_test_agentconnect($url_serveur);
	$cioidc_test_proconnect = cioidc_test_proconnect($url_serveur);
	$cioidc_test_franceconnect = cioidc_test_franceconnect($url_serveur);

	if (version_compare($GLOBALS['spip_version_branche'], '4.2.13') >=0) {
		include_spip('inc/filtres');
		$lien = attribut_url($lien);
	}
	
	if ($cioidc_test_agentconnect) {
		$return = cioidc_bouton_agent_connect($lien);
	} elseif ($cioidc_test_proconnect) {
		$return = cioidc_bouton_pro_connect($lien);
	} elseif ($cioidc_test_franceconnect) {
		$return = cioidc_bouton_france_connect($lien);
	} else {
		$return = '<a href="' . $lien . '" class="' . $class . '">' . $intitule . '</a>';
	}

	return $return;
}

/**
 * Génère le code HTML du bouton agent connect
 *
 * @param string $lien
 * @return string
 */
function cioidc_bouton_agent_connect($lien = '') {
	$return = '
<div class="cioidc-ac-group">
<a class="cioidc-ac" href="' . $lien . '">
<span class="cioidc-ac__login">' . _T('cioidc:identifier_avec') . '</span>
<span class="cioidc-ac__brand">' . _T('cioidc:agentconnect') . '</span>
</a>
<p>
<a href="https://agentconnect.gouv.fr/" class="cioidc-ac__info" target="_blank" rel="noopener noreferrer" title="' . _T('cioidc:qu_est_agentconnect') . ' ? - ' . _T('cioidc:nouvelle_fenetre') . '">' . _T('cioidc:qu_est_agentconnect') . '&nbsp;?</a>
</p>
</div>

';
	return $return;
}

/**
 * Génère le code HTML du bouton pro connect
 *
 * @param string $lien
 * @return string
 */
function cioidc_bouton_pro_connect($lien = '') {
	$return = '
<div class="cioidc-pc-group">
<a class="cioidc-pc" href="' . $lien . '">
<span class="cioidc-pc__login">' . _T('cioidc:identifier_avec') . '</span>
<span class="cioidc-pc__brand">' . _T('cioidc:proconnect') . '</span>
</a>
<p>
<a href="https://proconnect.gouv.fr/" class="cioidc-pc__info" target="_blank" rel="noopener noreferrer" title="' . _T('cioidc:qu_est_proconnect') . ' ? - ' . _T('cioidc:nouvelle_fenetre') . '">' . _T('cioidc:qu_est_proconnect') . '&nbsp;?</a>
</p>
</div>

';
	return $return;
}

/**
 * Génère le code HTML du bouton france connect
 *
 * @param string $lien
 * @return string
 */
function cioidc_bouton_france_connect($lien = '') {
	$return = '
<div class="cioidc-ac-group">
<a class="cioidc-ac" href="' . $lien . '">
<span class="cioidc-ac__login">' . _T('cioidc:identifier_avec') . '</span>
<span class="cioidc-ac__brand">' . _T('cioidc:franceconnect') . '</span>
</a>
<p>
<a href="https://franceconnect.gouv.fr/" class="cioidc-ac__info" target="_blank" rel="noopener noreferrer" title="' . _T('cioidc:qu_est_franceconnect') . ' ? - ' . _T('cioidc:nouvelle_fenetre') . '">' . _T('cioidc:qu_est_franceconnect') . '&nbsp;?</a>
</p>
</div>

';
	return $return;
}

/**
 * UID claim additionnel
 *
 * @return array
 * */
function cioidc_uid_claim_additionnel() {
	$return = '';

	// UID claim additionnel figurant dans le parametrage par fichier
	// ou par constante dans un fichier d'options
	if (defined('_CIOIDC_UID_CLAIM_ADDITIONNEL') 
		&& _CIOIDC_UID_CLAIM_ADDITIONNEL 
		&& cioidc_filtrer_uid_claim(_CIOIDC_UID_CLAIM_ADDITIONNEL, true)
	) {
		$return = _CIOIDC_UID_CLAIM_ADDITIONNEL;
	}

	return $return;
}
