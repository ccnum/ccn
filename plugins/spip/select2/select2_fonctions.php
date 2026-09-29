<?php


if (!function_exists('balise_URL_API_dist')) {
	/**
	 * Compile la balise `#URL_API` qui retourne une URL de type « .api »
	 *
	 * - `#URL_API{script, path='', args=''}`
	 * - `#URL_API*{script, path='', args=''} retourne l'URL sans convertir les `&` en `&amp;`
	 * 
	 * @usage
	 * - `#URL_API{toto}` génère l'url pour l’api `toto.api`
	 * - `#URL_API{toto.api}` génère l'url pour l’api `toto.api`
	 * - `#URL_API{toto, list}` génère l'url pour l’api `toto.api/list`
	 * - `#URL_API{toto, list/article}` génère l'url pour l’api `toto.api/list/article`
	 * - `#URL_API{toto, '', param=valeur}` génère l'url pour l’api `toto.api?param=valeur`
	 * - `#URL_API{toto, list/article, param=valeur}` génère l'url pour l’api `toto.api/list/article?param=valeur`
	 * 
	 * @note Deprecated usages:
	 * - `#URL_API{toto/list}` génère l'url pour l’api `toto.api/list`
	 * - `#URL_API{toto.api/list}` génère l'url pour l’api `toto.api/list`
	 * - `#URL_API{toto,param=valeur}` génère l'url pour la page `toto.api` avec des paramètres
	 * - `#URL_API{toto.api/list,param=valeur}` génère l'url pour la page `toto.api/list` avec des paramètres
	 *
	 * @balise
	 * @uses select2_generer_url_api()
	 * @example
	 *     ```
	 *     #URL_API{select2_autocomplete, demo} produit select2_autocomplete.api/demo
	 *     ```
	 *
	 * @param Champ $p
	 *     Pile au niveau de la balise
	 * @return Champ
	 *     Pile complétée par le code à générer
	 */
	function balise_URL_API_dist($p) {
		$api = interprete_argument_balise(1, $p);
		$path = interprete_argument_balise(2, $p);
		$args = interprete_argument_balise(3, $p);
		if ($path === null) {
			$path = "''";
		} 
		if ($args === null) {
			$args = "''";
		}

		$no_entities = $p->etoile ? "true" : "false";
		# $code = "generer_url_api($api, $path, $args, $no_entities)"; // SPIP 4.1+
		$code = "select2_generer_url_api($api, $path, $args, $no_entities)";
		$p->code = $code;
		$p->interdire_scripts = true;
		return $p;
	}
}


/**
 * Calcule une URL d’API SPIP.
 *
 * Une URL d’API est de la forme `truc.api` ou `truc.api/qqc/...`
 * et appelle ensuite un fichier d’action de SPIP, tel que
 * `action/api_truc.php`
 *
 * Nécessite un .htaccess (ou équivalent) actif sur le site.
 *
 * @param string $script
 *    Le nom du script d’API, avec ou sans `.api` dedans (ex: truc.api, truc)
 * @param string $path
 *    Le chemin dans l’api (ex: 'list', ou 'list/qqc')
 * @param string $args
 *    Des arguments supplémentaires, tel que `id_rubrique=3&limit=12`
 * @param bool $no_entities
 *    true pour ne pas échapper les entités.
 * @return string
 *    L’URL calculée.
 */
function select2_generer_url_api($script, $path = '', $args = '', $no_entities = false) {
	$signature = [$script, $path, $args];
	$deprecated = false;
	if (is_array($path) || false !== strpos($path, '=')) {
		$args = $path;
		$path = '';
		$deprecated = true;
	}
	if (false !== strpos($script, '/')) {
		[$script, $path] = explode('/', $script, 2);
		$deprecated = true;
	}
	if (is_array($args)) {
		foreach ($args as $k => $v) {
			$args[$k] = $k . '=' . $v;
		}
		$args = implode('&', $args);
		$deprecated = true;
	}

	if ($deprecated) {
		$param = function($p) { return $p ? trim(var_export($p, true), '\'') : ''; };
		$filter = function($args) { 
			if (empty($args[2])) { 
				unset($args[2]); 
				if (empty($args[1])) {
					unset($args[1]);
				}
			}
			return $args;
		};
		$old = $filter([ $param($signature[0]), $param($signature[1]), $param($signature[2]) ]);
		$new = $filter([ $param($script), $param($path), $param($args) ]);

		trigger_error(
			sprintf('Call to "%s{%s}" is deprecated. Use "%s{%s}" instead. See "%s"', 
				'#URL_API', 
				implode(', ', $old),
				'#URL_API',
				implode(', ', $new),
				'balise_URL_API_dist()'
			), 
			E_USER_DEPRECATED
		);
	}

	// SPIP >= 4.1
	if (function_exists('generer_url_api')) {
		return generer_url_api($script, $path, $args, $no_entities, true);
	}

	if (substr($script, -4) !== '.api') {
		$script .= '.api';
	}

	$url = _DIR_RACINE
		. $script . '/'
		. ($path ? trim($path, '/') : '')
		. ($args ? '?' . quote_amp($args) : '');

	if ($no_entities) {
		$url = str_replace('&amp;', '&', $url);
	}

	return $url;
}
