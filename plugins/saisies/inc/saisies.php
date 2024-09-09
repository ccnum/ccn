<?php

/**
 * Gestion des saisies
 *
 * @package SPIP\Saisies\Saisies
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Différentes méthodes pour trouver les saisies
include_spip('inc/saisies_lister');

// Différentes méthodes pour trouver les saisies avec un .yaml
include_spip('inc/saisies_lister_disponibles');

// Différentes méthodes pour manipuler une liste de saisies
include_spip('inc/saisies_manipuler');

// Les outils pour identifier les saisies de manière stables
include_spip('inc/saisies_identifiants');

// Les outils pour vérifier les saisies
include_spip('inc/saisies_verifier');

// Les outils pour trouver la valeur d'un champ posté depuis une saisies
include_spip('inc/saisies_request');

// Les outils pour afficher les saisies et leur vue
include_spip('inc/saisies_afficher');

// Les outils pour manipuler les options data
include_spip('inc/saisies_data');

// Les outils pour l'affichage conditionnelle des saisies
include_spip('inc/saisies_afficher_si_php');

// Les outils pour l'aide
include_spip('inc/saisies_aide');

// Les outils pour faciliter la construction de formulaires CVT sous formes de listes de saisies
include_spip('inc/saisies_formulaire');

// Les outils pour faciliter les conversion de name
include_spip('inc/saisies_name');

// Les outils pour obtenir des infos sur une saisie précise
include_spip('inc/saisies_info');

if (!function_exists('_T_ou_typo')) {
	/**
	 * une fonction qui regarde si $texte est une chaine de langue
	 * de la forme <:qqch:>
	 * si oui applique _T()
	 * si non applique typo() suivant le mode choisi
	 *
	 * @param mixed $valeur
	 *     Une valeur à tester. Si c'est un tableau, la fonction s'appliquera récursivement dessus.
	 * @param string $mode_typo
	 *     Le mode d'application de la fonction typo(), avec trois valeurs possibles "toujours", "jamais" ou "multi".
	 * @return mixed
	 *     Retourne la valeur éventuellement modifiée.
	 */
	function _T_ou_typo($valeur, $mode_typo = 'toujours', $connect = null, $env = []) {
		if (!in_array($mode_typo, ['toujours', 'multi', 'jamais'])) {
			$mode_typo = 'toujours';
		}

		// Si la valeur est bien une chaine (et pas non plus un entier déguisé)
		if (is_string($valeur) && !is_numeric($valeur)) {
			$presence_idiome = strpos($valeur, '<:');
			// Si la chaine est du type <:truc:> on passe à _T()
			if (
				$presence_idiome === 0
				&& preg_match('/^\<:([^>]*?):\>$/', $valeur, $match)
			) {
				$valeur = _T($match[1]);
			} else {
				// Sinon on la passe a typo() si c'est pertinent
				if ($presence_idiome !== false) {
					if (class_exists(Spip\Texte\Collecteur\Idiomes::class)) {//SPIP 4.2 et >
						$idiomes = new Spip\Texte\Collecteur\Idiomes();
						$presence_idiome = $idiomes->collecter($valeur, ['detecter_presence' => true]);
					} else {// SPIP 4.1 et <
						include_spip('inc/texte');
						$presence_idiome = preg_match(_EXTRAIRE_IDIOME, $valeur);
					}
				}

				if (
					$mode_typo === 'toujours'
					|| ($mode_typo === 'multi' && ($presence_idiome || strpos($valeur, '<multi>') !== false))
				) {
					include_spip('inc/texte');
					// définir le connect pour éviter de déclencher les sécurités dans typo
					// mais si on est en GLOBALS['filtrer_javascript'] == -1 alors le résultat passera dans safehtml
					$env['espace_prive'] = false;
					$valeur = typo($valeur, true, $connect ?? '', $env);
					// et sécuriser quand même le tout
					$valeur = interdire_scripts($valeur);
				}
			}
		}
		// Si c'est un tableau, on réapplique la fonction récursivement
		elseif (is_array($valeur)) {
			foreach ($valeur as $cle => $valeur2) {
				$valeur[$cle] = _T_ou_typo($valeur2, $mode_typo, $connect, $env);
			}
		}

		return $valeur;
	}
}
