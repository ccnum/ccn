<?php

/**
 * Gestion de l'affichage conditionnelle des saisies.
 * Partie commun js/php
 *
 * @package SPIP\Saisies\Afficher_si_commun
 **/


/**
 * Reçoit une condition
 * la parse pour trouver champs/opérateurs/valeurs etc.
 * @param string $condition
 * @param null|string $no_arobase, permet de ne pas parser le arobase
 * @return array tableau d'analyse (resultat d'un preg_match_all) montrant sous condition par sous condition l'analyse en champ/opérateur/valeur etc.
 **/
function saisies_parser_condition_afficher_si($condition, $no_arobase = null) {
	static $cache = [
		'no_arobase' => [],
		'arobase' => []
	];
	if ($no_arobase !== null) {
		$cache_ici = &$cache['no_arobase'];
		$no_arobase = '?';
	} else {
		$no_arobase = '';
		$cache_ici = &$cache['arobase'];
	}
	if (isset($cache_ici[$condition])) {
		return $cache_ici[$condition];
	}

	$condition = preg_replace('#!\d* IN#', '!IN', $condition); // Un peu de souplesse, autoriser ! IN

	$regexp =
		'(?<negation>!?)' // négation éventuelle
		. "((?:(?<arobase>@)(?<champ>.+?)(\k<arobase>)))$no_arobase" // @champ_@, optionnel (formidable_ts)
		. '(?<total>:TOTAL)?' // TOTAL éventuel (pour les champs de type case à cocher)
		. '(' // partie operateur + valeur (optionnelle) : debut
		. '(?:\s*?)' // espaces éventuels après
		. '(?<operateur>==|!=|IN|!IN|>=|>|<=|<|MATCH|!MATCH)' // opérateur
		. '(?:\s*?)' // espaces éventuels après
		. '((?<guillemet>"|\')(?<valeur>.*?)(\k<guillemet>)|(?<valeur_numerique>[+-]?\d+))' // valeur (string) ou valeur_numérique (int)
		. ')?' // partie operateur + valeur (optionnelle) : fin
		. '|(?<booleen>false|true)';//accepter false/true brut
	$regexp = "#$regexp#";

	preg_match_all($regexp, $condition, $tests, PREG_SET_ORDER);
	$tests = array_map('saisies_afficher_si_filtrer_parse_condition', $tests);
	$tests = array_filter($tests, function ($m) {
		if ($m['expression'] === '') {
			return false;
		} else {
			return true;
		}
	});
	$tests = array_values($tests);

	$cache_ici[$condition] = $tests;
	return $tests;
}

/**
 * Filtrer le retour d'un parsage d'un test d'afficher_si,
 * pour ne pas garder des infos qui ne servent pas par là suite.
 * IE : si la REGEXP était optimale, on n'aurai pas besoin de cette fonction
 * Note : on garde les fonctions entrées vides, car parfois besoin de distinguer vide de null
 * @param array $parse
 * @return array $parse
 **/
function saisies_afficher_si_filtrer_parse_condition($parse) {
	// Pour des raisons de regexp distingue valeur et valeur numerique, mais pas besoin ici
	// Supprimer certaines choses dont on n'a pas besoin
	$parse['expression'] = $parse[0];
	unset($parse['arobase']);
	unset($parse['guillemet']);
	foreach ($parse as $cle => $valeur) {
		if (is_int($cle)) {
			unset($parse[$cle]);
		}
	}
	if (($parse['valeur_numerique'] ?? '') !== '') {
		$parse['valeur'] = $parse['valeur_numerique'];
		unset($parse['valeur_numerique']);
	}
	return $parse;
}

/**
 * Retourne le résultat de l'évaluation d'un plugin actif
 * @param string $champ (sans les @@)
 * @param string $negation ! si on doit nier
 * @return bool '' ('' si jamais on ne teste pas un plugin)
 **/
function saisies_afficher_si_evaluer_plugin($champ, $negation = '') {
	if (preg_match_all('#plugin:(.*)#', $champ, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $plugin) {
			$plugin_a_tester = $plugin[1];
			if ($negation) {
				$actif = !test_plugin_actif($plugin_a_tester);
			} else {
				$actif = test_plugin_actif($plugin_a_tester);
			}
		}
	}	else {
		$actif = '';
	}
	return $actif;
}


/**
 * Teste une condition d'afficher_si
 * @param string|array $valeur_champ la valeur du champ à tester. Cela peut être :
 *	- un string
 *	- un tableau
 *	- un tableau sérializé
 * @param string $total TOTAL si on demande de faire le décompte dans un tableau
 * @param string $operateur : l'opérateur
 * @param string $valeur la valeur à tester
 * @param string $negation y-a-t-il un négation avant le test ? '!' si oui
 * @return bool false / true selon la condition
 **/
function saisies_tester_condition_afficher_si($valeur_champ, $total, $operateur = '', $valeur = '', $negation = '') {
	// Si pas operateur ni de valeur on test juste qu'un champ est cochée / validé
	if (!$operateur && !$valeur) {
		// En JS `Boolean('0')` renvoie True ;
		// En PHP `boolval('0') renvoi False
		// On se cale sur le comportement de JS
		// Cf https://git.spip.net/spip-contrib-extensions/saisies/issues/274
		if ($valeur_champ === '0') {
			$valeur_champ = 'oui';
		}
		if ($negation) {
			return !(isset($valeur_champ) && $valeur_champ);
		}
		else {
			return isset($valeur_champ) && $valeur_champ;
		}
	}

	if (is_null($valeur_champ)) {
		$valeur_champ = '';
	}
	//Si champ est de type string, tenter d'unserializer
	if (!is_array($valeur_champ)) {
		$tenter_unserialize = @unserialize($valeur_champ);
		if ($tenter_unserialize) {
			$valeur_champ = $tenter_unserialize;
		}
	}
	// Transformation en tableau des valeurs et valeur_champ, si IN/!IN
	if (in_array($operateur, ['IN', '!IN'])) {
		if (!is_array($valeur_champ)) {
				$valeur_champ = [$valeur_champ];
		}
	}

	//Et maintenant appeler les sous fonctions qui vont bien
	if (is_string($valeur_champ)) {
		$retour = saisies_tester_condition_afficher_si_string($valeur_champ, $operateur, $valeur);
	} elseif (is_array($valeur_champ)) {
		$retour = saisies_tester_condition_afficher_si_array($valeur_champ, $total, $operateur, $valeur);
	}
	if ($negation) {
		return !$retour;
	} else {
		return $retour;
	}
}

/**
 * Teste un condition d'afficher_si lorsque la valeur envoyée par le formulaire est une chaîne
 * @param string $valeur_champ la valeur du champ à tester.
 * @param string $operateur : l'opérateur:
 * @param string|int $valeur la valeur à tester.
 * @return bool false / true selon la condition
 **/
function saisies_tester_condition_afficher_si_string($valeur_champ, $operateur, $valeur) {
	if ($operateur === '==') {
		return $valeur_champ == $valeur;
	} elseif ($operateur === '!=') {
		return $valeur_champ != $valeur;
	} elseif ($operateur === '<') {
		return $valeur_champ < $valeur;
	} elseif ($operateur === '<=') {
		return $valeur_champ <= $valeur;
	} elseif ($operateur === '>') {
		return $valeur_champ > $valeur;
	} elseif ($operateur === '>=') {
		return $valeur_champ >= $valeur;
	} elseif ($operateur === 'MATCH') {
		return preg_match($valeur, $valeur_champ);
	} elseif ($operateur === '!MATCH') {
		return !preg_match($valeur, $valeur_champ);
	} else {//Si mauvaise operateur -> on annule
		return false;
	}
}

/**
 * Teste un condition d'afficher_si lorsque la valeur postée est un tableau
 * @param array $valeur_champ la valeur du champ à tester.
 * @param string $operateur : l'opérateur:
 * @param string $valeur la valeur à tester pour un IN. Par exemple "23" ou encore "23,25"
 * @return bool false / true selon la condition
 **/
function saisies_tester_condition_afficher_si_array($valeur_champ, $total, $operateur, $valeur) {
	if ($total) {//Cas 1 : on demande à compter le nombre total de champ
		return saisies_tester_condition_afficher_si_string(count($valeur_champ), $operateur, $valeur);
	} else {//Cas deux : on test une valeur
		$valeur = explode(',', $valeur);
		$intersection = array_intersect($valeur_champ, $valeur);
		if (in_array($operateur, ['==', 'IN'])) {
			return count($intersection) > 0;
		} else {
			return count($intersection) == 0;
		}
	}
}

/**
 * Retourne la valeur d'une config, si nécessaire
 * @param string $champ le "champ" a tester : config:xxx ou un vrai champ
 * @return string
 **/
function saisies_afficher_si_get_valeur_config($champ) {
	$valeur = '';
	if (preg_match('#config:(.*)#', $champ, $config)) {
		$config_a_tester = str_replace(':', '/', $config[1]);
		$valeur = lire_config($config_a_tester);
	}
	return $valeur;
}

/**
 * Vérifie qu'une condition est sécurisée
 * IE : ne permet pas d'executer n'importe quel code arbitraire.
 * @param string $condition
 * @param array $tests tableau des tests parsés
 * @return bool true si secure / false sinon
 **/
function saisies_afficher_si_secure($condition, $tests = []) {
	$condition_original = $condition;
	$hors_test = ['||','&&','!','(',')','true','false'];
	foreach ($tests as $test) {
		$condition = str_replace($test['expression'], '', $condition);
	}
	foreach ($hors_test as $hors) {
		$condition = str_replace($hors, '', $condition);
	}
	$condition = trim($condition);
	if ($condition) {// il reste quelque chose > c'est le mal
		spip_log("Afficher_si incorrect. $condition_original non sécurisée; il reste une partie non parsée : $condition", 'saisies' . _LOG_CRITIQUE);
		return false;
	} else {
		return true;
	}
}

/**
 * Vérifie qu'une condition respecte la syntaxe formelle
 * @param string $condition
 * @param array $tests liste des tests simples
 * @return bool
 **/
function saisies_afficher_si_verifier_syntaxe($condition, $tests = []) {
	if (!$condition) {
		// Si vide, alors tout va bien
		return true;
	}
	if ($tests && saisies_afficher_si_secure($condition, $tests)) {//Si cela passe la sécurité, faisons des tests complémentaires
		// parenthèses équilibrées
		if (substr_count($condition, '(') != substr_count($condition, ')')) {
			return false;
		}
		// pas de && ou de || qui traine sans rien à gauche ni à droite
		$condition = " $condition ";
		$condition_pour_sous_test = str_replace('||', '$', $condition);
		$condition_pour_sous_test = str_replace('&&', '$', $condition_pour_sous_test);
		$liste_sous_tests = explode('$', $condition_pour_sous_test);
		$liste_sous_tests = array_map('trim', $liste_sous_tests);
		if ($liste_sous_tests != array_filter($liste_sous_tests)) {
			return false;
		}

		//Vérifier la syntaxe regexp en cas de MATCH
		foreach ($tests as $test) {
			if (in_array($test['operateur'] ?? '', ['MATCH', '!MATCH'])) {
				if (!($regexp = saisies_afficher_si_parser_valeur_MATCH($test['valeur'])) || !$regexp['regexp']) {
					$expression = $test['expression'];
					spip_log("Afficher_si incorrect. $expression incluant MATCH, mais valeur à tester non valide (manque // ?)", 'saisies' . _LOG_CRITIQUE);
					return false;
				}
			}
		}
		// Tout va bien !
		return true;
	}
	// Pas sécure, on refuse
	return false;
}

/**
 * Décompose une chaine
 *'/regex/modificateur'
 * @param string $valeur
 * @return array(
 *	'regexp' => string,
 *	'modificateur' => string
 *	)
 **/
function saisies_afficher_si_parser_valeur_MATCH($valeur) {
	preg_match('#^\/(.*)\/(.*)$#', $valeur, $m);
	if ($m) {
		return [
			'regexp' => $m[1],
			'regexp_modif' => $m[2]
		];
	} else {
		return [
			'regexp' => '',
			'regexp_modif' => ''
		];
	}
}



/**
 * Vérifier que les afficher_si dans un tableau de saisies sont cohérents
 * A savoir qu'on ne demande pas un test sur un champ inexistans
 * @param array $saisie
 * @return string erreurs
 **/
function saisies_verifier_coherence_afficher_si(array $saisies): string {
	$erreur = '';
	$liste_erreurs = [];
	$saisies_avec_afficher_si = saisies_lister_avec_option('afficher_si', $saisies);
	$saisies = pipeline('saisies_afficher_si_saisies', $saisies);
	$saisies_par_nom = array_keys(saisies_lister_par_nom($saisies));

	foreach ($saisies_avec_afficher_si as $saisie) {
		$afficher_si = $saisie['options']['afficher_si'];
		preg_match_all('/@(.*)@/mU', $afficher_si, $champs_dans_afficher_si);
		$champs_dans_afficher_si = $champs_dans_afficher_si[1];

		// Autoriser '@config:xxx' et '@plugin:xx@'
		$champs_dans_afficher_si = array_filter($champs_dans_afficher_si, function ($champ) {
			if (strpos($champ, 'config:') === 0 || strpos($champ, 'plugin:') === 0) {
				return false;
			} else {
				return true;
			}
		});
		$diff = array_diff($champs_dans_afficher_si, $saisies_par_nom);
		if ($diff) {
			$liste_erreurs[$saisie['options']['nom']] = [
				'erreurs' => 	$diff,
				'label' => $saisie['options']['label_case'] ?? $saisie['options']['label']
			];
		}
	}

	// Une fois les erreurs compilés, générer le message d'erreur
	if ($liste_erreurs) {
		$erreur = singulier_ou_pluriel(
			count($liste_erreurs),
			'saisies:coherence_afficher_si_erreur_singulier',
			'saisies:coherence_afficher_si_erreur_pluriel'
		);
		foreach ($liste_erreurs as $cle => $value) {
			$erreur .= '<br /> - '
				. _T(
					'saisies:coherence_afficher_si_appel',
					[
						'label' => $value['label'],
						'erreurs' => '@' . implode('@, @', $value['erreurs']) . '@'
					]
				);
		}
	}
	return $erreur;
}