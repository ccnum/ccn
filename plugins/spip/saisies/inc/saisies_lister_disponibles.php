<?php

/**
 * Gestion de listes des saisies disponibles (via .yaml)
 *
 * @return SPIP\Saisies\Listes\Disponibles
 **/


/**
 * Liste toutes les saisies configurables (ayant une description).
 * @param string $saisies_repertoire le répertoire où trouver les saisies
 * @param bool $inclure_obsoletes : faut-il inclure les saisies obsolètes ?
 * @return array Un tableau listant des saisies et leurs options
 */
function saisies_lister_disponibles($saisies_repertoire = 'saisies', $inclure_obsoletes = true) {
	if (!defined('_DIR_PLUGIN_YAML')) {
		throw new Exception('La fonction saisies_lister_disponibles() nécessite le plugin YAML');
	}
	static $saisies = null;
	static $saisies_obsoletes = [];

	if (is_null($saisies)) {
		$saisies = [];
		$liste = find_all_in_path("$saisies_repertoire/", '.+[.]yaml$');

		if (count($liste)) {
			foreach ($liste as $fichier => $chemin) {
				$type_saisie = preg_replace(',[.]yaml$,i', '', $fichier);
				$dossier = str_replace($fichier, '', $chemin);
				// On ne garde que les saisies qui ont bien le HTML avec !
				if (
					file_exists("$dossier$type_saisie.html")
					&& (
						is_array($saisie = saisies_charger_infos($type_saisie))
					)
				) {
					if ($saisie['obsolete'] ?? false) {
						$saisies_obsoletes[$type_saisie] = $saisie;
					} else {
						$saisies[$type_saisie] = $saisie;
					}
				}
			}
		}
	}
	if ($inclure_obsoletes) {
		return pipeline('saisies_lister_disponibles', array_merge($saisies, $saisies_obsoletes));
	} else {
		return pipeline('saisies_lister_disponibles', $saisies);
	}
}

/**
 * Liste tous les groupes de saisies configurables (ayant une description).
 * @return array Un tableau listant des saisies et leurs options
 */
function saisies_groupes_lister_disponibles($saisies_repertoire = 'saisies') {
	static $saisies = null;

	if (is_null($saisies)) {
		$saisies = [];
		$liste = find_all_in_path("$saisies_repertoire/", '.+[.]yaml$');

		if (count($liste)) {
			foreach ($liste as $fichier => $chemin) {
				$type_saisie = preg_replace(',[.]yaml$,i', '', $fichier);
				$dossier = str_replace($fichier, '', $chemin);

				if (is_array($saisie = saisies_charger_infos($type_saisie, $saisies_repertoire))) {
					$saisies[$type_saisie] = $saisie;
				}
			}
		}
	}

	return $saisies;
}

/**
 * Lister les saisies existantes ayant une définition SQL.
 * @param string $saisies_repertoire le répertoire où trouver les saisies
 * @param bool $inclure_obsoletes : faut-il inclure les saisies obsolètes ?
 * @return array Un tableau listant des saisies et leurs options
 */
function saisies_lister_disponibles_sql($saisies_repertoire = 'saisies', $inclure_obsoletes = true) {
	$saisies = [];
	$saisies_disponibles = saisies_lister_disponibles($saisies_repertoire, $inclure_obsoletes);
	foreach ($saisies_disponibles as $type => $saisie) {
		if ($saisie['defaut']['options']['sql'] ?? '') {
			$saisies[$type] = $saisie;
		}
	}

	return $saisies;
}

/**
 * Charger les informations contenues dans le YAML d'une saisie.
 *
 * @param string $type_saisie Le type de la saisie
 *
 * @return array Un tableau contenant le YAML décodé
 */
function saisies_charger_infos($type_saisie, $saisies_repertoire = 'saisies') {
	static $cache = [];
	if (defined('_DIR_PLUGIN_YAML')) {
		include_spip('inc/yaml');
		$fichier = find_in_path("$saisies_repertoire/$type_saisie.yaml");
		if (isset($cache[$fichier])) {
			$saisie = $cache[$fichier];
		} else {
			$saisie = yaml_charger_inclusions(yaml_decode_file($fichier));

			if (is_array($saisie)) {
				$saisie = saisies_recuperer_heritage($saisie, $saisies_repertoire);
				$saisie['titre'] = _T_ou_typo($saisie['titre'] ?? '');
				if (!$saisie['titre']) {
					$saisie['titre'] = $type_saisie;
				};
				$saisie['description'] =  _T_ou_typo($saisie['description'] ?? '');
				if ($saisie['icone'] ?? '') {
					$icone = $saisie['icone'];
					$saisie['icone'] = chemin_image($icone);
					if (!$saisie['icone']) {
						$saisie['icone'] = find_in_path($icone);
					}
				} else {
					$saisie['icone'] = '';
				}
			}
			$cache[$fichier] = $saisie;
		}
	}
	else {
		throw new Exception('La fonction saisies_charger_infos() nécessite le plugin YAML');
	}

	return $saisie;
}
/**
 * Permet à une saisie d'hériter des options et valeur par défaut d'une autre saisies
 * @param string $saisie la saisie
 * @param string $saisies_repertoire = 'saisies'
 * @return array
 **/
function saisies_recuperer_heritage($saisie, $saisies_repertoire = 'saisies') {
	if (!isset($saisie['heritage'])) {
		return $saisie;
	}
	$heritage = $saisie['heritage'];
	$parent = saisies_charger_infos($heritage['parent'], $saisies_repertoire);
	$saisie_options = &$parent['options'];

	// Enlever les options
	if (isset($heritage['enlever_options'])) {
		foreach ($heritage['enlever_options'] as $option) {
			$saisie_options = saisies_supprimer($saisie_options, $option);
		}
	}
	// Les saisies qu'on modifie
	if (isset($heritage['modifier_options'])) {
		foreach ($heritage['modifier_options'] as $option) {
			if (isset($option['chemin'])) {
				$id_ou_nom_ou_chemin = $option['chemin'];
				unset($option['chemin']);
			} elseif (isset($option['options']['nom'])) {
				$id_ou_nom_ou_chemin = $option['options']['nom'];
			} else {
				continue;
			}
			if (($option['mode'] ?? '') === 'fusionner') {
				$fusion = true;
			} else {
				$fusion = false;
			}
			unset($option['mode']);
			$saisie_options = saisies_modifier($saisie_options, $id_ou_nom_ou_chemin, $option, $fusion);
		}
	}
	//Ajouter les nouvelles options
	if (isset($heritage['ajouter_options'])) {
		foreach ($heritage['ajouter_options'] as $option) {
			if (isset($option['chemin'])) {
				$chemin = $option['chemin'];
				unset($option['chemin']);
				$saisie_options = saisies_inserer($saisie_options, $option, $chemin);
			} elseif (isset($option['inserer_avant'])) {
				$chemin = $option['inserer_avant'];
				unset($option['inserer_avant']);
				$saisie_options = saisies_inserer_avant($saisie_options, $option, $chemin);
			} elseif (isset($option['inserer_apres'])) {
				$chemin = $option['inserer_apres'];
				unset($option['inserer_apres']);
				$saisie_options = saisies_inserer_apres($saisie_options, $option, $chemin);
			}
		}
	}

	// Nettoyage et fusion finale
	unset($saisie['heritage']);

	$saisie = array_replace_recursive($parent, $saisie);
	return $saisie;
}



/**
 * Lister les catégories par défaut, puis les envoyer au pipeline
 * @return array liste des catégories
 **/
function saisies_lister_categories() {
	$categories = pipeline(
		'saisies_lister_categories',
		[
			'libre' => [
				'nom' => _T('saisies:categorie_libre_label'),
			],
			'choix' => [
				'nom' =>  _T('saisies:categorie_choix_label'),
			],
			'structure' => [
				'nom' =>  _T('saisies:categorie_structure_label'),
			],
			'objet' => [
				'nom' =>  _T('saisies:categorie_objet_label'),
			],
			'defaut' => [
				'nom' =>  _T('saisies:categorie_defaut_label'),
			]
		]
	);

	// S'assurer que defaut soit tout le temps à la fin
	$defaut = $categories['defaut'];
	unset($categories['defaut']);
	$categories['defaut'] = $defaut;

	return $categories;
}

/**
 * Lister les saisies disponibles en les regroupant en catégories
 * @param array $options
 *	'saisies_repertoire' => string ('saisies')
 *	'inclure_obsoletes' => bool (false)
 *	'categorie' => string|null|false (false)
 *	'uniquement_sql' => bool (false) pour limiter à celle avec sql
 * @return array
 **/
function saisies_lister_disponibles_par_categories($options = []) {
	// Options par défaut
	if (!isset($options['saisies_repertoire'])) {
		$options['saisies_repertoire'] = 'saisies';
	}
	if (!isset($options['inclure_obsoletes'])) {
		$options['inclure_obsoletes'] = false;
	}
	if (!isset($options['categorie'])) {
		$options['categorie'] = false;
	}

	if ($options['uniquement_sql'] ?? '') {
		$saisies = saisies_lister_disponibles_sql($options['saisies_repertoire'], $options['inclure_obsoletes']);
	} else {
		$saisies = saisies_lister_disponibles($options['saisies_repertoire'], $options['inclure_obsoletes']);
	}

	return saisies_regrouper_disponibles_par_categories($saisies, $options['categorie']);
}
/**
 * Liste par catégorie les saisies disponibles ayant une définition SQL
 * @param array $options > voir saisies_lister_disponibles_par_categories
 * return array
 **/
function saisies_lister_disponibles_sql_par_categories($options = []) {
	$options['uniquement_sql'] = true;
	return saisies_lister_disponibles_par_categories($options);
}



/**
 * Regroupe par categories les saisies
 * @param array $saisies
 * @param string|null $categorie_demande pour ne renvoyer que cette catégorie
 * @return array
 **/
function saisies_regrouper_disponibles_par_categories($saisies = [], $categorie_demande = null) {

	$categories = saisies_lister_categories();
	foreach ($saisies as $s => $saisie) {
		if (isset($saisie['categorie']['type'])) {
			$categorie = $saisie['categorie']['type'];
		} else {//Sinon defaut
			$categorie = 'defaut';
			spip_log($saisie['titre'] . ' sans catégorie, reclassée en defaut', 'saisies');
		}

		// Saisie dans une catégorie non existante
		if (!isset($categories[$categorie])) {
			spip_log($saisie['titre'] . "dans une catégorie inexistante ($categorie), reclassée en defaut", 'saisies');
			$categorie = 'defaut';
		}

		// Si l'On demande uniquement une cate
		if ($categorie_demande && $categorie_demande !== $categorie) {
			continue;
		}

		// Vérifier qu'il y a deja des saisies pour cette catégorie
		if (!isset($categories[$categorie]['saisies'])) {
			$categories[$categorie]['saisies'] = [];
		}

		$categories[$categorie]['saisies'][$s] = $saisie;
	}
	foreach ($categories as $cat => &$desc) {
		if (isset($desc['saisies'])) {
			uasort($desc['saisies'], 'saisies_lister_disponibles_par_categories_usort');
		}
	}

	return $categories;
}



/**
 * Function de comparaison pour trier les saisies disponibles par rang
 * Si pas de rang défini, on met après ceux avec rang
 * Si deux saisies avec le même rang, on se rabat sur l'ordre alpha
 * @param $saisie1
 * @param $saisie2
 * @return int
 **/
function saisies_lister_disponibles_par_categories_usort($saisie1, $saisie2) {
	if (!isset($saisie1['categorie']['rang']) && isset($saisie2['categorie']['rang'])) {
		return +1;
	}
	if (isset($saisie1['categorie']['rang']) && !isset($saisie2['categorie']['rang'])) {
		return -1;
	}
	if (!isset($saisie1['categorie']['rang']) && !isset($saisie2['categorie']['rang'])) {
		$rang1 = $rang2 = 0;
	} else {
		$rang1 = $saisie1['categorie']['rang'];
		$rang2 = $saisie2['categorie']['rang'];
	}
	// On utilise la fonction translitteration faute de mieux, pour avoir Évenement avant Rubrique, mais en réalité il faudrait gerer cela par les fonctions de comparaison de Inl
	$titre1 = translitteration($saisie1['titre']);
	$titre2 = translitteration($saisie2['titre']);
	// Rangs égaux > on tri par titre
	if ($rang1 == $rang2) {
		if ($titre1 > $titre2) {
			return 1;
		} elseif ($titre1 < $titre2) {
			//Même si a priori peu probable que ce soit égal !
			return -1;
		}
		return 0;
		// Rang différent : on tri par rang
	} else {
		return $rang1 - $rang2;
	}
}
