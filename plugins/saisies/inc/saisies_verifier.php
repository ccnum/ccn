<?php

/**
 * Gestion de la verification des saisies
 *
 * @package SPIP\Saisies\Verifier
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Vérifier tout un formulaire tel que décrit avec les Saisies
 *
 * @param array $formulaire Le formulaire à vérifier, c'est à dire un tableau de saisies, avec éventuellement une clé options, comprenant tout les étapes
 * @param bool $saisies_masquees_empty_string
 *		Si true, les saisies masquées selon afficher_si ne sont pas verifiées.
 *		/!\ Dans tous les cas ces saisies sont mises à `''` à la fin de des tests, si aucune erreur.
 * @param $etape l'étape courante à vérifier
 * @param array $valeurs Optionnellement un tableau de valeurs à passer à _request plutôt que GET/POST
 * @return array Retourne un tableau d'erreurs
 */
function saisies_verifier($formulaire, $saisies_masquees_empty_string = true, $etape = null, $valeurs = null) {
	include_spip('inc/verifier');

	$verif_fonction = charger_fonction('verifier', 'inc', true);

	// Supprimer les saisies depubliéee
	$formulaire = saisies_supprimer_depublie($formulaire);


	// Lister les saisies par étapes, si besoin
	if (is_numeric($etape)) {
		$saisies_par_etapes = saisies_lister_par_etapes($formulaire);
	} else {
		$saisies_par_etapes = $formulaire;
	}


	// Enlever les afficher_si où la condition n'est pas validée, si besoin
	if ($saisies_masquees_empty_string) {
		$saisies_par_etapes_apres_verification_afficher_si = saisies_verifier_afficher_si($saisies_par_etapes, $valeurs);
	} else {
		$saisies_par_etapes_apres_verification_afficher_si = $saisies_par_etapes;
	}

	// Trouver les saisies de l'étape courante
	if (is_numeric($etape)) {
		if (isset($saisies_par_etapes_apres_verification_afficher_si["etape_$etape"])) {
			$saisies_etape_courante_apres_verification_afficher_si = $saisies_par_etapes_apres_verification_afficher_si["etape_$etape"]['saisies'];
		} else {//Si jamais l'étape courante a été masquée par afficher_si
			$saisies_etape_courante_apres_verification_afficher_si = [];
		}
	} else {
		$saisies_etape_courante_apres_verification_afficher_si = $saisies_par_etapes_apres_verification_afficher_si;
	}

	// On passe à une liste par nom
	$saisies_etape_courante_apres_verification_afficher_si_par_nom = saisies_lister_par_nom($saisies_etape_courante_apres_verification_afficher_si);

	// Vérifier si c'est obligatoire
	$erreurs = [];
	// On parcourt chacune des saisies
	$anciennes_valeurs = saisies_request('anciennes_valeurs', $valeurs);
	foreach ($saisies_etape_courante_apres_verification_afficher_si_par_nom as $saisie) {
		$champ = $saisie['options']['nom'];
		$valeur = saisies_get_valeur_saisie($saisie, $valeurs);
		$saisie['options']['ancienne_valeur'] = $anciennes_valeurs[$champ] ?? '';


		$obligatoire = $saisie['options']['obligatoire'] ?? '';
		$file = saisies_saisie_est_fichier($saisie);

		$erreur = saisies_saisie_verifier_obligatoire($saisie, $valeur);
		// S'il y a une erreur on passe à la saisie suivante
		if ($erreur) {
			$erreurs[$champ] = $erreur;
			continue;
		}
		$verifier_tous = $saisie['verifier'] ?? [];
		// Compatibilité historique avec les vieux appels
		if (isset($verifier_tous['type'])) {
			$verifier_tous = [$verifier_tous];
		}
		//Boucle sur toutes les verif
		foreach ($verifier_tous as $verifier) {
			if (is_array($verifier) && $verifier) {//Sécurité d'appel
				// Si on fait une vérification de type fichiers, il n'y a pas vraiment de normalisation, mais un retour d'erreur fichiers par fichiers
				if ($verif_fonction) {
					if ($verifier['type'] == 'fichiers') {
						$normaliser = [];
					} else {
						$normaliser = null;
					}
					$options = $verifier['options'] ?? [];
					if (!$options) {//Sécurité, si jamais ''
						$options = [];
					}
					$options = array_merge($options, ['_saisie' => $saisie]);
					if ($erreur_eventuelle = $verif_fonction($valeur, $verifier['type'], $options, $normaliser)) {
						if (isset($erreurs[$champ])) {
							$erreurs[$champ] .= '<br />' . $erreur_eventuelle;
						} else {
							$erreurs[$champ] = $erreur_eventuelle;
						}
						// Si le champ n'est pas valide par rapport au test demandé, on ajoute l'erreur
					}
					// S'il n'y a pas d'erreur et que la variable de normalisation a été remplie, on l'injecte dans le POST
					elseif (!is_null($normaliser) && $verifier['type'] != 'fichiers') {
						saisies_set_request($champ, $normaliser, $valeurs);
					}
				} else {
					spip_log('Demande de vérification, mais fonction inc_verifier inexistante (probablement plugin verifier manquant)', 'saisies' . _LOG_ERREUR);
				}
			}
		}
	}

	// On passe nos résultats à un pipeline
	$erreurs = pipeline(
		'saisies_verifier',
		[
			'args' => [
				'formulaire' => $formulaire,
				'saisies' => $saisies_etape_courante_apres_verification_afficher_si_par_nom,
				'saisies_par_etapes' => $saisies_par_etapes,
				'saisies_par_etapes_apres_verification_afficher_si' => $saisies_par_etapes_apres_verification_afficher_si,
				'saisies_etape_courante_apres_verification_afficher_si' => $saisies_etape_courante_apres_verification_afficher_si,
				'saisies_masquees_empty_string' => $saisies_masquees_empty_string,
				'etape' => $etape,
				'valeurs' => $valeurs,
			],
			'data' => $erreurs
		]
	);

	//S'il n'y a pas d'erreur, et seulement si on vient de franchir la dernière étape, on vide les afficher_si)
	if (
		empty($erreurs)
		&&  ($etape === count($saisies_par_etapes)
		&& !_request('aller_a_etape', $valeurs))
		|| (!$etape)
	) {
		saisies_afficher_si_masquees_set_request_empty_string($saisies_par_etapes, $valeurs);
	}


	// Vérifier que les valeurs postées sont acceptables, à savoir par exemple que pour un select, ce soit ce qu'on a proposé. On vérifie cela en tout dernier, après le vidage des afficher_si car certainses saisies peuvent avoir des valeurs acceptables qui dépendant des afficher_si (exemple : les saisies calculs).  Si jamais on a une valeur innacceptable, c'est que la personne a triché sur le POST en truandant le HTML, donc on s'en fiche si en retour son formulaire d'erreur n'est pas cohérent.
	if ($formulaire['options']['verifier_valeurs_acceptables'] ?? '') {
		$erreurs = saisies_verifier_valeurs_acceptables($saisies_etape_courante_apres_verification_afficher_si_par_nom, $erreurs);
	}
	return $erreurs;
}

/**
 * Vérifier que les valeurs postées sont acceptables,
 * c'est-à-dire qu'elles ont été proposées lors de la conception de la saisie.
 * Typiquement pour une saisie radio, vérifier que les gens n'ont pas postée une autre fleur.
 * @param $saisies array tableau général des saisies, déjà aplati, classé par nom de champ
 * @param $erreurs array tableau des erreurs
 * @return array table des erreurs modifiés
 **/
function saisies_verifier_valeurs_acceptables($saisies, $erreurs) {
	$verifier = charger_fonction('valeurs_acceptables', 'verifier');
	foreach ($saisies as $saisie => $description) {
		// Pas la peine de vérifier si par ailleurs il y a déjà une erreur
		if (isset($erreurs[$saisie])) {
			continue;
		}
		$valeur = saisies_request($saisie);
		$description['options']['ancienne_valeur'] = saisies_request("anciennes_valeurs/$saisie");
		//Il n'y a rien à vérifier sur les saisies pas champ
		if (!saisies_saisie_est_champ($description)) {
			continue;
		}
		if ($erreur = $verifier($valeur, ['_saisie' => $description])) {
			$erreurs[$saisie] = $erreur;
		}
	}
	return $erreurs;
}

/**
 * Prend un tableau de saisies
 * et applique l'option `depublie` à toutes les sous-saisies d'une saisie dépubliée
 * @param array $saisies
 * @param string $depublie
 * @return array
 **/
function saisies_appliquer_depublie_recursivement(array $saisies, string $depublie = ''): array {
	foreach ($saisies as &$saisie) {
		if (!($saisie['options']['depublie'] ?? '') && $depublie) {
			$saisie['options']['depublie'] = $depublie;
		}
		if (isset($saisie['saisies'])) {
			$saisie['saisies'] = saisies_appliquer_depublie_recursivement($saisie['saisies'], $saisie['options']['depublie'] ?? $depublie);
		}
	}
	return $saisies;
}

/**
 * Verifier si une saisie individuelle remplit les conditions d'obligation
 * en tenant compte du fait que la valeur envoyé peut être :
 *	1. Un tableau
 *	2. Un fichier
 *	3. Une chaine
 *	@param array $saisie
 *	@param mixed $valeur
 *	@return string message d'erreur ou `''`
**/
function saisies_saisie_verifier_obligatoire(array $saisie, $valeur): string {
	$depublie	= $saisie['options']['depublie'] ?? '';
	// Inutile d'aller plus loin si dépubliée
	if ($depublie) {
		return '';
	}
	$obligatoire = $saisie['options']['obligatoire'] ?? '';
	// Inutile d'aller plus loin si pas obligatoire
	if (!$obligatoire || $obligatoire === 'non') {
		return '';
	}

	$erreur = '';

	$file = saisies_saisie_est_fichier($saisie);
	if (
			($file && $valeur == null)
			|| (!$file && (
				is_null($valeur)
				|| (is_string($valeur) && trim($valeur) == '')
				|| (is_array($valeur) && count($valeur) == 0)
			))
	) {
		$erreur = true;
	}

	// Cas où c'est une saisie avec data_rows et data_col ($choix_grille)
	foreach (saisies_chaine2tableau($saisie['options']['data_rows'] ?? []) as $cle => $row) {
		if (!isset($valeur[$cle])) {
			$erreur = true;
			break;
		}
	}

	// Choix du message d'erreur
	if ($erreur) {
		if ($saisie['options']['erreur_obligatoire'] ?? '') {
			$erreur = _T_ou_typo($saisie['options']['erreur_obligatoire']);
		} else {
			$erreur =  _T('info_obligatoire');
		}
	}

	return $erreur;
}
