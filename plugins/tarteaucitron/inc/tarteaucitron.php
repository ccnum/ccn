<?php

/**
 * Fonctions internes du plugin
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
/**
 * Détecter si au moins un service est actif.
 *
 * @return boolean
 */

function tarteaucitron_actif() {
	$actif = false;
	$liste_services = lire_config('tarteaucitron/services', []);

	foreach ($liste_services as $value => $params) {
		if (!empty($value)) {
			$actif = true;
		}
	}
	return $actif;
}

/**
 * Retourne la liste des services TarteAuCitron avec leur statut (actif,inactif)
 * et leur(s) éventuel(s) paramètre(s)
 *
 * @return array
 */

function tarteaucitron_liste_services() {
	$services_actifs = lire_config('tarteaucitron/services', []);
	$list_services = [];
	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);

	foreach ($parsed_json as $service => $prop) {
		$list_services[$service] = [
			'type' => $prop->{'type'},
			'statut' => (array_key_exists($service, $services_actifs)) ? 'actif' : 'inactif',
			'params' => (!empty($prop->{'params'})) ? $prop->{'params'} : [],
		];
	}

	return $list_services;
}

/**
 * Retourne la liste des types des services TarteAuCitron installés
 *
 * @return array
 */

function tarteaucitron_liste_types_actifs() {
	$list_types = [];
	$services = tarteaucitron_liste_services();

	foreach ($services as $service => $prop) {
		if ($prop['statut'] == 'actif') {
			$list_types[$prop['type']][$service] = $prop['params'];
		}
	}

	return $list_types;
}

function tarteaucitron_generer_modeles($data) {

	$dir_modeles = _DIR_PLUGIN_TARTEAUCITRON . 'modeles/';
	if (!is_dir($dir_modeles)) {
		sous_repertoire(_DIR_PLUGIN_TARTEAUCITRON, 'modeles');
	}

	foreach ($data as $categorie => $services) {

		// ❌ Exclusion des catégories "support" et "analytic"
		if (in_array(strtolower($categorie), ['support', 'analytic'])) {
			continue;
		}

		foreach ($services as $service => $infos) {

			// Récupérer le HTML brut (chaîne ou tableau)
			if (!isset($infos['code']['html'])) {
				continue;
			}
			$html_raw = $infos['code']['html'];

			if (is_array($html_raw)) {
				// Cas LinkedIn & co : tableau d'objets [{name, code}, ...] → prendre le premier 'code'
				if (isset($html_raw[0]) && is_array($html_raw[0]) && isset($html_raw[0]['code'])) {
					$html_raw = (string) $html_raw[0]['code'];
				} else {
					// Sinon, premier élément brut (chaîne)
					$first = reset($html_raw);
					$html_raw = is_array($first) && isset($first['code']) ? (string) $first['code'] : (string) $first;
				}
			} else {
				$html_raw = (string) $html_raw;
			}

			if (trim($html_raw) === '') {
				continue; // ne pas créer de modèle vide
			}

			// 🔹 1) Remplacer ###param### → #ENV{param}
			//     avec nettoyage : espace → underscore
			$html_raw = preg_replace_callback(
				'/###\s*([\w\-.:\s]+)(?:\s*\(.*?\))?\s*###/',
				function ($matches) {
					$param = trim($matches[1]);
					$param = preg_replace('/\s+/', '_', $param); // espaces → _
					return '#ENV{' . $param . '}';
				},
				$html_raw
			);

			// 🔹 2) Transformer les attributs (data-*, action, value) → [ attr="(#ENV{...})"]
			$html_raw = preg_replace(
				'/\s+(data-[\w\-:.]+|action|value)=[\'"]#ENV\{([^}]+)\}[\'"]/',
				'[ $1="(#ENV{$2})"]',
				$html_raw
			);

			// 3) Tous les attributs (data-*, id, class, etc.) contenant un #ENV, sauf style déjà traité
			$html_raw = preg_replace_callback(
				'/\s+([a-zA-Z0-9_-]+)="([^"]*#ENV\{[^}]+\}[^"]*)"/',
				function ($m) {
					$attr = $m[1]; // ex: id, data-app-id, style
					$val = $m[2]; // ex: libchat_#ENV{hash}, width:#ENV{width}px;

					// 🔹 Cas particulier : attribut style
					if ($attr === 'style') {
						$props = explode(';', $val);
						$new_props = [];
						foreach ($props as $prop) {
							$prop = trim($prop);
							if ($prop === '') {
								continue;
							}

							if (strpos($prop, '#ENV') !== false) {
								// remplacer chaque #ENV{xxx} par (#ENV{xxx})
								$prop = preg_replace('/#ENV\{([^}]+)\}/', '(#ENV{$1})', $prop);
								$new_props[] = "[$prop;]";
							} else {
								$new_props[] = $prop . ';';
							}
						}
						return ' ' . $attr . '="' . implode('', $new_props) . '"';
					}

					// 🔹 Cas général (comme avant)
					$val = preg_replace('/###([\w.-]+)\s+([\w.-]+)###/', '#ENV{$1_$2}', $val);
					$val = preg_replace('/###([\w.-]+)###/', '#ENV{$1}', $val);
					$val = preg_replace('/#ENV\{([^}]+)\}/', '(#ENV{$1})', $val);

					return ' [' . $attr . '="' . $val . '"]';
				},
				$html_raw
			);

			// 4) <script>…</script> : ne pas toucher aux attributs, seulement au contenu
			$html_raw = preg_replace_callback(
				'/(<script\b[^>]*>)(.*?)(<\/script>)/is',
				function ($matches) {
					$before = $matches[1];
					$script = $matches[2];
					$after = $matches[3];

					$parts = preg_split('/;(\s*)/', $script);
					$new_parts = [];
					foreach ($parts as $p) {
						$p = trim($p);
						if ($p === '') {
							continue;
						}
						if (strpos($p, '#ENV') !== false) {
							$p = preg_replace('/#ENV\{([^}]+)\}/', '(#ENV{$1})', $p);
							$new_parts[] = "[$p;]";
						} else {
							$new_parts[] = $p . ';';
						}
					}
					return $before . implode('', $new_parts) . $after;
				},
				$html_raw
			);

			// 5) Nettoyage final pour éviter les doublons ou crochets bizarres
			$html_raw = preg_replace('/\[\s*\[+/', '[ ', $html_raw); // éviter [[
			$html_raw = preg_replace('/\]+(\s*)\]/', ']', $html_raw); // éviter ]]
			$html_raw = preg_replace('/\]\s*\[/', '][', $html_raw);   // éviter ][ mal placés
			$html_raw = preg_replace('/\(\(\s*#ENV\{([^}]+)\}\s*\)\)/', '(#ENV{$1})', $html_raw); // éviter les (( et ))

			// 🔹 Sauvegarde
			$modele_file = $dir_modeles . 'tac_' . strtolower($service) . '.html';
			ecrire_fichier($modele_file, $html_raw);

			echo "✅ Modèle créé : $modele_file<br/>";
		}
	}
}
function tarteaucitron_generer_json($data) {

	$dir_json = _DIR_PLUGIN_TARTEAUCITRON . 'json/';
	$dir_modeles = _DIR_PLUGIN_TARTEAUCITRON . 'modeles/';

	if (!is_dir($dir_json)) {
		sous_repertoire(_DIR_PLUGIN_TARTEAUCITRON, 'json');
	}

	$services = [];
	$pattern = '/###(.+?)###/';

	/* ===============================
	   HELPERS
	================================ */

	function formatParam($param) {
		$param = preg_replace('/\(.*?\)/', '', $param);
		$param = preg_replace('/[0-9\[\]]/', '', $param);
		$param = preg_replace('/[^a-zA-Z_-]/', '_', $param);
		$param = preg_replace('/_+/', '_', $param);
		$param = trim($param, '_');
		$param = strtolower($param);
		return 'ptac_' . $param;
	}

	function normalizeCode($code) {
		if (is_array($code)) {
			$flat = [];
			array_walk_recursive($code, function ($v) use (&$flat) {
				if (is_string($v)) {
					$flat[] = $v;
				}
			});
			return implode("\n", $flat);
		}
		return is_string($code) ? $code : '';
	}

	function cleanHtml($html) {
		if (!$html) {
			return '';
		}

		libxml_use_internal_errors(true);

		$dom = new DOMDocument();

		$dom->loadHTML(
			'<?xml encoding="UTF-8">' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$xpath = new DOMXPath($dom);

		// ❌ supprimer balises inutiles
		foreach (['script', 'style', 's'] as $tag) {
			while (($nodes = $dom->getElementsByTagName($tag))->length) {
				$nodes->item(0)
					->parentNode->removeChild($nodes->item(0));
			}
		}

		// ❌ supprimer nœuds texte qui sont des slugs-libellés (ex: "static-ads", "share-long", "share-bubble-t2")
		foreach (iterator_to_array($xpath->query('//text()')) as $node) {
			if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)+$/i', trim($node->nodeValue))) {
				$node->parentNode->removeChild($node);
			}
		}

		// ❌ supprimer éléments dont tout le contenu texte est un slug-libellé
		foreach (iterator_to_array($xpath->query('//*')) as $node) {
			if (!$node->parentNode) {
				continue;
			}
			$text = trim($node->textContent);
			if ($text !== '' && preg_match('/^[a-z0-9]+(-[a-z0-9]+)+$/i', $text)) {
				$node->parentNode->removeChild($node);
			}
		}

		// ❌ supprimer <p> vides après nettoyage des slugs
		foreach (iterator_to_array($xpath->query('//p')) as $node) {
			if (!$node->parentNode) {
				continue;
			}
			if (trim($node->textContent) === '') {
				$node->parentNode->removeChild($node);
			}
		}

		$htmlClean = $dom->saveHTML();

		// ❌ retirer la déclaration XML ajoutée pour l'encodage
		$htmlClean = preg_replace('/<\?xml[^>]*>\s*/i', '', $htmlClean);

		// ❌ nettoyer <p> autour des <div>
		$htmlClean = preg_replace('/<p>\s*(<div[^>]*>.*?<\/div>)\s*<\/p>/is', '$1', $htmlClean);

		libxml_clear_errors();

		return trim($htmlClean);
	}

	function cleanJs($js) {

		// ❌ balises <s>...</s> (code barré/commenté dans le JSON source)
		$js = preg_replace('/<s>.*?<\/s>/is', '', $js);

		// ❌ assignments avec objet vide ou commentaire seul : tarteaucitron.user.x = { /* ... */ };
		$js = preg_replace('/tarteaucitron\.user\.[a-zA-Z0-9_]+\s*=\s*\{[^{}]*\/\*[^*]*\*\/[^{}]*\}\s*;?\n?/s', '', $js);

		// wrap ptac
		$js = preg_replace_callback(
			"/(tarteaucitron\.user\.[a-zA-Z0-9_]+)\s*=\s*(ptac_[a-z0-9_-]+)\s*;/i",
			fn ($m) => "{$m[1]} = '{$m[2]}';",
			$js
		);

		// ❌ fonctions inutiles
		$js = preg_replace("/tarteaucitron\.user\.[a-zA-Z0-9_]+More\s*=\s*function\s*\([^)]*\)\s*\{.*?\};/s", '', $js);

		// ❌ anciens push
		$js = preg_replace("/\(tarteaucitron\.job\s*=\s*tarteaucitron\.job\s*\|\|\s*\[\]\)\.push\([^)]+\);?/i", '', $js);

		// ❌ labels restants
		$js = preg_replace('/\b[a-z]+(_[a-z]+)*-(inline|bubble|horizontal|none)-t\d+\b/i', '', $js);

		// ❌ meta / html parasites
		$js = preg_replace('/<meta[^>]*>/i', '', $js);
		$js = preg_replace('/<!DOCTYPE.*?>/is', '', $js);
		$js = preg_replace('/<\/?(html|body|head)[^>]*>/i', '', $js);

		// ❌ commentaires de ligne entière (// ...)
		$js = preg_replace('/^\s*\/\/.*$/m', '', $js);

		// lignes vides
		$js = preg_replace('/\n{2,}/', "\n", $js);

		return trim($js);
	}

	/* ===============================
	   MAIN LOOP
	================================ */

	foreach ($data as $category => $items) {

		if (!is_array($items)) {
			continue;
		}

		foreach ($items as $serviceName => $serviceData) {

			$jsCode = normalizeCode($serviceData['code']['js'] ?? '');
			$htmlCode = cleanHtml(normalizeCode($serviceData['code']['html'] ?? ''));

			// 🔍 params
			preg_match_all($pattern, $jsCode . ' ' . $htmlCode, $matches);
			$params = array_map('formatParam', array_unique($matches[1] ?? []));

			// 🔁 remplacement placeholders dans JS
			$jsCode = preg_replace_callback($pattern, fn ($m) => formatParam($m[1]), $jsCode);

			// 🔁 remplacement placeholders dans HTML
			$htmlCode = preg_replace_callback($pattern, fn ($m) => formatParam($m[1]), $htmlCode);

			// 🧼 nettoyage JS
			$fullJs = cleanJs($jsCode);

			// ➕ push unique
			$fullJs .= "\n(tarteaucitron.job = tarteaucitron.job || []).push('{$serviceName}');";

			// 📁 modèle existant ?
			$modele_file = $dir_modeles . 'tac_' . strtolower($serviceName) . '.html';

			$services[$serviceName] = [
				'hasModele' => file_exists($modele_file),
				'params' => $params,
				'type' => $category,
				'JS' => $fullJs,
				'HTML' => $htmlCode,
			];
		}
	}

	$file = $dir_json . 'services.json';

	file_put_contents($file, json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

	echo "✅ Fichier généré avec succès : $file\n";
}