<?php

/**
 * Gestion de la manipulation des saisies.
 *
 * @return SPIP\Saisies\Manipuler
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Supprimer une saisie dont on donne l'identifiant, le nom ou le chemin.
 *
 * @param array $saisies
		 Tableau des descriptions de saisies
 * @param string|array $id_ou_nom_ou_chemin
 *   L'identifiant unique
 *     ou le nom de la saisie à supprimer
 *     ou son chemin sous forme d'une liste de clés
 *
 * @return array
 *               Tableau modifié décrivant les saisies
 */
function saisies_supprimer($saisies, $id_ou_nom_ou_chemin) {
	// On enlève les options générales avant de manipuler
	if (isset($saisies['options'])) {
		$options_generales = $saisies['options'];
		unset($saisies['options']);
	}

	// Si la saisie n'existe pas, on ne fait rien
	if ($chemin = saisies_chercher($saisies, $id_ou_nom_ou_chemin, true)) {
		// La position finale de la saisie
		$position = array_pop($chemin);

		// On va chercher le parent par référence pour pouvoir le modifier
		$parent = &$saisies;
		foreach ($chemin as $cle) {
			$parent = &$parent[$cle];
		}

		// On supprime et réordonne
		unset($parent[$position]);
		$parent = array_values($parent);
	}

	// On remet les options générales après avoir manipulé
	if (isset($options_generales)) {
		$saisies['options'] = $options_generales;
	}

	return $saisies;
}

/**
 * Insère une saisie à une position donnée dans un tableau de donnée
 *		- soit en lui passant un chemin
 *		- soit en lui passant une saisie devant laquelle se placer
 * @param array $saisies     Tableau des descriptions de saisies
 * @param array $saisie     Description de la saisie à insérer
 * @param array|string $id_ou_nom_ou_chemin
 *	- Si array c'est un chemin
 *     Position complète où insérer la saisie
 *				- Si directement à la racine du tableau : array(<index_où_inserer>)
 *				- Si au sein d'un fieldset ou assimilé : array(<index_du _fieldset>, 'saisies', <index_où_inserer_au_sein_du_fieldset>)
 *	- Si string
 *		- Si entre crochets, ca veut dire qu'on insère à la fin d'un fieldset `[fieldset]`
 *		- Si entre crochets, suivis d'un entier entre crochet, on insère à une position données dans le fieldset `[fieldset][0]`
 *		- Si pas de crochet, on insère avant la saisie `saisie`
 *  - En absence, insère la saisie à la fin.
 * @return array
 *     Tableau des saisies complété de la saisie insérée
 **/
function saisies_inserer($saisies, $saisie, $id_ou_nom_ou_chemin = []) {
	if (is_string($id_ou_nom_ou_chemin) && $id_ou_nom_ou_chemin) {//Est-ce qu'on n'a un nom ou un id ?
		if (preg_match('/^\[(@?[\w]*)\](\[([\d])*\])*$/', $id_ou_nom_ou_chemin, $match)) {//Si [fieldset], inserer à la fin du fieldset, si [fieldset][X] inserer à la position X dans le fieldset
			if (isset($match[3])) {
				$position = $match[3];
			} else {
				$position = 10000000;
			}
			$parent = saisies_chercher($saisies, $match[1], true);
			$chemin = array_merge($parent, ['saisies', $position]);
			$saisies = saisies_inserer_selon_chemin($saisies, $saisie, $chemin);
		} else {
			$saisies = saisies_inserer_avant($saisies, $saisie, $id_ou_nom_ou_chemin);
		}
	}	else {
		$saisies = saisies_inserer_selon_chemin($saisies, $saisie, $id_ou_nom_ou_chemin);
	}
	return $saisies;
}

/**
 * Insère une saisie avant une autre saisie.
 *
 * @param array $saisies     Tableau des descriptions de saisies
 * @param array $saisie     Description de la saisie à insérer
 * @param array $id_ou_nom_ou_chemin identifiant ou nom ou chemin de la saisie devant laquelle inserer
 * @return array
 *     Tableau des saisies complété de la saisie insérée
 */
function saisies_inserer_avant($saisies, $saisie, $id_ou_nom_ou_chemin) {
	if (is_array($id_ou_nom_ou_chemin)) {
		$chemin = $id_ou_nom_ou_chemin;
	} else {
		$chemin = saisies_chercher($saisies, $id_ou_nom_ou_chemin, true);
	}
	$saisies = saisies_inserer_selon_chemin($saisies, $saisie, $chemin);
	return $saisies;
}

/**
 * Insère une saisie après une autre saisie.
 *
 * @param array $saisies     Tableau des descriptions de saisies
 * @param array $saisie     Description de la saisie à insérer
 * @param array $id_ou_nom_ou_chemin identifiant ou nom ou chemin de la saisie derrière laquelle inserer
 * @return array
 *     Tableau des saisies complété de la saisie insérée
 */
function saisies_inserer_apres($saisies, $saisie, $id_ou_nom_ou_chemin) {
	if (is_array($id_ou_nom_ou_chemin)) {
		$chemin = $id_ou_nom_ou_chemin;
	} else {
		$chemin = saisies_chercher($saisies, $id_ou_nom_ou_chemin, true);
	}
	// Si la saisie que l'on cherche n'existe pas, insérer tout à la fin
	if ($chemin === null) {
		$saisies = saisies_inserer_selon_chemin($saisies, $saisie);
	} else {
		$chemin[count($chemin) - 1]++;
		$saisies = saisies_inserer_selon_chemin($saisies, $saisie, $chemin);
	}
	//Augmenter de 1 le dernier element du chemin
	return $saisies;
}

/**
 * Insère une saisie à une position donnée, en lui passant un chemin.
 *
 * @param array $saisies     Tableau des descriptions de saisies
 * @param array $saisie     Description de la saisie à insérer
 * @param array $chemin
 *     Position complète où insérer la saisie
 *				- Si directement à la racine du tableau : array(<index_où_inserer>)
 *				- Si au sein d'un fieldset ou assimilé : array(<index_du _fieldset>, 'saisies', <index_où_inserer_au_sein_du_fieldset>)
 *     En absence, insère la saisie à la fin.
 * @return array
 *     Tableau des saisies complété de la saisie insérée
 */
function saisies_inserer_selon_chemin($saisies, $saisie, $chemin = []) {
	// On enlève les options générales avant de manipuler
	if (isset($saisies['options'])) {
		$options_generales = $saisies['options'];
		unset($saisies['options']);
	}
	// On vérifie quand même que ce qu'on veut insérer est correct
	if (isset($saisie['saisie']) && isset($saisie['options']['nom'])) {
		// ajouter un identifiant
		$saisie = saisie_identifier($saisie);

		// Par défaut le parent c'est la racine
		$parent = &$saisies;
		// S'il n'y a pas de position, on va insérer à la fin du formulaire
		if (!$chemin) {
			$position = count($parent);
		} elseif (is_array($chemin)) {
			$position = array_pop($chemin);
			foreach ($chemin as $cle) {
				// Si la clé est un conteneur de saisies ("saisies") et qu'elle n'existe pas encore, on la crée
				if ($cle == 'saisies' && !isset($parent[$cle])) {
					$parent[$cle] = [];
				}
				$parent = &$parent[$cle];
			}
			// On vérifie maintenant que la position est cohérente avec le parent
			if ($position < 0) {
				$position = 0;
			} elseif ($position > count($parent)) {
				$position = count($parent);
			}
		}
		// Et enfin on insère
		array_splice($parent, $position, 0, [$saisie]);
	}

	// On remet les options générales après avoir manipulé
	if (isset($options_generales)) {
		$saisies['options'] = $options_generales;
	}

	return $saisies;
}


/**
 * Duplique une saisie (ou groupe de saisies)
 * en placant la copie à la suite de la saisie d'origine.
 * Modifie automatiquement les identifiants des saisies.
 *
 * @param array        $saisies             Un tableau décrivant les saisies
 * @param unknown_type $id_ou_nom_ou_chemin L'identifiant unique ou le nom ou le chemin de la saisie a dupliquer
 *
 * @return array Retourne le tableau modifié des saisies
 */
function saisies_dupliquer($saisies, $id_ou_nom_ou_chemin) {
	// On récupère le contenu de la saisie à déplacer
	$saisie = saisies_chercher($saisies, $id_ou_nom_ou_chemin);
	if ($saisie) {
		list($clone) = saisies_transformer_noms_auto($saisies, [$saisie]);
		// insertion apres quoi ?
		$chemin_validation = saisies_chercher($saisies, $id_ou_nom_ou_chemin, true);
		// 1 de plus pour mettre APRES le champ trouve
		++$chemin_validation[count($chemin_validation) - 1];
		// On ajoute "copie" après le label du champs
		if (isset($clone['options']['label'])) {
			$clone['options']['label'] .= ' ' . _T('saisies:construire_action_dupliquer_copie');
		}

		// Création de nouveau identifiants pour le clone
		$clone = saisie_identifier($clone, true);

		$saisies = saisies_inserer($saisies, $clone, $chemin_validation);
	}

	return $saisies;
}

/**
 * Déplace une saisie existante autre part.
 *
 * @param array        $saisies             Un tableau décrivant les saisies
 * @param unknown_type $id_ou_nom_ou_chemin L'identifiant unique ou le nom ou le chemin de la saisie à déplacer
 * @param string       $ou
 *	- Le nom de la saisie devant laquelle on déplacera
 *	- OU le nom d'un conteneur entre crochets [conteneur] (et dans ce cas on déplace à la fin de conteneur)
 *	- OU le nom d'un conteneur entre crochets suivi d'un identifiant numérique entre crochets [conteneur][x] (et dans ce cas on déplace à la position x au sein du conteneur)
 * @param string $avant_ou_apres (optionel) : valeur possible : `'avant'` ou `'apres'`, pour inserer respectivent avant/après la saisie `$ou`
 * @return array Retourne le tableau modifié des saisies
 */
function saisies_deplacer($saisies, $id_ou_nom_ou_chemin, $ou, $avant_ou_apres = 'avant') {
	// Si le paramètre $avant_ou_apres est erronné, on arrête là
	if ($avant_ou_apres !== 'avant' && $avant_ou_apres !== 'apres') {
		return $saisies;
	}
	// On récupère le contenu de la saisie à déplacer
	$saisie = saisies_chercher($saisies, $id_ou_nom_ou_chemin);

	// Si on l'a bien trouvé
	if ($saisie) {
		// On cherche l'endroit où la déplacer
		// Si $ou est vide, c'est à la fin de la racine
		if (!$ou) {
			$saisies = saisies_supprimer($saisies, $id_ou_nom_ou_chemin);
			$chemin = [count($saisies)];
		} elseif (preg_match('/^\[(@?[\w]*)\](\[([\d])*\])*$/', $ou, $match)) {
			// Si l'endroit est entre crochet, c'est un conteneur
			$parent = $match[1];
			// Si dans les crochets il n'y a rien, on met à la fin du formulaire
			if (!$parent) {
				$saisies = saisies_supprimer($saisies, $id_ou_nom_ou_chemin);
				$chemin = [count($saisies)];
			} elseif (saisies_chercher($saisies, $parent, true)) {
				// Sinon on vérifie que ce conteneur existe
				// S'il existe on supprime la saisie et on recherche la nouvelle position
				$saisies = saisies_supprimer($saisies, $id_ou_nom_ou_chemin);
				$parent = saisies_chercher($saisies, $parent, true);
				//Si [fieldset], inserer à la fin du fieldset, si [fieldset][X] inserer à la position X dans le fieldset
				if (isset($match[3])) {
					$position = $match[3];
				} else {
					$position = 10000000;
				}
				$chemin = array_merge($parent, ['saisies', $position]);
			} else {
				$chemin = false;
			}
		} else {
			// Sinon ça sera devant un champ
			// On vérifie que le champ existe
			if (saisies_chercher($saisies, $ou, true)) {
				// S'il existe on supprime la saisie
				$saisies = saisies_supprimer($saisies, $id_ou_nom_ou_chemin);
				// Et on recherche la nouvelle position qui n'est plus forcément la même maintenant qu'on a supprimé une saisie
				$chemin = saisies_chercher($saisies, $ou, true);
			} else {
				$chemin = false;
			}
		}

		// Si seulement on a bien trouvé un nouvel endroit où la placer, alors on déplace
		if ($chemin) {
			if ($avant_ou_apres === 'avant') {
				$saisies = saisies_inserer($saisies, $saisie, $chemin);
			} else {
				$saisies = saisies_inserer_apres($saisies, $saisie, $chemin);
			}
		}
	}

	return $saisies;
}

/**
 * Déplacer une saisie existante avant une autre
 * @param array        $saisies             Un tableau décrivant les saisies
 * @param unknown_type $id_ou_nom_ou_chemin L'identifiant unique ou le nom ou le chemin de la saisie à déplacer
 * @param string       $ou la saisie devant laquelle déplacer
 * @return array $string
 * @use saisie_deplacer()
 **/
function saisies_deplacer_avant($saisies, $id_ou_nom_ou_chemin, $ou) {
	return saisies_deplacer($saisies, $id_ou_nom_ou_chemin, $ou, 'avant');
}


/**
 * Déplacer une saisie existante après une autre
 * @param array        $saisies             Un tableau décrivant les saisies
 * @param unknown_type $id_ou_nom_ou_chemin L'identifiant unique ou le nom ou le chemin de la saisie à déplacer
 * @param string       $ou la saisie devant laquelle déplacer
 * @return array $string
 * @use saisie_deplacer()
 **/
function saisies_deplacer_apres($saisies, $id_ou_nom_ou_chemin, $ou) {
	return saisies_deplacer($saisies, $id_ou_nom_ou_chemin, $ou, 'apres');
}

/**
 * Modifie une saisie.
 *
 * @param array        $saisies             Un tableau décrivant les saisies
 * @param array|string $id_ou_nom_ou_chemin L'identifiant unique ou le nom ou le chemin de la saisie à modifier
 * @param array        $modifs              Le tableau des modifications à apporter à la saisie
 * @param bool				 $fusion							True si on veut simplifier rajouter des choses, sans tout remplacer
 * @return array Retourne le tableau décrivant les saisies, mais modifié
 */
function saisies_modifier($saisies, $id_ou_nom_ou_chemin, $modifs, $fusion = false) {
	if ($chemin = saisies_chercher($saisies, $id_ou_nom_ou_chemin, true)) {
		$position = array_pop($chemin);
		$parent = &$saisies;
		foreach ($chemin as $cle) {
			$parent = &$parent[$cle];
		}

		// On récupère le type tel quel
		$modifs['saisie'] = $parent[$position]['saisie'];
		// On récupère le nom s'il n'y est pas
		if (!isset($modifs['options']['nom'])) {
			$modifs['options']['nom'] = $parent[$position]['options']['nom'];
		}
		// On récupère les enfants tels quels s'il n'y a pas des enfants dans la modif
		if (
			!isset($modifs['saisies'])
			&& isset($parent[$position]['saisies'])
			&& is_array($parent[$position]['saisies'])
		) {
			$modifs['saisies'] = $parent[$position]['saisies'];
		}
		// Pareil pour les vérifications
		if (
			!isset($modifs['verifier'])
			&& is_array($parent[$position]['verifier'] ?? '')
		) {
			$modifs['verifier'] = $parent[$position]['verifier'];
		}

		// Si 'nouveau_type_saisie' est donnee, c'est que l'on souhaite
		// peut être changer le type de saisie !
		// Note : on maintient encore la syntaxe historique qui met cela dans 'options', mais elle n'est pas nécessaire et disparaitra en 6.0
		if ($type = ($modifs['nouveau_type_saisie'] ?? '')) {
			$modifs['saisie'] = $type;
			unset($modifs['nouveau_type_saisie']);
		} elseif ($type = ($modifs['options']['nouveau_type_saisie'] ?? '')) {
			$modifs['saisie'] = $type;
			unset($modifs['options']['nouveau_type_saisie']);
			trigger_error('Dans la fonction saisies_modifier, nouveau_type_saisie doit être appelé à la racine de $modifs. L\'appel dans $options est deprécié et sera supprimé en v6.', E_USER_DEPRECATED);
			spip_log('Dans la fonction saisies_modifier, nouveau_type_saisie doit être appelé à la racine de $modifs. L\'appel dans $options est deprécié et sera supprimé en v6.', 'deprecated_saisies');
		}
		// On remplace tout
		if (!$fusion) {
			$parent[$position] = $modifs;
		} else {
			$parent[$position] = array_replace_recursive($parent[$position], $modifs);
		}
	}

	return $saisies;
}

/**
 * Transforme tous les noms du formulaire avec un preg_replace.
 *
 * @param array  $saisies      Un tableau décrivant les saisies
 * @param string $masque       Ce que l'on doit chercher dans le nom
 * @param string $remplacement Ce par quoi on doit remplacer
 *
 * @return array               Retourne le tableau modifié des saisies
 */
function saisies_transformer_noms($saisies, $masque, $remplacement) {
	return saisies_transformer_option($saisies, 'nom', $masque, $remplacement);
}

/**
 * Transforme tous les noms en les encapsulant avec un préfixe.
 *
 * Cela permet d'avoir toutes les valeurs postées dans un unique tableau.
 * Après transformation, on pourra faire `_request(<prefixe>)` pour les récupérer.
 *
 * Utilisation possible : on mélange les saisies de plusieurs formulaires,
 * et on a dans ce cas besoin de cloisonner les valeurs.
 *
 * @example Avant/après
 * - bidule        → prefixe[bidule]
 * - machin[chose] → prefixe[machin][chose]
 *
 * @param array $saisies
 *   Un tableau décrivant les saisies
 * @param string $prefixe
 *   Préfixe
 * @param boolean $recursif
 *   Pour procéder récursivement dans les fieldsets
 * @return array
 */
function saisies_encapsuler_noms(array $saisies, string $prefixe, bool $recursif = true) {
	$saisies = saisies_mapper_option(
		$saisies,
		'nom',
		function ($nom) use ($prefixe) {
			// Soit c'est déjà un tableau : machin[truc]
			if (($pos = strpos($nom, '[')) !== false) {
				$nom = "{$prefixe}[" . substr_replace($nom, ']', $pos, 0);
				// Soit c'est un nom simple : machin
			} else {
				$nom = "{$prefixe}[{$nom}]";
			}
			return $nom;
		},
		[],
		$recursif
	);

	return $saisies;
}

/**
 * Transforme toutes les options textuelles d'un certain nom, avec un preg_replace.
 *
 * @param $saisies
 *     Tableau décrivant les saisies
 * @param $option
 *     Nom de l'option à transformer (par ex "nom", ou "afficher_si"), ou tableau
 *     Note : si l'option n'existe pas, elle est automatiquement mise à `''` avant toute transformation
 * @param $masque
 *     Ce que l'on doit chercher dans le texte
 * @param $remplacement
 *     Ce par quoi on doit remplacer
 * @param bool $recursif
 * @return array
 * 		Retourne le tableau modifié des saisies
 */
function saisies_transformer_option($saisies, $option, $masque, $remplacement, $recursif = true) {
	return saisies_mapper_option(
		$saisies,
		$option,
		function ($valeur, $masque, $remplacement) {
			if (!is_string($valeur)) {
				$valeur = '';
			}
			return preg_replace($masque, $remplacement, $valeur);
		},
		[$masque, $remplacement],
		$recursif
	);
}


/**
 * Modifie toutes les options d'un certain nom, avec une fonction de rappel.
 *
 * @param array $saisies
 *     Tableau décrivant les saisies
 * @param string|array $options
 *     Nom de l'option à transformer (par ex "nom", ou "afficher_si")
 *     Ou tableau de noms
 *     Note : si l'option n'existe pas, elle est automatiquement mise à `''` avant toute transformation
 * @param string $callback
 *		 Nom de la fonction à appliquer
 * @param array $args
 *		 Arguments de la fonction de rappel
 *		 La valeur de l'option est passée automatiquement en premier
 * @param bool $recursif=True
 * @return array
 * 		Retourne le tableau modifié des saisies
 */
function saisies_mapper_option($saisies, $options, $callback, $args = [], $recursif = true) {
	if (!is_array($options)) {
		$options = [$options];
	}
	if (is_array($saisies)) {
		foreach ($saisies as $cle => $saisie) {
			foreach ($options as $option) {
				$saisies[$cle]['options'][$option] = call_user_func_array($callback, array_merge([$saisies[$cle]['options'][$option] ?? ''], $args));
			}
			// On parcourt récursivement toutes les saisies enfants
			if (is_array($saisie['saisies'] ?? '') && $recursif) {
				$saisies[$cle]['saisies'] = saisies_mapper_option($saisie['saisies'], $option, $callback, $args, $recursif);
			}
		}
	}

	return $saisies;
}



/**
 * Supprime toutes les options d'un certain nom.
 *
 * @param $saisies
 *     Tableau décrivant les saisies
 * @param $option
 *     Nom de l'option à supprimer (par ex "nom", ou "afficher_si")
 * @param bool $recursif
 * @return array
 * 		Retourne le tableau modifié des saisies
 */
function saisies_supprimer_option($saisies, $option, $recursif = true) {
	if (is_array($saisies)) {
		foreach ($saisies as $cle => $saisie) {
			unset($saisies[$cle]['options'][$option]);

			// On parcourt récursivement toutes les saisies enfants
			if (is_array($saisie['saisies'] ?? '') && $recursif) {
				$saisies[$cle]['saisies'] = saisies_supprimer_option($saisie['saisies'], $option);
			}
		}
	}

	return $saisies;
}

/**
 * Modifie les vérifications des saisies avec une fonction de rappel
 *
 * @param array $saisies
 *     Tableau décrivant les saisies
 * @param string $callback
 *		 Nom de la fonction à appliquer, elle doit retourner la nouvelle version du tableau de vérification
 *		 1. Les vérifs telles que disponibles actuellement sont passées en premier
 *		  (on modifie donc l'ensemble des vérifications, charge à la fonction de rappel de choisir quelle vérification modifier).
 *		  Toutefois le tableau de vérification est normalisé pour utiliser uniquement la "nouvelle" syntaxe permettant d'avoir plusieurs vérifications
 *		 2. La description complète de la saisie est passée en second
 * @param array $args
 *		 Arguments supplémentaires passées à la fonction de rappel
 * @param bool $recursif=True
 * @return array
 * 		Retourne le tableau modifié des saisies
 */
function saisies_mapper_verifier($saisies, $callback, $args = [], $recursif = true) {
	if (is_array($saisies)) {
		foreach ($saisies as &$saisie) {
			// On parcourt récursivement toutes les saisies enfants
			if (is_array($saisie['saisies'] ?? '')) {
				if ($recursif) {
				$saisie['saisies'] = saisies_mapper_verifier($saisie['saisies'], $callback, $args, $recursif);
				}
			} else {
				$verifier = $saisie['verifier'] ?? [];
				// Normalisation
				if (isset($verifier['type'])) {
					$verifier = [$verifier];
				}
				$saisie['verifier'] = call_user_func_array($callback, array_merge([$verifier], [$saisie], $args));
			}
		}
	}

	return $saisies;
}
/**
 * Transforme les noms d'une liste de saisies pour qu'ils soient
 * uniques dans le formulaire donné.
 *
 * @param array $formulaire  Le formulaire à analyser
 * @param array $saisies     Un tableau décrivant les saisies.
 *
 * @return array
 *     Retourne le tableau modifié des saisies
 */
function saisies_transformer_noms_auto($formulaire, $saisies) {
	if (is_array($saisies)) {
		foreach ($saisies as $cle => $saisie) {
			$saisies[$cle]['options']['nom'] = saisies_generer_nom($formulaire, $saisie['saisie']);
			// il faut prendre en compte dans $formulaire les saisies modifiees
			// sinon on aurait potentiellement 2 champs successifs avec le meme nom.
			// on n'ajoute pas les saisies dont les noms ne sont pas encore calculees.
			$new = $saisies[$cle];
			unset($new['saisies']);
			$formulaire[] = $new;

			if (is_array($saisie['saisies'] ?? '')) {
				$saisies[$cle]['saisies'] = saisies_transformer_noms_auto($formulaire, $saisie['saisies']);
			}
		}
	}

	return $saisies;
}

/**
 * Insère du HTML au début ou à la fin d'une saisie.
 *
 * @param array  $saisie    La description d'une seule saisie
 * @param string $insertion Du code HTML à insérer dans la saisie
 * @param string $ou        L'endroit où insérer le HTML : "debut" ou "fin"
 *
 * @return array            Retourne la description de la saisie modifiée
 */
function saisies_inserer_html($saisie, $insertion, $ou = 'fin') {
	if (!in_array($ou, ['debut', 'fin'])) {
		$ou = 'fin';
	}

	if ($ou == 'debut') {
		$saisie['options']['inserer_debut'] =
			$insertion . (isset($saisie['options']['inserer_debut']) ? $saisie['options']['inserer_debut'] : '');
	} elseif ($ou == 'fin') {
		$saisie['options']['inserer_fin'] =
			(isset($saisie['options']['inserer_fin']) ? $saisie['options']['inserer_fin'] : '') . $insertion;
	}

	return $saisie;
}

/**
 * Ajoute l'option onglet aux fieldset de premier niveau dans un tableau de $saisie
 * Ajoute également un identifiant unique, éventuellement préfixé
 * @param array $saisies
 * @param string $identifiant_prefixe
 * @param bool $vertical
 * @return array $saisies modifiées
 **/
function saisies_fieldsets_en_onglets($saisies, $identifiant_prefixe = '', $vertical = false) {
	$options = $saisies['options'] ?? [];
	unset($saisies['options']);
	foreach ($saisies as &$saisie) {
		if ($saisie['saisie'] == 'fieldset') {
			$saisie['options']['onglet'] = 'on';
			if ($vertical) {
				$saisie['options']['onglet_vertical'] = 'on';
			}
			$saisie['identifiant'] = $identifiant_prefixe . '_' . saisie_nom2classe($saisie['options']['nom']);
		}
	}
	if ($options) {
		$saisies['options'] = $options;
	}
	return $saisies;
}

/**
 * Prend un tableau de saisies
 * Enlève les saisies qui n'ont rien dans _request
 * Ou dont l'ensemble des sous-saisies sont vides
 * @param array $saisies
 * @param optional $tableau (tableau pour chercher dans request)
 * @return array
 **/
function saisies_supprimer_sans_reponse(array $saisies, ?array $tableau = null): array {
	$saisies = saisies_supprimer_callback($saisies, function ($saisie) use ($tableau): bool {
		// Si sous saisie, on garde, la fonction saisies_supprimer_callback fera les tests sous chaque sous saisies
		return saisies_saisie_possede_reponse($saisie, $tableau);
	});
	return $saisies;
}

/**
 * Indique si une saisie possède une réponse
 * @param array $saisie la saisie individuelle
 * @param null|array $tableau (tableau pour chercher les valeurs, à défaut request
 * @return bool
**/
function saisies_saisie_possede_reponse(array $saisie, $tableau = null) {
	// Si sous saisie, on fait récursivement
	if (isset($saisie['saisies'])) {
		foreach ($saisie['saisies'] as $s => $ss) {
			if (saisies_saisie_possede_reponse($ss, $tableau)) {
				return true;
			}
		}
		return false;
	}

	// Les saisies de type fichier
	if (saisies_saisie_est_fichier($saisie)) {
		$valeur = saisies_request_from_FILES($saisie['options']['nom']);
	} else {
		$valeur = saisies_request($saisie['options']['nom'], $tableau);
	}

	if (is_string($valeur)) {
		$valeur = vider_date($valeur);
	}
	if ($valeur === '' || is_null($valeur)) {// Notons la très stricte égalité pour ne pas invalider une saisie dont la valeur serait 0
		return false;
	} else {
		return true;
	}
}

/**
 * Prend un tableau de saisies
 * Enlève les saisies dépubliées
 * Ou dont l'ensemble des sous-saisies sont dépubliés
 * @param array $saisies
 * @return array
 **/
function saisies_supprimer_depublie(array $saisies): array {
	$saisies = saisies_supprimer_callback($saisies, function (array $saisie): bool {
		return !($saisie['options']['depublie'] ?? false);
	});
	return $saisies;
}



/**
 * Prend un tableau de saisies
 * Enlève les saisies dépubliées
 * Et qui n'ont pas de réponse
 * Ou dont l'ensemble des saisies répondent au critère ci-dessus
 * @param array $saisies
 * @param array|null $reponses null pour chercher dans _request
 * @return array
 **/
function saisies_supprimer_depublie_sans_reponse(array $saisies,  $reponses = null): array {
	$saisies = saisies_supprimer_callback($saisies, function (array $saisie) use ($reponses): bool {
		return (!($saisie['options']['depublie'] ?? false)) || saisies_saisie_possede_reponse($saisie, $reponses);
	});
	return $saisies;
}


/**
 * Prend un tableau de saisies
 * supprime les saisies selon une fonction de rappel
 * @param array $saisies tableau de saisies
 * @param callable $callback fonction de rappel
 *  la fonction doit renvoyer `true` si on garde, `false` sinon
 * @return array
**/
function saisies_supprimer_callback(array $saisies, callable $callback): array {
	// On gère les options de saisies
	if (isset($saisies['options'])) {
		$options_generales = $saisies['options'];
		unset($saisies['options']);
	}
	foreach ($saisies as $key => &$saisie) {
		// Cas 1: c'est une saisies avec sous_saisies, dans ce cas on analyse d'abord les sous_saisies, puis on supprime le cas échéant
		if (isset($saisie['saisies'])) {
			$saisie['saisies'] = saisies_supprimer_callback($saisie['saisies'], $callback);
			if (empty($saisie['saisies'])) {
				unset($saisies[$key]);
			}
		}
		// Dans tous les cas, on vérifie
		if (!$callback($saisie)) {
			unset($saisies[$key]);
		}
	}
	$saisies = array_values($saisies);
	if (isset($options_generales)) {
		$saisies['options'] = $options_generales;
	}
	return $saisies;
}

/**
 * Transforme un tableau de saisies en englobant le tout dans un fieldset
 * utilisé pour la prévisualisation
 * @param array $saisies
 * @param array $options du fieldset
 * @return array
 **/
function saisies_wrapper_fieldset(array $saisies, array $options): array {
	$options_globales = $saisies['options'] ?? [];
	unset($saisies['options']);
	$saisies = [
		[
			'saisie' => 'fieldset',
			'options' => $options,
			'saisies' => $saisies
		]
	];
	if ($options_globales) {
		$saisies['options'] = $options_globales;
	}
	return $saisies;
}
