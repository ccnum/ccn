<?php

/**
 * Gestion de l'affichage conditionnelle des saisies.
 * Partie spécifique js
 *
 * @package SPIP\Saisies\Afficher_si_js
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/saisies_afficher_si_commun');
include_spip('inc/saisies_lister');

/**
 * Transforme une condition afficher_si en condition js
 * @param string $condition
 * @param array $saisies_par_etapes les saisies du même formulaire, regroupées par etapes. Nécessaire pour savoir quel type de test js on met.
 * @return string
 **/
function saisies_afficher_si_js($condition, $saisies_par_etapes = []) {
	if (!$condition) {
		return '';
	}
	$saisies_par_etapes = saisies_mapper_option($saisies_par_etapes, 'nom', 'saisie_nom2name');
	$saisies_par_etapes = pipeline('saisies_afficher_si_saisies', $saisies_par_etapes);
	$etape = _request('_etape');
	if ($etape && array_key_exists('etape_1', $saisies_par_etapes)) {
		$saisies_etape_courante_par_nom = saisies_lister_par_nom($saisies_par_etapes["etape_$etape"]['saisies']);
		$saisies_toutes_par_nom = saisies_lister_par_nom($saisies_par_etapes);
	} else {
		$saisies_toutes_par_nom = $saisies_etape_courante_par_nom = saisies_lister_par_nom($saisies_par_etapes);
	}

	if ($tests = saisies_parser_condition_afficher_si($condition)) {
		if (!saisies_afficher_si_verifier_syntaxe($condition, $tests)) {
			spip_log("Afficher_si incorrect. $condition syntaxe incorrecte", 'saisies' . _LOG_CRITIQUE);
			return '';
		}
		foreach ($tests as $test) {
			$expression = $test['expression'];
			unset($test['expression']);// Les les fonctions saisies_afficher_si_js n'ont pas besoin de l'expression qui est deja parsée, et cela évite qu'elles l'insèrent dans le js, avec le risque du coup de remplacement recursif et du coup de saisie js invalide.

			$negation = $test['negation'] ?? '' ;
			$champ = $test['champ'] ?? '' ;
			$modificateur = $test['modificateur'] ?? [];
			$operateur = $test['operateur'] ?? null ;
			$booleen = $test['booleen'] ?? '';
			$valeur = $test['valeur'] ?? null ;

			$champ = saisie_nom2name($champ);
			// Cas des saisies type grille, rechercher le vrai nom de la saisie
			preg_match('/(.*)\[(.*)\]$/', $champ, $sous_champ);
			$racine_champ = $sous_champ[1] ?? '';
			$sous_champ = $sous_champ[2] ?? '';


			$plugin = saisies_afficher_si_evaluer_plugin($champ, $negation);

			if ($plugin !== '') {
				$condition = str_replace($expression, $plugin ? 'true' : 'false', $condition);
			} elseif (stripos($champ, 'config:') !== false) {
				$config = saisies_afficher_si_get_valeur_config($champ);
				$test_modifie = eval('return ' . saisies_tester_condition_afficher_si($config, $modificateur, $operateur, $valeur, $negation) . ';') ? 'true' : 'false';
				$condition = str_replace($expression, $test_modifie, $condition);
			} elseif ($booleen) {
				$condition = $condition;
			} else { // et maintenant, on rentre dans le vif du sujet : les champs.
				if (!saisies_verifier_coherence_afficher_si_par_champ($champ, $saisies_toutes_par_nom)) {
					//La saisie conditionnante n'existe pas pour ce formulaire > on laisse tomber
					spip_log("Afficher_si incorrect. Champ $champ inexistant", 'saisies' . _LOG_CRITIQUE);
					$condition = '';
				} elseif (
					!isset($saisies_etape_courante_par_nom[$champ])
					&& !($racine_champ && isset($saisies_etape_courante_par_nom[$racine_champ]))
				) {
					//Cas 1. La saisie existe bien dans le formulaire, mais pas à l'étape courante.
					if (isset($saisies_toutes_par_nom[$champ])) {
						$valeur_champ = saisies_get_valeur_saisie($saisies_toutes_par_nom[$champ]);
					} else {
						$valeur_champ = saisies_get_valeur_saisie($saisies_toutes_par_nom[$racine_champ]);
						$valeur_champ = $valeur_champ[$sous_champ] ?? '';
					}
					$test_modifie = eval('return ' . saisies_tester_condition_afficher_si($valeur_champ, $modificateur, $operateur, $valeur, $negation) . ';') ? 'true' : 'false';
					$condition = str_replace($expression, $test_modifie, $condition);
				} else {
					$type_saisie = $saisies_toutes_par_nom[$champ]['saisie'] ?? $saisies_toutes_par_nom[$racine_champ]['saisie'];
					if (!$f = charger_fonction($type_saisie, 'saisies_afficher_si_js', true)) {//Y-a-t'il une fonction spécifique pour la génération du JS de cette saisie ?
						$f = charger_fonction('defaut', 'saisies_afficher_si_js');
					}

					$condition = str_replace($expression, $f($test, $saisies_etape_courante_par_nom, $saisies_par_etapes), $condition);
				}
			}
		}
	} else {
		if (!saisies_afficher_si_verifier_syntaxe($condition)) {
			spip_log("Afficher_si incorrect. $condition syntaxe incorrecte", 'saisies' . _LOG_CRITIQUE);
			return '';
		}
	}
	return str_replace('"', '&quot;', $condition);
}

/**
 * Vérifier qu'un test JS d'afficher si est statique, c'est à dire qu'il ne dépend d'aucune saisie de l'étape en cours.
 *
 * Exemple
 *	`false && false` => statique, la fonction renvoie true
 *	`true && afficher_si(...)` => pas statique, la fonction renvoie false
 * @param string $afficher_si
 * @return bool
 **/
function saisies_afficher_si_js_est_statique($test) {
	$test = str_replace('false', '', $test);
	$test = str_replace('true', '', $test);
	$test = str_replace('&&', '', $test);
	$test = str_replace('||', '', $test);
	$test = str_replace('(', '', $test);
	$test = str_replace(')', '', $test);
	$test = str_replace('!', '', $test);
	$test = trim($test);
	if ($test) {//S'il reste encore quelque chose, c'est qu'on a des choses variables
		return false;
	} else {
		return true;
	}
}

