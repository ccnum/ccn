<?php

/**
 * Gestion de l'affichage conditionnelle des saisies.
 * Partie spécifique php
 *
 * @package SPIP\Saisies\Afficher_si_php
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/saisies_afficher_si_commun');

/**
 * Traitement des saisies ayant l'option `afficher_si`.
 *
 * Lorsque qu'on affiche les saisies avec `#VOIR_SAISIES`,
 * ou lorsqu'on les vérifie avec saisies_verifier().
 * Si la condition d'affichage d'une saisie n'est pas remplie, on retire cette saisie du tableau de saisies, SAUF SI l'option `afficher_si_remplissage_uniquement` est activée et que `$env` est non null.
 *
 * @param array      $saisies
 *                            Tableau de descriptions de saisies
 * @param array|null $env
 *                            Tableau d'environnement transmis dans inclure/voir_saisies.html,
 *                            NULL si on doit rechercher dans _request (pour saisies_verifier()).
 * @param array $saisies_toutes_par_nom ensemble des saisies du formulaire courant, quelque soit le niveau de profondeur dans l'arborescence des saisies. A passer uniquement lorsque la fonction s'appelle elle-même, pour gérer la récursion
 * @return array
 *               Tableau de descriptions de saisies
 */
function saisies_verifier_afficher_si(array $saisies, ?array $env = null, array $saisies_toutes_par_nom = []): array {
	if (!$saisies_toutes_par_nom) {
		$saisies = pipeline('saisies_afficher_si_saisies', $saisies);
		$saisies_toutes_par_nom = saisies_lister_par_nom($saisies);
	}

	foreach ($saisies as $cle => $saisie) {
		$condition = trim($saisie['options']['afficher_si'] ?? '');
		if ($condition) {
			// Est-ce uniquement au remplissage?
			if (($saisie['options']['afficher_si_remplissage_uniquement'] ?? '')  == 'on') {
				$remplissage_uniquement = true;
			} else {
				$remplissage_uniquement = false;
			}

			// On transforme en une condition PHP valide
			$ok = saisies_evaluer_afficher_si($condition, $env, $saisies_toutes_par_nom);
			if (!$ok) {
				saisies_afficher_si_liste_masquees('set', $saisie);//Retenir que la saisie a été masquée
				if ($remplissage_uniquement == false || is_null($env)) {
					unset($saisies[$cle]);
				}
			}
		}
		if (isset($saisies[$cle]['saisies'])) {
			// S'il s'agit d'un fieldset ou equivalent, verifier les sous-saisies
			$a_merger = isset($saisies['options']) ? ['options' => $saisies['options']] : [];
			$saisies[$cle]['saisies'] = saisies_verifier_afficher_si(
				array_merge(
					$saisies[$cle]['saisies'],
					$a_merger
				),
				$env,
				$saisies_toutes_par_nom
			);
		}
	}
	return $saisies;
}

/**
 * Recherche les saisies qui ont été masquées par afficher_si
 * et les mets à `''`,
 * sauf si
 * *	- options globales de saisies :  `afficher_si_avec_post`
 * *  - option de la saisie spécifique : `afficher_si_avec_post`
 * Cette fonction est appelée à la toute fin de `saisies_verifier()`
 **/
function saisies_afficher_si_masquees_set_request_empty_string($saisies, $valeurs = null) {
	$saisies_masquees = saisies_afficher_si_liste_masquees('get');//Trouver les saisies masquées
	if (!empty($saisies['options']['afficher_si_avec_post'])) {
		return;
	}
	foreach ($saisies_masquees as $saisie) {
		if (
			empty($saisie['options']['afficher_si_avec_post']) // option de la saisie
		) {
			saisies_set_request_recursivement($saisie, '', $valeurs);
		}
	}
}


/**
 * Pose un set_request sur une saisie et toute ses sous-saisies.
 * Utiliser notamment pour annuler toutes les sous saisies d'un fieldeset
 * si le fieldset est masquée à cause d'un afficher_si.
 * @param array             $saisie
 * @param null|string|array $val (defaut `''`)
 * @param array             $valeurs
 *     Optionnellement un tableau de valeurs à passer à _request plutôt que GET/POST
 **/
function saisies_set_request_recursivement($saisie, $val = '', $valeurs = null) {
	// Attention, tout champ peut être un sous-tableau !
	saisies_set_request($saisie['options']['nom'], $val, $valeurs);

	if (isset($saisie['saisies'])) {
		foreach ($saisie['saisies'] as $sous_saisie) {
			saisies_set_request_recursivement($sous_saisie, $val, $valeurs);
		}
	}
}

/**
 * Récupère la valeur d'un champ à tester avec afficher_si
 * Si le champ est de type @config:xx@, alors prend la valeur de la config
 * Si le champ est de type @plugin:xx@, vérifier si le plugin est actif
 * Sinon en _request() ou en $env["valeurs"]
 * @param string $champ
 * @param null|array $env
 * @param array $saisies_par_nom
 *   Les saisies déjà classées par nom de champ
 * @return  null|mixed la valeur du champ ou de la config
 **/
function saisies_afficher_si_get_valeur_champ($champ, $env, $saisies_par_nom) {
	$valeur = null;
	$plugin = saisies_afficher_si_evaluer_plugin($champ);
	$config = saisies_afficher_si_get_valeur_config($champ);
	$fichiers = false;
	$est_tabulaire = false;

	if (isset($saisies_par_nom[$champ])) {
		$fichiers = saisies_saisie_est_fichier($saisies_par_nom[$champ]);
		$est_tabulaire = saisies_saisie_est_tabulaire($saisies_par_nom[$champ]);
	}
	if (strpos($champ, 'plugin:') === 0) {
		$valeur = $plugin;
	} elseif (strpos($champ, 'config:') === 0) {
		$valeur = $config;
	} elseif (is_null($env)) {
		if ($fichiers) {
			$precedent = saisies_request('cvtupload_fichiers_precedents');
			if ($precedent) {
				$precedent = $precedent[$champ];
			}
			$valeur = saisies_request_property_from_FILES($champ, 'name');
		} else {
			$valeur = saisies_request($champ);
		}
	} else {
		$valeur = saisies_request($champ, (!empty($env['valeurs']) && is_array($env['valeurs'])) ? $env['valeurs'] : $env);
		if (is_null($valeur)) {
			$valeur = '';
		}
	}
	if ($fichiers) {
		if (!is_array($precedent)) {
			$precedent = [];
		}
		$valeur = array_merge($valeur, $precedent);
		$valeur = array_filter($valeur);
	}

	// On teste si on doit forcer que ce soit un tableau, suivant le type de la saisie
	if ($est_tabulaire) {
		$data = saisies_trouver_data($saisies_par_nom[$champ]);
		$valeur = saisies_valeur2tableau($valeur, $data);
	}

	return $valeur;
}


/**
 * Prend un test conditionnel,
 * le sépare en une série de sous-tests de type champ - operateur - valeur
 * remplace chacun de ces sous-tests par son résultat
 * renvoie la chaine transformé
 * @param string $condition
 * @param array|null $env
 *   Tableau d'environnement transmis dans inclure/voir_saisies.html,
 *   NULL si on doit rechercher dans _request (pour saisies_verifier()).
 * @param  array $saisies_par_nom
 *   Les saisies déjà classées par nom de champ
 * @param string|null $no_arobase une valeur à tester là où il devrait y avoir un @@
 * @return string $condition
 **/
function saisies_transformer_condition_afficher_si($condition, $env = null, $saisies_par_nom = [], $no_arobase = null) {
	if ($tests = saisies_parser_condition_afficher_si($condition, $no_arobase)) {
		if (!saisies_afficher_si_verifier_syntaxe($condition, $tests)) {
			spip_log("Afficher_si incorrect. $condition syntaxe_incorrecte", 'saisies' . _LOG_CRITIQUE);
			return '';
		}

		foreach ($tests as $test) {
			$expression = $test['expression'];
			if (!isset($test['booleen'])) {
				$modificateur = $test['modificateur'] ?? '';
				$operateur = $test['operateur'] ?? null;
				$negation = $test['negation'] ?? '';

				if (isset($test['valeur'])) {
					$valeur = $test['valeur'];
				} else {
					$valeur = null;
				}

				if ($no_arobase === null) {
					$nom_champ = $test['champ'];
					$nom_champ = saisie_nom2name($nom_champ);
					// Cas des saisies type grille, rechercher le vrai nom de la saisie
					preg_match('/(.*)\[(.*)\]$/', $nom_champ, $sous_champ);
					$racine_champ = $sous_champ[1] ?? '';
					$sous_champ = $sous_champ[2] ?? '';
				} else {
					$nom_champ = '';
				}
				if (
						!$saisies_par_nom
						|| isset($saisies_par_nom[$nom_champ])
						|| strpos($nom_champ, 'config:') === 0
						|| strpos($nom_champ, 'plugin:') === 0
						|| isset($saisies_par_nom[$racine_champ])
				) {
					if ($no_arobase === null) {
						$valeur_champ = saisies_afficher_si_get_valeur_champ($nom_champ, $env, $saisies_par_nom);
					} else {
						$valeur_champ = $no_arobase;
					}
					$test_modifie = saisies_tester_condition_afficher_si($valeur_champ, $modificateur, $operateur, $valeur, $negation) ? 'true' : 'false';
					$condition = str_replace($expression, $test_modifie, $condition);
				} else {
					$condition = '';// Si champ inexistant, on laisse tomber tout le tests
					spip_log("Afficher_si incorrect. Champ $nom_champ inexistant", 'saisies' . _LOG_CRITIQUE);
				}
			}
		}
	} else {
		if (!saisies_afficher_si_verifier_syntaxe($condition, $tests)) {
			spip_log("Afficher_si incorrect. $condition syntaxe_incorrecte", 'saisies' . _LOG_CRITIQUE);
			return '';
		}
	}

	return $condition;
}


/**
 * Evalue un afficher_si
 * @param string $condition
 * @param array|null $env
 *   Tableau d'environnement transmis dans inclure/voir_saisies.html,
 *   NULL si on doit rechercher dans _request (pour saisies_verifier()).
 * @param array $saisies_par_nom
 *   Les saisies déjà classées par nom de champ
 * @param string|null $no_arobase une valeur à tester là où il devrait y avoir un @@
 * @return bool le résultat du test
 **/
function saisies_evaluer_afficher_si($condition, $env = null, $saisies_par_nom = [], $no_arobase = null) {
	$condition = saisies_transformer_condition_afficher_si($condition, $env, $saisies_par_nom, $no_arobase);
	if ($condition) {
		eval('$ok = ' . $condition . ';');
	} else {
		$ok = true;
	}
	return $ok;
}

/**
 * Liste des saisies masquées par afficher_si dans le hit courant
 * @param string $action ('set'|'get'), defaut 'get';
 * @param array $saisie complète
 * @return array|null
 **/
function saisies_afficher_si_liste_masquees($action = 'get', $saisie = '') {
	static $tableau = [];
	if ($action === 'set') {
		$tableau[] = $saisie;
	} elseif ($action === 'get') {
		return $tableau;
	}
}
