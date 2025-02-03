<?php

/**
 * Gestion de listes des saisies.
 *
 * @return SPIP\Saisies\Listes
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * les saisies "à plat" classées par identifiant unique.
 *
 * @param array $contenu        Le contenu d'un formulaire
 * @param bool  $avec_conteneur Indique si on renvoie aussi les saisies ayant des enfants, comme les fieldsets
 *
 * @return array Un tableau avec uniquement les saisies
 */
function saisies_lister_par_identifiant($contenu, $avec_conteneur = true) {
	$saisies = [];

	if (is_array($contenu)) {
		foreach ($contenu as $ligne) {
			if (is_array($ligne)) {
				$enfants_presents = is_array($ligne['saisies'] ?? '');
				if (array_key_exists('saisie', $ligne) && (!$enfants_presents || $avec_conteneur) && isset($ligne['identifiant'])) {
					$saisies[$ligne['identifiant']] = $ligne;
				}
				if ($enfants_presents) {
					$saisies = array_merge($saisies, saisies_lister_par_identifiant($ligne['saisies'], $avec_conteneur));
				}
			}
		}
	}

	return $saisies;
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * les saisies "à plat" classées par nom.
 *
 * @param array $contenu        Le contenu d'un formulaire
 * @param bool  $avec_conteneur Indique si on renvoie aussi les saisies ayant des enfants, comme les fieldset
 *
 * @return array Un tableau avec uniquement les saisies
 */
function saisies_lister_par_nom($contenu, $avec_conteneur = true) {
	$saisies = [];
	if (is_array($contenu)) {
		foreach ($contenu as $ligne) {
			if (is_array($ligne)) {
				if (
					array_key_exists('saisie', $ligne)
					&& (!is_array($ligne['saisies'] ?? '') || $avec_conteneur)
					&& isset($ligne['options'])
				) {
					$saisies[$ligne['options']['nom']] = $ligne;
				}
				if (is_array($ligne['saisies'] ?? '')) {
					$saisies = array_merge($saisies, saisies_lister_par_nom($ligne['saisies'], $avec_conteneur));
				}
			}
		}
	}

	return $saisies;
}

/**
 * Liste les saisies en parcourant tous les niveau de la hiérarchie, et en excluant les saisies ayant des sous-saisies
 * @param array  $saisies Liste de saisies
 * @return liste de ces saisies triées selon l'ordre de déclaration initiale
 */
function saisies_lister_finales($saisies) {
	$saisies_retour = [];
	foreach ($saisies as $identifiant => $saisie) {
		if (isset($saisie['saisies'])) {
			$saisies_retour = array_merge($saisies_retour, saisies_lister_finales($saisie['saisies']));
		} elseif (isset($saisie['saisie'])) {// pour ne pas avoir les options gloables des saisies
			$saisies_retour[] = $saisie;
		}
	}
	return $saisies_retour;
}
/**
 * Liste les saisies ayant une option X
 * # saisies_lister_avec_option('sql', $saisies);.
 *
 *
 * @param string $option  Nom de l'option cherchée
 * @param array  $saisies Liste de saisies
 * @param string $tri     tri par défaut des résultats (s'ils ne sont pas deja triés) ('nom', 'identifiant')
 *
 * @return liste de ces saisies triees par nom ayant une option X définie
 */
function saisies_lister_avec_option($option, $saisies, $tri = 'nom') {
	$saisies_option = [];

	// tri par nom si ce n'est pas le cas
	$s = array_keys($saisies);
	if (is_int(array_shift($s))) {
		$trier = 'saisies_lister_par_' . $tri;
		$saisies = $trier($saisies);
	}

	foreach ($saisies as $nom_ou_id => $saisie) {
		if ($saisie['options'][$option] ?? '') {
			$saisies_option[$nom_ou_id] = $saisie;
		}
	}

	return $saisies_option;
}

/**
 * Liste les saisies ayant une definition SQL.
 *
 * @param array  $saisies liste de saisies
 * @param string $tri     tri par défaut des résultats (s'ils ne sont pas deja triés) ('nom', 'identifiant')
 *
 * @return liste de ces saisies triees par nom ayant une option sql définie
 */
function saisies_lister_avec_sql($saisies, $tri = 'nom') {
	return saisies_lister_avec_option('sql', $saisies, $tri);
}

/**
 * Liste les saisies d'un certain type.
 *
 * @example `$saisies_date = saisies_lister_avec_type($saisies, 'date')`
 *
 * @param array $saisies liste de saisies
 * @param string|array $type Type de la saisie, ou tableau de types
 * @param string $tri tri par défaut des résultats (s'ils ne sont pas deja triés) ('nom')
 * @param bool avec_conteneur faut-il conserver l'arbo?
 *
 * @return liste de ces saisies triees par nom
 */
function saisies_lister_avec_type($saisies, $type, $tri = 'nom', $avec_conteneur = false) {
	if (!is_array($type)) {
		$type = [$type];
	}
	unset($saisies['options']);//Pas les options globales du formulaire
	$saisies_type = [];

	// tri par nom si ce n'est pas le cas
	$s = array_keys($saisies);
	if (is_int(array_shift($s)) && $tri && !$avec_conteneur) {
		$trier = 'saisies_lister_par_' . $tri;
		$saisies = $trier($saisies);
	}

	foreach ($saisies as $nom_ou_id => $saisie) {
		if (in_array($saisie['saisie'], $type)) {
			if ($avec_conteneur && isset($saisie['saisies'])) {
				$saisie['saisies'] = saisies_lister_avec_type($saisie['saisies'], $type, $tri, $avec_conteneur);
			}
			$saisies_type[$nom_ou_id] = $saisie;
		}
	}

	return $saisies_type;
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * les saisies "à plat" classées par type de saisie.
 * $saisie['input']['input_1'] = $saisie.
 *
 * Attention : ne sont retournées que les saisies finales (qui ne contiennent pas de sous-saisies).
 * @param array $contenu Le contenu d'un formulaire
 *
 * @return array Un tableau avec uniquement les saisies
 */
function saisies_lister_par_type($contenu) {
	$saisies = [];

	if (is_array($contenu)) {
		foreach ($contenu as $ligne) {
			if (is_array($ligne)) {
				if (array_key_exists('saisie', $ligne) && (!isset($ligne['saisies']))) {
					$saisies[ $ligne['saisie'] ][ $ligne['options']['nom'] ] = $ligne;
				}
				if (is_array($ligne['saisies'] ?? '')) {
					$saisies = array_merge_recursive($saisies, saisies_lister_par_type($ligne['saisies']));
				}
			}
		}
	}

	return $saisies;
}

/**
 * Liste les saisies par étapes s'il y en a
 *
 * @param array $saisies
 * 		Liste des saisies
 * @param bool $check_only = false, si true, se contente de vérifier si on gère les étapes, mais ne construit pas le tableau d'étape
 * @return array|bool
 * 		Retourne un tableau associatif par numéro d'étape avec pour chacune leurs saisies (ou bien true si check_only est à true), false si pas d'étapes
 * 		Retourne un tableau associatif "etape_xxx" => "contenu de l'étape" avec pour chacune leurs saisies, false si pas d'étapes
 * 		Ajoute si besoin une étape N+1 "Récapitulatif"
 * 		Les noms des étapes sont automatiquement passés dans _T_ou_typo
 */
function saisies_lister_par_etapes($saisies, $check_only = false, ?array $env = []) {
	$saisies_etapes = false;
	$etapes = 0;
	$previsualisation_etape = ($saisies['options']['previsualisation_mode'] ?? '') === 'etape';

	if (($saisies['options']['etapes_activer'] ?? '') || $previsualisation_etape) {
		if (isset($saisies['options']['etapes_ignorer_recapitulatif'])) {
			$ignorer_recapitulatif = $saisies['options']['etapes_ignorer_recapitulatif'];
		} else {
			$ignorer_recapitulatif = false;
		}

		// Un premier parcourt pour compter les étapes
		unset($saisies['options']);
		foreach ($saisies as $cle => $saisie) {
			if (is_array($saisies) && $saisie['saisie'] === 'fieldset') {
				$etapes++;
			}
		}

		// Seulement s'il y a au moins deux étapes ou que l'on est en mode prévisu sur du mono étape
		if ($etapes > 1 || $previsualisation_etape) {
			if ($check_only) {
				return true;
			}
			$saisies_etapes = [];
			$compteur_etape = 0;

			if ($previsualisation_etape) {
				$saisies = saisies_wrapper_fieldset(
					$saisies,
					[
						'nom' => '@saisies_remplissage',
						'label' => '<:saisies:etapes_remplissage_label:>'
					]
				);
			}

			// On reparcourt pour lister les saisies
			foreach ($saisies as $cle => $saisie) {
				// Si c'est un groupe, on ajoute son contenu à l'étape
				if (($saisie['saisie'] ?? '') === 'fieldset') {
					if (!saisie_editable($saisie, $env, true)) {
						continue;
					}
					$compteur_etape++;
					// S'il y a eu des champs hors groupe avant, on fusionne
					if (isset($saisies_etapes["etape_$compteur_etape"]['saisies'])) {
						$saisies_precedentes = $saisies_etapes["etape_$compteur_etape"]['saisies'];
						$saisies_etapes["etape_$compteur_etape"] = $saisie;
						$saisies_etapes["etape_$compteur_etape"]['saisies'] = array_merge($saisies_precedentes, $saisie['saisies']);
					}
					else {
						$saisies_etapes["etape_$compteur_etape"] = $saisie;
					}
					$saisies_etapes["etape_$compteur_etape"]['options']['label'] = _T_ou_typo($saisies_etapes["etape_$compteur_etape"]['options']['label']);
				}
				// Sinon si champ externe à un groupe, on l'ajoute à toutes les étapes
				elseif (isset($saisie['saisie'])) {
					for ($e = 1; $e <= $etapes; $e++) {
						if (!isset($saisies_etapes["etape_$e"]['saisies'])) {
							$saisies_etapes["etape_$e"] = ['saisies' => []];
						}
						array_push($saisies_etapes["etape_$e"]['saisies'], $saisie);
					}
				}
			}
			//// Ajouter l'étape recapitulatif
			if (!$ignorer_recapitulatif) {
				$compteur_etape++;
				if (test_plugin_actif('cvtupload')) {
					include_spip('inc/cvtupload');
					$valeurs = array_merge($_POST, cvtupload_vue_from_FILES());
				} else {
					$valeurs = $_POST;
				}
				$saisies_etapes["etape_$compteur_etape"] = [
					'saisie' => 'fieldset',
					'options' => [
						'nom' => '@saisies_recapitulatif',
						'label' => _T('saisies:etapes_recapitulatif_label')
					],
					'saisies' => [],
					'valeurs' => $valeurs,
				];
			}
		}
	}
	return $saisies_etapes;
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * une liste des noms des champs du formulaire.
 *
 * @param array $contenu        Le contenu d'un formulaire
 * @param bool  $avec_conteneur Indique si on renvoie aussi les saisies ayant des enfants, comme les fieldset
 *
 * @return array Un tableau listant les noms des champs
 */
function saisies_lister_champs($contenu, $avec_conteneur = true) {
	$saisies = saisies_lister_par_nom($contenu, $avec_conteneur);

	return array_keys($saisies);
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * une liste des labels humains des vrais champs du formulaire (par nom)
 *
 * @param array $contenu        Le contenu d'un formulaire
 * @param bool  $avec_conteneur Indique si on renvoie aussi les saisies ayant des enfants, comme les fieldset
 *
 * @return array Un tableau listant les labels humains des champs
 */
function saisies_lister_labels($contenu, $avec_conteneur = false) {
	$saisies = saisies_lister_par_nom($contenu, $avec_conteneur);

	$labels = [];
	foreach ($saisies as $nom => $saisie) {
		if (isset($saisie['options']['label'])) {
			$labels[$nom] = $saisie['options']['label'];
		}
	}

	return $labels;
}

/**
 * A utiliser dans une fonction charger d'un formulaire CVT,
 * cette fonction renvoie le tableau de contexte correspondant
 * de la forme $contexte['nom_champ'] = ''.
 *
 * @param array $contenu Le contenu d'un formulaire (un tableau de saisies)
 *
 * @return array Un tableau de contexte
 */
function saisies_charger_champs($contenu) {
	return array_fill_keys(saisies_lister_champs($contenu, false), null);
}

/**
 * Prend la description complète du contenu d'un formulaire et retourne
 * une liste des valeurs par défaut des champs du formulaire.
 *
 * @param array $contenu Le contenu d'un formulaire
 *
 * @return array Un tableau renvoyant la valeur par défaut de chaque champs
 */
function saisies_lister_valeurs_defaut($contenu) {
	$contenu = saisies_lister_par_nom($contenu, false);
	$defauts = [];

	foreach ($contenu as $nom => $saisie) {
		// Si le nom du champ est un tableau indexé, il faut parser !
		$nom = saisie_nom2name($nom);
		if (preg_match('/([\w]+)((\[[\w]+\])+)/', $nom, $separe)) {
			$nom = $separe[1];
			// Dans ce cas on ne récupère que le nom,
			// la valeur par défaut du tableau devra être renseigné autre part
			$defauts[$nom] = [];
		}
		else {
			$defauts[$nom] = $saisie['options']['defaut'] ?? '';
			$champ_defaut_session = $saisie['options']['defaut_session'] ?? '';
			if ($champ_defaut_session) {
				include_spip('inc/session');
					$valeur_session = session_get($champ_defaut_session);
					if ($valeur_session) {
						$defauts[$nom] = $valeur_session;
					}
			}
		}
	}

	return $defauts;
}

/**
 * Compare deux tableaux de saisies pour connaitre les différences.
 *
 * @param array  $saisies_anciennes Un tableau décrivant des saisies
 * @param array  $saisies_nouvelles Un autre tableau décrivant des saisies
 * @param bool   $avec_conteneur    Indique si on veut prendre en compte dans la comparaison les conteneurs comme les fieldsets
 * @param string $tri               Comparer selon quel tri ? 'nom' / 'identifiant'
 *
 * @return array Retourne le tableau des saisies supprimées, ajoutées et modifiées
 */
function saisies_comparer($saisies_anciennes, $saisies_nouvelles, $avec_conteneur = true, $tri = 'nom') {
	$trier = "saisies_lister_par_$tri";
	$saisies_anciennes = $trier($saisies_anciennes, $avec_conteneur);
	$saisies_nouvelles = $trier($saisies_nouvelles, $avec_conteneur);

	// Les saisies supprimées sont celles qui restent dans les anciennes quand on a enlevé toutes les nouvelles
	$saisies_supprimees = array_diff_key($saisies_anciennes, $saisies_nouvelles);
	// Les saisies ajoutées, c'est le contraire
	$saisies_ajoutees = array_diff_key($saisies_nouvelles, $saisies_anciennes);
	// Il reste alors les saisies qui ont le même nom
	$saisies_restantes = array_intersect_key($saisies_anciennes, $saisies_nouvelles);
	// Dans celles-ci, celles qui sont modifiées sont celles dont la valeurs est différentes
	$saisies_modifiees = array_udiff(array_diff_key($saisies_nouvelles, $saisies_ajoutees), $saisies_restantes, 'saisies_comparer_rappel');
	#$saisies_modifiees = array_udiff($saisies_nouvelles, $saisies_restantes, 'saisies_comparer_rappel');
	// Et enfin les saisies qui ont le même nom et la même valeur
	$saisies_identiques = array_diff_key($saisies_restantes, $saisies_modifiees);

	return [
		'supprimees' => $saisies_supprimees,
		'ajoutees' => $saisies_ajoutees,
		'modifiees' => $saisies_modifiees,
		'identiques' => $saisies_identiques,
	];
}

/**
 * Compare deux saisies et indique si elles sont égales ou pas.
 *
 * @param array $a Une description de saisie
 * @param array $b Une autre description de saisie
 *
 * @return int Retourne 0 si les saisies sont identiques, 1 sinon.
 */
function saisies_comparer_rappel($a, $b) {
	if ($a === $b) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Compare deux tableaux de saisies pour connaitre les différences
 * en s'appuyant sur les identifiants de saisies.
 *
 * @see saisies_comparer()
 *
 * @param array $saisies_anciennes Un tableau décrivant des saisies
 * @param array $saisies_nouvelles Un autre tableau décrivant des saisies
 * @param bool  $avec_conteneur    Indique si on veut prendre en compte dans la comparaison
 *                                 les conteneurs comme les fieldsets
 *
 * @return array Retourne le tableau des saisies supprimées, ajoutées et modifiées
 */
function saisies_comparer_par_identifiant($saisies_anciennes, $saisies_nouvelles, $avec_conteneur = true) {
	return saisies_comparer($saisies_anciennes, $saisies_nouvelles, $avec_conteneur, 'identifiant');
}

/**
 * Quelles sont les saisies qui se débrouillent toutes seules, sans le _base commun.
 *
 * @return array Retourne un tableau contenant les types de saisies qui ne doivent pas utiliser le _base.html commun
 */
function saisies_autonomes() {
	$saisies_autonomes = pipeline(
		'saisies_autonomes',
		[
			'fieldset',
			'conteneur_inline',
			'hidden',
			'destinataires',
			'explication',
			'champ',
		]
	);

	return $saisies_autonomes;
}


/**
 * Cherche une saisie par son id, son nom ou son chemin et renvoie soit la saisie, soit son chemin
 *
 * @param array $saisies Un tableau décrivant les saisies
 * @param array|string $id_ou_nom_ou_chemin L'identifiant ou le nom de la saisie à chercher ou le chemin sous forme d'une liste de clés
 * @param bool $retourner_chemin Indique si on retourne non pas la saisie mais son chemin
 * @return array Retourne soit la saisie, soit son chemin, soit null
 */
function saisies_chercher($saisies, $id_ou_nom_ou_chemin, $retourner_chemin = false) {
	unset($saisies['options']);
	if (is_array($saisies) && $id_ou_nom_ou_chemin) {
		if (is_string($id_ou_nom_ou_chemin)) {
			$nom = $id_ou_nom_ou_chemin;
			// identifiant ? premier caractere @
			$id = ($nom[0] == '@');

			foreach ($saisies as $cle => $saisie) {
				$chemin = [$cle];
				// notre saisie est la bonne ?
				if ($id) {
					$nom_cette_saisie = $saisie['identifiant'] ?? '';
				} else {
					$nom_cette_saisie = $saisie['options']['nom'] ?? '';
				}
				if ($nom === $nom_cette_saisie) {
					return $retourner_chemin ? $chemin : $saisie;
					// sinon a telle des enfants ? et si c'est le cas, cherchons dedans
				} elseif (
					is_array($saisie['saisies'] ?? '') && ($saisie['saisies'] ?? [])
					&& ($retour = saisies_chercher($saisie['saisies'], $nom, $retourner_chemin))
				) {
					return $retourner_chemin ? array_merge($chemin, ['saisies'], $retour) : $retour;
				}
			}
		} elseif (is_array($id_ou_nom_ou_chemin)) {
			$chemin = $id_ou_nom_ou_chemin;
			$saisie = $saisies;

			// On vérifie l'existence quand même
			foreach ($chemin as $cle) {
				if (isset($saisie[$cle])) {
					$saisie = $saisie[$cle];
				} else {
					return null;
				}
			}

			// Si c'est une vraie saisie
			if (($saisie['saisie'] ?? '') && ($saisie['options']['nom'] ?? '')) {
				return $retourner_chemin ? $chemin : $saisie;
			}
		}
	}

	return null;
}

/**
 * Prend un tableau de saisie
 * retourne un tableau contenant uniquement les champs de saisie organisé par section (fieldset), avec pour chaque saisie le nom de la saisie en clé
 * @param array $saisies le tableau de saisie
 * @param array $options tableau d'options
 *	- string `callback_section` nom de la fonction de rappel sur les section, à défaut prend le nom ; exemple : `'saisies_saisie_get_label'`
 *	- string `callback_champ` nom de la fonction de rappel sur les champ, à défaut renvoie la saisie complète ; exemple : `'saisies_saisie_get_label'`
 *	- int `profondeur_max_output` indique sur combien de niveau doit être le tableau d'output, si pas défini / <= 0 , va jusqu'au bout de la hiérarchie, sinon ca aplatit les niveaux les plus profonds
 *	- bool `sans_vide`: si True, ne pas renvoyer les section vide
 *	- array|string forcer_type: liste de type à prendre, en excluant les autres
 * @return array
**/
function saisies_lister_champs_par_section(array $saisies, array $options = []): array {
	unset($saisies['options']);

	$resultat = [];


	// Normaliser les options
	$callback_section = $options['callback_section'] ?? function (array $s): string {
		return $s['options']['nom'];
	};
	$callback_champ = $options['callback_champ'] ?? function (array $s): array {
		return 	$s;
	};
	$profondeur_max_output = intval($options['profondeur_max_output'] ?? 0);
	$sans_vide = $options['sans_vide'] ?? false;

	$forcer_type = $options['forcer_type'] ?? [];
	if (!is_array($forcer_type)) {
		$forcer_type = [trim($forcer_type)];
	}
	$forcer_type = array_filter($forcer_type);


	// À quel niveau est-ton dans l'arborescence ?
	static $profondeur_actuelle = 1;
	$profondeur_actuelle++;

	// Parcourir les saisies et remplir $resultat (array)
	foreach ($saisies as $saisie) {
		if (saisies_saisie_est_avec_sous_saisies($saisie) && saisies_saisie_est_labelisable($saisie)) {
			$section = $callback_section($saisie);

			if (!$profondeur_max_output || $profondeur_actuelle <= $profondeur_max_output) {
				$resultat[$section] = saisies_lister_champs_par_section($saisie['saisies'], $options);
			} else {
				$resultat[$section] =
					saisies_lister_champs_par_section(
						saisies_lister_finales($saisie['saisies']),
						$options
					);
			}
		} elseif (saisies_saisie_est_avec_sous_saisies($saisie)) {
			$resultat = array_merge($resultat, saisies_lister_champs_par_section($saisie['saisies'], $options));
		} elseif (saisies_saisie_est_champ($saisie)) {
			$nom = $saisie['options']['nom'];
			if (!$forcer_type) {
				$resultat[$nom] = $callback_champ($saisie);
			} elseif (in_array($saisie['saisie'], $forcer_type)) {
				$resultat[$nom] = $callback_champ($saisie);
			}
		}
	}

	if ($sans_vide) {
		$resultat = array_filter($resultat);
	}
	// Avant de ressortir, diminuer la profondeur actuelle
	$profondeur_actuelle--;
	return $resultat;
}

/**
 * Indique si dans une liste de saisies, au moins l'une d'entre elle possède l'option `$option`
 * Optimisée par rapport à `saisies_lister_avec_option()` car s'arrête de parcourir dès la première saisies trouvée
 * @param array $saisies
 * @param string $options
 * @return bool
**/
function saisies_dont_avec_option(?array $saisies = [], string $option = ''): bool {
	if (!$saisies) {
		return false;
	}
	unset($saisies['options']);
	foreach ($saisies as $saisie) {
		if ($saisie['options'][$option] ?? '') {
			return true;
		}
		if (isset($saisie['saisies']) && saisies_dont_avec_option($saisie['saisies'], $option)) {
			return true;
		}
	}
	return false;
}
