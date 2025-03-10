<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérification d'une URL
 *
 * Par défaut vérifie uniquement si un protocole de type web est présent (http ou https)
 *
 * @param string $url
 *   L'url à vérifier.
 * @param array $options
 *   - mode :
 *     - protocole_seul : vérifie la présence d'un protocole uniquement
 *     - php_filter : vérifie la présence d'un protocole puis vérifie la conformité
 *       au moyen de la fonction native de PHP → filter_var + FILTER_VALIDATE_URL + drapeaux par défaut
 *     - complet : vérifie la présence d'un protocole puis vérifie la syntaxe
 *       au moyen de tests maisons
 *   - type_protocole :
 *     - tous : tous ceux qui respectent un certain pattern
 *     - web : http ou https
 *     - mail : imap, pop3, smtp
 *     - ftp : ftp ou sftp,
 *     - exact : cf. option protocole
 *   - protocole : nom du protocole (si type_protocole = exact)
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_url_dist($url, $options = []) {
	if (!is_string($url)) {
		return _T('erreur_inconnue_generique');
	}

	// Options
	$options_defaut = [
		'mode'           => 'protocole_seul',
		'type_protocole' => 'web',
		'protocole'      => '',
	];
	$options = array_merge($options_defaut, $options);

	// Choix du mode de verification de la syntaxe des url
	if (!in_array($options['mode'] ?? '', ['protocole_seul','php_filter','complet'])) {
		$mode = 'protocole_seul';
	} else {
		$mode = $options['mode'];
	}

	// Choix du type de protocole à vérifier
	if (!in_array($options['type_protocole'] ?? '', ['tous','web','mail','ftp','webcal_souple','webcal_strict','exact'])) {
		$type_protocole = 'web';
		$protocole = '';
	} else {
		$type_protocole = $options['type_protocole'];
		$protocole = '' ;
		if ($type_protocole == 'exact' && $options['protocole']) {
			$protocole = $options['protocole'];
		}
	}

	$fonctions_disponibles = ['protocole_seul' => 'verifier_url_protocole', 'php_filter' => 'verifier_php_filter', 'complet' => 'verifier_url_complet'];
	$fonction_verif = $fonctions_disponibles[$mode];
	return $fonction_verif($url, $type_protocole, $protocole) ;
}

/**
 * Vérifier uniquement la présence d'un protocole
 *
 * @param string $url L'url à vérifier
 * @param string $type_protocole : tous, web (http ou https), mail (imap, pop3, smtp), ftp (ftp ou sftp), webcal (webcal, http, https), exact
 * @param string $protocole : nom du protocole (si type_protocole=exact)
 * @return string Retourne '' uniquement lorsque l'url est valide
 */
function verifier_url_protocole($url, $type_protocole, $protocole) {

	$urlregex = [
		'tous' => '#^([a-z0-9]*)\:\/\/.*$# i',
		'web' => '#^(https?)\:\/\/.*$# i',
		'ftp' => '#^(s?ftp)\:\/\/.*$# i',
		'mail' => '#^(pop3|smtp|imap)\:\/\/.*$# i',
		'webcal' => '#^(webcal|https?)\:\/\/.*$# i',
		'exact' => '#^(".$protocole.")\:\/\/.*$# i'
	];

	$msg_erreur = [
		'tous' => '',
		'web' => 'http://, https://',
		'ftp' => '^ftp://, sftp://',
		'mail' => 'pop3://, smtp://, imap://',
		'webcal' => 'webcal://, http://, https://',
		'exact' => $protocole . '://'
	];


	if (!preg_match($urlregex[$type_protocole], $url)) {
		if ($type_protocole == 'tous') {
			return _T('verifier:erreur_url_protocole_exact', ['url' => echapper_tags($url)]);
		} else {
			return _T('verifier:erreur_url_protocole', ['url' => echapper_tags($url),'protocole' => $msg_erreur[$type_protocole]]);
		}
	}
	return '';
}

/**
 * Vérifier la présence d'un protocole,
 * puis utilise le filtre FILTER_VALIDATE_URL de PHP pour s'assurer que l'url est complète
 *
 * @param string $url L'url à vérifier
 * @param string $type_protocole : tous, web (http ou https), mail (imap, pop3, smtp), ftp (ftp ou sftp), webcal (webcal, http, https), exact
 * @param string $protocole : nom du protocole (si type_protocole=exact)
 * @return string Retourne '' uniquement lorsque l'url est valide
 */
function verifier_php_filter($url, $type_protocole, $protocole) {
	if ($msg = verifier_url_protocole($url, $type_protocole, $protocole)) {
		return $msg;
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return _T('verifier:erreur_url', ['url' => echapper_tags($url)]);
	}
	return '';
}

/**
 * Vérifier la présence d'un protocole et de la bonne syntaxe du reste de l'url
 *
 * http://phpcentral.com/208-url-validation-in-php.html
 * <http[s]|ftp> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]
 *
 * @param string $url L'url à vérifier
 * @param string $type_protocole : web (http ou https), mail (imap, pop3, smtp), ftp (ftp ou sftp), webcal (webcal, http, https), exact
 * @param string $protocole : nom du protocole (si type_protocole=exact)
 * @return string Retourne '' uniquement lorsque l'url est valide
 */
function verifier_url_complet($url, $type_protocole, $protocole) {

	if ($msg = verifier_url_protocole($url, $type_protocole, $protocole)) {
		return $msg;
	}
	// SCHEME
	$urlregex = '#^(.*)\:\/\/';

	// USER AND PASS (optional)
	$urlregex .= '([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?';

	// HOSTNAME OR IP
	$urlregex .= '[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*'; // http://x = allowed (ex. http://localhost, http://routerlogin)
	//$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+"; // http://x.x = minimum
	//$urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}"; // http://x.xx(x) = minimum
	//use only one of the above

	// PORT (optional)
	$urlregex .= '(\:[0-9]{2,5})?';
	// PATH (optional)
	$urlregex .= '(\/([a-z0-9+\$_%,-]\.?)+)*\/?';
	// GET Query (optional)
	$urlregex .= '(\?[a-z+&\$_.-][a-z0-9;:@\&%=+\$\|_.-]*)?';
	// ANCHOR (optional)
	$urlregex .= '(\#[a-z_.-][a-z0-9+\$_.-]*)?$#i';

	if (!preg_match($urlregex, $url)) {
		return _T('verifier:erreur_url', ['url' => echapper_tags($url)]);
	}
	return '';
}
