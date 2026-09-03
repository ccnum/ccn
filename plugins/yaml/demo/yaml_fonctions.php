<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Décode un fichier de démonstration, en mesurant la durée et en relevant l'erreur éventuelle.
 *
 * @param string $chemin  Chemin relatif au path SPIP, résolu ici par `find_in_path()`.
 * @param array $options
 * @return array
 */
function decoder_fichier_yaml($chemin, $options = []) {

	include_spip('inc/yaml');

	$fichier = find_in_path($chemin);
	$debut = microtime(true);
	$parsed = yaml_decode_file($fichier, $options, $erreur);
	$duree = (microtime(true) - $debut) * 1000;

	return [
		'fichier' => $fichier ?: "{$chemin} (introuvable)",
		'duree' => round($duree, 3) . ' ms',
		'erreur' => $erreur,
		'yaml' => $parsed,
	];
}

/**
 * Compare deux valeurs comme le ferait une assertion : le type compte.
 *
 * @param mixed $attendu
 * @param mixed $obtenu
 * @return bool
 */
function yaml_demo_identique($attendu, $obtenu) {
	if (is_array($attendu) and is_array($obtenu)) {
		return $attendu == $obtenu;
	}

	return $attendu === $obtenu;
}

/**
 * Rend une valeur PHP lisible dans un tableau HTML, type compris.
 *
 * Le contenu est tronqué : ces colonnes servent à reconnaitre une valeur et son type, pas à la lire en
 * entier. Sans cela une noisette d'une dizaine d'entrées étire tout le tableau.
 *
 * @param mixed $valeur
 * @param int $long  Longueur au-delà de laquelle la valeur est abrégée.
 * @return string
 */
function yaml_demo_afficher($valeur, $long = 90) {
	if (is_array($valeur)) {
		$json = json_encode($valeur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (mb_strlen($json) > $long) {
			$json = mb_substr($json, 0, $long) . '…';
		}

		return 'array(' . count($valeur) . ') ' . htmlspecialchars($json);
	}
	if (is_bool($valeur)) {
		return $valeur ? 'bool true' : 'bool false';
	}
	if (is_null($valeur)) {
		return 'null';
	}
	if (is_int($valeur) or is_float($valeur)) {
		return gettype($valeur) . ' ' . $valeur;
	}

	if (mb_strlen($valeur) > $long) {
		$valeur = mb_substr($valeur, 0, $long) . '…';
	}

	return 'string ' . htmlspecialchars('"' . $valeur . '"');
}

/**
 * Les cas limites de l'API, joués pour de vrai.
 *
 * Chaque cas dit ce qu'il attend, l'exécute, et se conclut par un verdict. C'est la suite de
 * non-régression du plugin : elle couvre ce qui a servi à valider les correctifs de la 3.3.0 — chemin
 * absent, fichier vide, contenu réduit à `0`, YAML malformé, document scalaire, inclusions, et le canal
 * `$erreur` qui seul peut signaler l'échec.
 *
 * @return array Liste de cas, chacun avec son verdict.
 */
function yaml_demo_cas_limites() {

	include_spip('inc/yaml');

	$chemin = function ($nom) {
		return find_in_path('demo/' . $nom);
	};

	$cas = [];

	// --- Ce qui n'est pas un document ------------------------------------------------------------
	$cas[] = [
		'groupe' => 'Chemins qui ne mènent à rien',
		'libelle' => 'Chemin <code>false</code>, ce que rend <code>find_in_path()</code> sur un fichier absent',
		'code' => "yaml_decode_file(false, [], \$erreur)",
		'attendu' => [],
		'erreur_attendue' => true,
		'appel' => function (&$erreur) {
			return yaml_decode_file(false, [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Chemins qui ne mènent à rien',
		'libelle' => 'Chemin vide',
		'code' => "yaml_decode_file('', [], \$erreur)",
		'attendu' => [],
		'erreur_attendue' => true,
		'appel' => function (&$erreur) {
			return yaml_decode_file('', [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Chemins qui ne mènent à rien',
		'libelle' => 'Fichier absent',
		'code' => "yaml_decode_file('/chemin/absent.yaml', [], \$erreur)",
		'attendu' => [],
		'erreur_attendue' => true,
		'appel' => function (&$erreur) {
			return yaml_decode_file(_DIR_PLUGIN_YAML . 'demo/absent.yaml', [], $erreur);
		},
	];

	// --- Des documents valides, mais pas des tableaux --------------------------------------------
	$cas[] = [
		'groupe' => 'Documents valides qui ne sont pas des tableaux',
		'libelle' => 'Fichier vide — ce n\'est pas une erreur',
		'code' => "yaml_decode_file('demo/test_vide.yaml', [], \$erreur)",
		'attendu' => [],
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_vide.yaml'), [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Documents valides qui ne sont pas des tableaux',
		'libelle' => 'Contenu réduit à <code>0</code> — YAML valide, longtemps avalé',
		'code' => "yaml_decode_file('demo/test_zero.yaml', [], \$erreur)",
		'attendu' => 0,
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_zero.yaml'), [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Documents valides qui ne sont pas des tableaux',
		'libelle' => 'Document scalaire',
		'code' => "yaml_decode_file('demo/test_scalaire.yaml', [], \$erreur)",
		'attendu' => 'une chaine, et rien de plus',
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_scalaire.yaml'), [], $erreur);
		},
	];

	// --- L'échec d'analyse ----------------------------------------------------------------------
	$cas[] = [
		'groupe' => 'Échec d\'analyse',
		'libelle' => 'YAML malformé — rend <code>false</code>, et <code>$erreur</code> dit pourquoi',
		'code' => "yaml_decode_file('demo/test_malforme.yaml', [], \$erreur)",
		'attendu' => false,
		'erreur_attendue' => true,
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_malforme.yaml'), [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Échec d\'analyse',
		'libelle' => 'Une chaine <code>false</code> est un document valide — d\'où le besoin de <code>$erreur</code>',
		'code' => "yaml_decode('false', [], \$erreur)",
		'attendu' => false,
		'erreur_attendue' => false,
		'appel' => function (&$erreur) {
			return yaml_decode('false', [], $erreur);
		},
	];

	// --- Les inclusions --------------------------------------------------------------------------
	$cas[] = [
		'groupe' => 'Inclusions',
		'libelle' => 'Inclusion résolue par l\'option <code>include</code>',
		'code' => "yaml_decode_file('demo/test_inclusion.yaml', ['include' => true], \$erreur)",
		'attendu' => true,
		'erreur_attendue' => false,
		'verifier' => function ($obtenu) {
			return is_array($obtenu) and isset($obtenu['parametres'][1]) and is_array($obtenu['parametres'][1]);
		},
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_inclusion.yaml'), ['include' => true], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Inclusions',
		'libelle' => 'Inclusion introuvable : la valeur d\'origine est conservée',
		'code' => "\$decode['parametres'][0]",
		'attendu' => 'inclure:inclusion-introuvable.yaml',
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($chemin) {
			$d = yaml_decode_file($chemin('test_inclusion.yaml'), ['include' => true], $erreur);

			return $d['parametres'][0] ?? null;
		},
	];
	$cas[] = [
		'groupe' => 'Inclusions',
		'libelle' => 'Clés numériques non séquentielles préservées',
		'code' => "yaml_decode_file('demo/test_cles_numeriques.yaml', ['include' => true], \$erreur)",
		'attendu' => [5 => 'cinq', 10 => 'dix', 2 => 'deux'],
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($chemin) {
			return yaml_decode_file($chemin('test_cles_numeriques.yaml'), ['include' => true], $erreur);
		},
	];

	// --- L'encodage, et l'aller-retour ----------------------------------------------------------
	$structure = ['nom' => 'noisette', 'parametres' => ['a', 'b'], 'actif' => true, 'niveau' => 3];
	$cas[] = [
		'groupe' => 'Encodage',
		'libelle' => 'Aller-retour : <code>yaml_encode()</code> puis <code>yaml_decode()</code> rend la structure',
		'code' => "yaml_decode(yaml_encode(\$structure))",
		'attendu' => $structure,
		'erreur_attendue' => false,
		'appel' => function (&$erreur) use ($structure) {
			return yaml_decode(yaml_encode($structure), [], $erreur);
		},
	];
	$cas[] = [
		'groupe' => 'Encodage',
		'libelle' => 'Une valeur non représentable ne fait pas échouer l\'encodage',
		'code' => "yaml_encode(['ressource' => STDERR])",
		'attendu' => true,
		'erreur_attendue' => false,
		'verifier' => function ($obtenu) {
			return is_string($obtenu) and $obtenu !== '';
		},
		'appel' => function (&$erreur) {
			$erreur = '';

			return yaml_encode(['ressource' => fopen('php://memory', 'r')]);
		},
	];

	// --- Exécution et verdict --------------------------------------------------------------------
	foreach ($cas as &$_cas) {
		$erreur = '';
		$_cas['obtenu'] = $_cas['appel']($erreur);
		$_cas['erreur'] = $erreur;

		$valeur_ok = isset($_cas['verifier'])
			? (bool) $_cas['verifier']($_cas['obtenu'])
			: yaml_demo_identique($_cas['attendu'], $_cas['obtenu']);
		$erreur_ok = $_cas['erreur_attendue'] === (bool) $erreur;

		$_cas['verdict'] = ($valeur_ok and $erreur_ok);
		$_cas['detail'] = [];
		if (!$valeur_ok) {
			$_cas['detail'][] = 'valeur';
		}
		if (!$erreur_ok) {
			$_cas['detail'][] = $_cas['erreur_attendue'] ? 'erreur attendue, absente' : 'erreur inattendue';
		}
		$_cas['detail_texte'] = implode(', ', $_cas['detail']);
		unset($_cas['appel'], $_cas['verifier']);
	}
	unset($_cas);

	return $cas;
}

/**
 * Les mêmes cas limites, rangés par groupe : `groupe => [cas, ...]`.
 *
 * Le regroupement se fait ici plutôt que dans le squelette, où le critère `{fusion}` ne s'applique pas à
 * une source `table`.
 *
 * @return array
 */
function yaml_demo_cas_limites_groupes() {

	$groupes = [];
	foreach (yaml_demo_cas_limites() as $_cas) {
		$groupes[$_cas['groupe']][] = $_cas;
	}

	return $groupes;
}

/**
 * Le compte des échecs, pour l'annoncer en tête de page.
 *
 * @return array{total: int, echecs: int}
 */
function yaml_demo_cas_limites_bilan() {

	$cas = yaml_demo_cas_limites();
	$echecs = 0;
	foreach ($cas as $_cas) {
		if (!$_cas['verdict']) {
			$echecs++;
		}
	}

	return ['total' => count($cas), 'echecs' => $echecs];
}

/**
 * Décode tous les fichiers YAML d'un dossier du path et rend le bilan de chacun.
 *
 * @param array $fichiers  Tableau chemin_relatif => chemin_resolu, tel que rend `find_all_in_path()`.
 * @param array $options
 * @return array
 */
function yaml_demo_scanner($fichiers, $options = []) {

	include_spip('inc/yaml');

	$bilan = [];
	foreach ($fichiers as $_nom => $_chemin) {
		$debut = microtime(true);
		$parsed = yaml_decode_file($_chemin, $options, $erreur);
		$duree = (microtime(true) - $debut) * 1000;

		$bilan[] = [
			'nom' => basename($_nom, '.yaml'),
			'chemin' => $_chemin,
			'duree' => round($duree, 2) . ' ms',
			'erreur' => $erreur,
			'entrees' => is_array($parsed) ? count($parsed) : '—',
			'type' => is_array($parsed) ? 'array' : gettype($parsed),
			'verdict' => (is_array($parsed) and !$erreur),
		];
	}

	return $bilan;
}
