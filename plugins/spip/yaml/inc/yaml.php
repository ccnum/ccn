<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Encode une structure de données PHP en une chaine YAML.
 *
 * La chaine est rendue, jamais écrite : la ranger quelque part appartient à l'appelant. La fonction
 * n'échoue pas — une valeur que la librairie ne sait pas représenter, une ressource par exemple, est
 * rendue sous la forme `null`.
 *
 * @api
 *
 * @param mixed $structure
 *        Structure PHP, tableau, chaine... à convertir en YAML.
 * @param array $options
 *        Tableau associatif d'options standard ou spécifique à une librairie donnée.
 *
 * @return string
 *        Chaîne YAML construite, prête pour être éventuellement écrite dans un fichier.
 */
function yaml_encode($structure, $options = []) {
	require_once __DIR__ . '/symfony.php';

	return symfony_yaml_encode($structure, $options);
}


/**
 * Décode une chaine YAML en une structure de données PHP.
 *
 * Un document YAML pouvant décrire aussi bien une map qu'une liste ou un scalaire, le type rendu est
 * quelconque. Aucune exception ne sort : une erreur d'analyse rend `false` et est journalisée dans le
 * canal `yaml`. Comme `false` est aussi un document valide, **c'est `$erreur` qui décrit l'échec**, pas la
 * valeur de retour.
 *
 * @api
 *
 * @param string $input
 *        La chaîne YAML à décoder.
 * @param array $options
 *        Tableau associatif des options du parsing.
 *        - 'show_error' : indicateur d'affichage des erreurs de parsing, false par défaut.
 * @param string $erreur
 *        Passé par référence : message d'erreur d'analyse, chaine vide si tout va bien.
 *
 * @return mixed
 *        La structure décodée, de n'importe quel type ; `false` en cas d'erreur d'analyse.
 */
function yaml_decode($input, $options = [], &$erreur = null) {
	require_once __DIR__ . '/symfony.php';

	return symfony_yaml_decode($input, $options, $erreur);
}

/**
 * Décode une chaine YAML pour la boucle `DATA` de SPIP, qui n'accepte qu'un tableau.
 *
 * C'est l'implémentation unique derrière les deux points d'entrée que le noyau peut retenir —
 * `inc_yaml_to_array()` dans `yaml_fonctions.php` et `inc_yaml_to_array_dist()` dans
 * `inc/yaml_to_array.php` —, lesquels ne sont que des adaptateurs. Elle est ici pour qu'ils ne puissent
 * pas diverger : c'est exactement ce qui était arrivé, l'un rendant la valeur brute et l'autre `null`.
 *
 * Le contrat vient de l'appelant : l'itérateur `DATA` ignore ce qui n'est pas un tableau, et le noyau
 * applique la même règle à ses propres formats — voir `inc_json_to_array_dist()`. Un document YAML
 * pouvant décrire un scalaire, tout ce qui n'est pas un tableau est donc ramené à `[]`.
 *
 * @api
 * @param string $input
 * @return array
 */
function yaml_to_array($input) {
	$yaml = yaml_decode($input);
	if (is_object($yaml)) {
		$yaml = (array) $yaml;
	}

	return is_array($yaml) ? $yaml : [];
}

/** 
 * Decode un fichier en utilisant yaml_decode
 * 
 * Options
 * - include: true pour gérer les inclusions de la forme `'inclure:chemin/fichier.yaml'`
 * 
 * @api
 * @param string|false $fichier
 *        Chemin déjà résolu. `false` est accepté sans dommage : c'est ce que rend `find_in_path()`
 *        quand le fichier est absent.
 * @param array{include?: bool, ...<string,mixed>} $options
 * @param string $erreur
 *        Passé par référence : message d'échec, chaine vide si tout s'est bien passé.
 * @return mixed
 *        La structure décodée ; `[]` faute de document lisible, `false` sur un YAML malformé.
 */
function yaml_decode_file($fichier, $options = [], &$erreur = null) {

	$erreur = '';
	$retour = [];

	// Traitement des options
	if (empty($options['include'])) {
		$options['include'] = false;
	}

	// Lecture du fichier YAML.
	// - le test sur $fichier est indispensable : find_in_path() rend false quand le fichier est absent,
	//   et lire_fichier(false) appelle fopen('') qui lève une ValueError en PHP 8.
	// - le test sur '' plutôt que sur la vérité de $yaml : un fichier ne contenant que `0` est un YAML
	//   valide, que `if ($yaml)` écartait sans le décoder. Un fichier vide n'est pas une erreur.
	if (!$fichier) {
		$erreur = 'Chemin de fichier YAML vide';
	} elseif (!lire_fichier($fichier, $yaml)) {
		$erreur = "Fichier YAML introuvable ou illisible : {$fichier}";
	} elseif ($yaml !== '') {
		// Décodage du contenu YAML en structure de données PHP.
		$retour = yaml_decode($yaml, $options, $erreur);
		if ($options['include']) {
			$retour = yaml_decode_inclusions($retour, $options, $erreur);
		}
	}

	return $retour;
}


/**
 * Charge les inclusions de YAML dans un tableau
 *
 * Les inclusions sont indiquees dans le tableau via la valeur 'inclure:rep/fichier.yaml' ou rep indique le chemin relatif.
 * On passe donc par find_in_path() pour trouver le fichier
 *
 * @api
 * @param array $tableau
 * @param array $options
 * @param string $erreur
 *        Passé par référence : message d'échec, chaine vide si tout s'est bien passé. En cas d'inclusions
 *        multiples, la première erreur rencontrée est conservée.
 * @return mixed
 */
function yaml_charger_inclusions($tableau, $options = [], &$erreur = null) {
	$erreur = '';
	$options['include'] = true;
	return yaml_decode_inclusions($tableau, $options, $erreur);
}


if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool {
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }
}


/**
 * Charge les inclusions `inclure:fichier.yaml` d’un décodage YAML
 *
 * Les inclusions sont indiquees dans le tableau via la valeur 'inclure:rep/fichier.yaml' ou rep indique le chemin relatif.
 * On passe donc par find_in_path() pour trouver le fichier
 * 
 * Plusieurs cas… 
 * 
 * Soit
 * ```yaml
 * # a.yaml
 * - 'a.1'
 * - 'a.2'
 * ```
 * 
 * Sur une liste, le contenu inclu remplace l’inclusion
 * ```yaml
 * - 'avant'
 * - 'inclure:a.yaml'
 * - 'inclure:b.yaml'
 * - 'apres'
 * ```
 * 
 * Sortie équivalente à
 * ```yaml
 * - 'avant'
 * - 'a.1'
 * - 'a.2'
 * - 'b.1'
 * - 'b.2'
 * - 'apres'
 * ```
 * 
 * Avec des cles nommées, le contenu est intégré dans la clé
 * ```yaml
 * avant: 'avant'
 * a: 'inclure:a.yaml'
 * b: 'inclure:b.yaml'
 * apres: 'apres'
 * ```
 * 
 * Sortie équivalente à 
 * ```
 * avant: 'avant'
 * a: 
 *   - 'a.1'
 *   - 'a.2;
 * b:
 *   - 'b.1'
 *   - 'b.2'
 * apres: 'apres'
 * ```
 * 
 * @internal
 * @param mixed $parsed
 * @param array $options
 * @param string $erreur
 *        Passé par référence : la première erreur rencontrée, jamais écrasée par une inclusion suivante.
 * @return mixed
 */
function yaml_decode_inclusions($parsed, $options = [], &$erreur = null) {
	if (is_array($parsed)) {
		$res = [];
		foreach ($parsed as $key => $value) {
			$file = yaml_is_to_include_file($value);
			if ('' !== $file) {
				// La première erreur rencontrée est conservée : une inclusion en échec ne doit pas
				// effacer l'erreur du document parent.
				$content = yaml_decode_file($file, $options, $sous_erreur);
				if ($sous_erreur and !$erreur) {
					$erreur = $sous_erreur;
				}
				if (array_is_list($parsed)) {
					$res = array_merge($res, $content);
				} else {
					$res = array_merge($res, [$key => $content]);
				}
				continue;
			}

			$content = yaml_decode_inclusions($value, $options, $erreur);
			if (array_is_list($parsed)) {
				$res = array_merge($res, [$content]);
			} else {
				$res[$key] = $content;
			}
		}
		return $res;
	}

	$file = yaml_is_to_include_file($parsed);
	if ('' !== $file) {
		$retour = yaml_decode_file($file, $options, $sous_erreur);
		if ($sous_erreur and !$erreur) {
			$erreur = $sous_erreur;
		}
		return $retour;
	}

	return $parsed;
}

/**
 * Retourne le chemin du fichier, si c’est une inclusion à faire
 * 
 * @internal
 * @param mixed $parsed
 * @return string
 *        Chemin résolu par `find_in_path()`, ou chaine vide si la valeur n'est pas une inclusion ou si le
 *        fichier désigné est introuvable.
 */
function yaml_is_to_include_file($parsed) {
	// if (is_string($value) && str_starts_with($value, 'inclure:') && str_ends_with($value, '.yaml')) {
	if (is_string($parsed) && substr($parsed, 0, 8) == 'inclure:' && substr($parsed, -5) == '.yaml') {
		return find_in_path(substr($parsed, 8)) ?: '';
	}
	return '';
}