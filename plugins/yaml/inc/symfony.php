<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

require_once _DIR_PLUGIN_YAML . 'vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Encode une structure de données PHP en une chaîne YAML.
 * Utilise pour cela la librairie symfony/yaml (branche v4) qui est la plus récente.
 *
 * @param mixed $structure
 *        Structure PHP, tableau, chaine... à convertir en YAML.
 * @param array $options
 *        Tableau associatif des options du dump. Cette librairie accepte:
 *        - 'inline' : niveau à partir duquel la présentation du YAML devient inline, 3 par défaut.
 *        - 'indent' : nombre d'espaces pour chaque niveau d'indentation, 2 par défaut.
 *        - 'flags'  : combinaison des flags DUMP_* supportés par la fonction (voir documentation).
 *
 * @internal
 * @return string
 *        Chaîne YAML construite.
 */
function symfony_yaml_encode($structure, $options = []) {

	// Traitement des options du dump
	if (empty($options['inline']) or (isset($options['inline']) and !is_int($options['inline']))) {
		$options['inline'] = 3;
	}
	if (empty($options['indent']) or (isset($options['indent']) and !is_int($options['indent']))) {
		$options['indent'] = 2;
	}
	if (!isset($options['flags']) or (isset($options['flags']) and !is_int($options['flags']))) {
		$options['flags'] = 0;
	}

	$dump = Yaml::dump($structure, $options['inline'], $options['indent'], $options['flags']);
	return $dump;
}


/**
 * Décode une chaîne YAML en une structure de données PHP adaptée.
 * Utilise pour cela la librairie symfony/yaml (branche v4) qui est la plus récente.
 *
 * @param string $input
 *        La chaîne YAML à décoder.
 * 
 * @param array|bool $options
 *        Tableau associatif des options du parsing. Cette librairie accepte:
 *        - 'include'    : si vrai indique qu'il faut traiter les inclusions YAML. Cela implique de forcer le flag
 *                         PARSE_CUSTOM_TAGS
 *        - 'flags'      : combinaison des flags PARSE_* supportés par la fonction (voir documentation).
 *        - 'show_error' : indicateur d'affichage des erreurs de parsing, false par défaut.
 *        Un booléen est encore accepté : c'est l'ancienne signature `$show_error`, antérieure à 2018.
 * @param string $erreur
 *        Passé par référence : reçoit le message d'erreur d'analyse, ou une chaine vide si tout s'est bien
 *        passé. C'est le seul moyen fiable de détecter un échec, la valeur de retour ne pouvant pas le
 *        signaler — un document YAML valide peut valoir `false`.
 *
 * @internal
 * @return bool|mixed
 *        Structure PHP produite par le parsing de la chaîne YAML, `false` en cas d'erreur d'analyse.
 */
function symfony_yaml_decode($input, $options = [], &$erreur = null) {

	$erreur = '';
	$parsed = false;

	// Compatibilité ascendante : jusqu'en 2018 le second argument était `$show_error`, un booléen.
	// L'écriture subsiste dans la zone (champs_extras_interface/formulaires/importer_champs_extras.php).
	if (is_bool($options)) {
		$options = ['show_error' => $options];
	}

	// Traitement des options du parsing.
	$flags = 0;
	if (isset($options['flags']) and is_int($options['flags'])) {
		$flags = $options['flags'];
	}

	try {
		$parsed = Yaml::parse($input, $flags);
	} catch (ParseException $exception) {
		$erreur = $exception->getMessage();
		if (!empty($options['show_error'])) {
			printf('Unable to parse the YAML string: %s', $erreur);
		}

		// Pour compenser l'absence par défaut d'erreurs, on loge l'erreur dans les logs SPIP.
		spip_log("Erreur d'analyse YAML : " . $erreur, 'yaml' . _LOG_ERREUR);
	}

	return $parsed;
}
