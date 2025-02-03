<?php

/**
 * Gestion de l'affichage des saisies.
 *
 * @return SPIP\Saisies\Afficher
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Indique si une saisie peut être affichée.
 *
 * On utilise en priorité l'option `depublie`.
 * Si activée, la saisie n'est pas affichée sauf si valeur déjà présente.
 *
 * Mais par compatibilité historique, on peut s'appuyer sur l'éventuelle clé "editable" du $champ.
 * Si editable vaut :
 *    - absent : le champ est éditable
 *    - 1, le champ est éditable
 *    - 0, le champ n'est pas éditable
 *    - -1, le champ est éditable s'il y a du contenu dans le champ (l'environnement)
 *         ou dans un de ses enfants (fieldsets)
 *
 * @param array $champ
 *                                 Tableau de description de la saisie
 * @param array $env
 *                                 Environnement transmis à la saisie, certainement l'environnement du formulaire
 * @param bool  $utiliser_editable
 *                                 - false pour juste tester le cas -1
 *
 * @return bool
 *              Retourne un booléen indiquant l'état éditable ou pas :
 *              - true si la saisie est éditable (peut être affichée)
 *              - false sinon
 */
function saisie_editable($champ, $env, $utiliser_editable = true) {
	if ($champ['options']['depublie'] ?? false) {
		$utiliser_editable = false;
	} elseif (isset($champ['saisies'])) {
		$original_utiliser_editable = $utiliser_editable;
		$sous_saisies = saisies_lister_par_nom($champ['saisies']);
		foreach ($sous_saisies as $s => $sous_saisie) {
			if (($sous_saisie['options']['depublie'] ?? false)) {
				$utiliser_editable = false;
			}
			if (!$utiliser_editable && saisie_editable($sous_saisie, $env, $original_utiliser_editable)) {
				return true;
			}
		}
	}
	if ($utiliser_editable) {
		// si le champ n'est pas éditable, on sort.
		if (!isset($champ['editable'])) {
			return true;
		}
		$editable = $champ['editable'];

		if ($editable > 0) {
			return true;
		}
		if ($editable == 0) {
			return false;
		}
	}

	// cas -1
	// name de la saisie
	if (isset($champ['options']['nom'])) {
		// si on a le name dans l'environnement, on le teste
		$nom = $champ['options']['nom'];
		if (isset($env[$nom])) {
			return $env[$nom] ? true : false;
		}
	}
	// sinon, si on a des sous saisies
	if (is_array($champ['saisies'] ?? '')) {
		foreach ($champ['saisies'] as $saisie) {
			if (saisie_editable($saisie, $env, false)) {
				return true;
			}
		}
	}

	// aucun des paramètres demandés n'avait de contenu
	return false;
}

/**
 * Génère une saisie à partir d'un tableau la décrivant et de l'environnement.
 *
 * @param array $champ
 *                     Description de la saisie.
 *                     Le tableau doit être de la forme suivante :
 *                     array(
 *                     'saisie' => 'input',
 *                     'options' => array(
 *                     'nom' => 'le_name',
 *                     'label' => 'Un titre plus joli',
 *                     'obligatoire' => 'oui',
 *                     'explication' => 'Remplissez ce champ en utilisant votre clavier.'
 *                     )
 *                     )
 * @param array $env
 *                     Environnement du formulaire
 *                     Permet de savoir les valeurs actuelles des contenus des saisies,
 *                     les erreurs eventuelles présentes...
 *
 * @return string
 *                Code HTML des saisies de formulaire
 */
function saisies_generer_html($champ, $env = []) {
	// Si le parametre n'est pas bon, on genere du vide
	if (!is_array($champ)) {
		return '';
	}

	// Si la saisie n'est pas editable, on sort aussi.
	if (!saisie_editable($champ, $env) && !($env['_toujour_editable'] ?? false)) {
		return '';
	}

	$contexte = [];

	// Si la saisie est depubliee, mais qu'une valeur existait avant (car sinon elle ne serait plus éditable), on enlève l'obligation et on marque que c'est dépubliée
	if (($champ['options']['depublie'] ?? '') || ($env['depublie'] ?? '')) {
		$env['depublie'] = 'on';// pour transmettre aux enfants
		unset($champ['options']['obligatoire']);
		// On indique si c'est dépubliée
		if ($champ['options']['label'] ?? '') {
			$champ['options']['label'] = $champ['options']['label'] . ' (' . _T('saisies:saisie_depublie') . ')';
		}
		if ($options['label_case'] ?? '') {
			$champ['option']['label_case'] = $champ['options']['label_case'] . ' (' . _T('saisies:saisie_depublie') . ')';
		}
	}


	// On sélectionne le type de saisie
	$contexte['type_saisie'] = $champ['saisie'];
	// Identifiant unique de saisie, si present
	if (isset($champ['identifiant'])) {
		$contexte['id_saisie'] = $champ['identifiant'];
	}

	// S'il y a le détail des saisies du même formulaire, on le passe en contexte. Utiliser pour générer correctement les afficher_si
	// Attention, en cas de multi-étapes, on passe le contexte de toutes les saisies, classées par etape en cherchant par priorité dans `saisies_par_etapes`, puis `_saisies_par_etapes`, puis `saisies` qu'on regroupe en étapes.
	if (isset($env['_etape']) && isset($env['saisies_par_etapes'])) {
		$contexte['_saisies'] = $env['saisies_par_etapes'];
	} elseif (isset($env['_etape']) && isset($env['_saisies_par_etapes'])) {
		$contexte['_saisies'] = $env['_saisies_par_etapes'];
	} else {
		$contexte['_saisies'] = isset($env['saisies']) ? $env['saisies'] : [];
	}

	// Peut-être des transformations à faire sur les options textuelles
	$options = isset($champ['options']) ? $champ['options'] : [];
	foreach ($options as $option => $valeur) {
		if (substr($option, 0, 4) === 'data' && !is_array($valeur)) {//data, ou datas, ou data_rows, ou data_cols
			// exploser une chaine datas en tableau
			$options[$option] = _T_ou_typo(saisies_chaine2tableau($valeur), 'multi');
		} else {
			$options[$option] = _T_ou_typo($valeur, 'multi');
		}
	}
	// compatibilité li_class > conteneur_class
	if (!empty($options['li_class'])) {
		if (isset($options['conteneur_class'])) {
			$options['conteneur_class'] .= ' ' . $options['li_class'];
		} else {
			$options['conteneur_class'] = $options['li_class'];
		}
	}
	// Ne pas passer les sous saisies qui auraient été mise dans $options directement dans le le contexte, cf https://git.spip.net/spip-contrib-extensions/saisies/issues/127
	unset($options['saisies']);

	// Normaliser l'option `attributs` : on la merge avec l'option `attributs_data`.
	// Cette dernière sert à faciliter spécifiquement l'ajout d'attributs `data-xxx`.
	// Format : tableau de paires clé / valeur dont les entrées seront ajoutées sous la forme `data-<cle> = <valeur>`.
	// Les valeurs sous forme de tableau sont converties en JSON.
	// Les valeurs null produisent juste `data-<cle>`, sans valeur.
	$options = saisies_afficher_normaliser_options_attributs($options);

	// On ajoute les options propres à la saisie
	$contexte = array_merge($contexte, $options);

	// On ajoute aussi les infos de vérification, si cela peut se faire directement en HTML5
	if (isset($champ['verifier'])) {
		$contexte = array_merge($contexte, ['verifier' => $champ['verifier']]);
	}

	// Si env est définie dans les options ou qu'il y a des enfants, on ajoute tout l'environnement
	if (isset($contexte['env']) || is_array($champ['saisies'] ?? '')) {
		unset($contexte['env']);

		// on sauve l'ancien environnement
		// car les sous-saisies ne doivent pas être affectees
		// par les modification sur l'environnement servant à generer la saisie mère
		$contexte['_env'] = $env;

		// À partir du moment où on passe tout l'environnement,
		// il faut enlever certains éléments qui ne doivent absolument provenir que des options
		unset($env['inserer_debut']);
		unset($env['inserer_fin']);
		unset($env['id']);
		$saisies_disponibles = saisies_lister_disponibles();
		if (isset($saisies_disponibles[$contexte['type_saisie']]) && is_array($saisies_disponibles[$contexte['type_saisie']]['options'] ?? '')) {
			$options_a_supprimer = array_merge(
				saisies_lister_champs($saisies_disponibles[$contexte['type_saisie']]['options']),
				saisies_lister_champs($saisies_disponibles[$contexte['type_saisie']]['options_dev'] ?? [])
			);
			foreach ($options_a_supprimer as $option_a_supprimer) {
				unset($env[$option_a_supprimer]);
			}
		}
		$contexte = array_merge($env, $contexte);
	} else {
		// Sinon on ne sélectionne que quelques éléments importants
		// On récupère la liste des erreurs
		$contexte['erreurs'] = isset($env['erreurs']) ? $env['erreurs'] : [];
		// On récupère la langue de l'objet si existante
		if (isset($env['langue'])) {
			$contexte['langue'] = $env['langue'];
		}
		// On ajoute toujours le bon self
		$contexte['self'] = self();
	}

	// Transformer en amont les noms
	$contexte['nom'] = saisie_nom2name($contexte['nom']);
	// Dans tous les cas on récupère de l'environnement la valeur actuelle du champ
	// Si le nom du champ est un tableau indexé, il faut parser !
	if (
		isset($contexte['nom'])
		&& preg_match('/([\w]+)((\[[\w]+\])+)/', $contexte['nom'], $separe)
		&& isset($env[$separe[1]])
	) {
		$contexte['valeur'] = $env[$separe[1]];
		preg_match_all('/\[([\w]+)\]/', $separe[2], $index);
		// On va chercher au fond du tableau
		foreach ($index[1] as $cle) {
			$contexte['valeur'] = isset($contexte['valeur'][$cle]) ? $contexte['valeur'][$cle] : null;
		}
	} elseif (isset($contexte['nom']) && isset($env[$contexte['nom']])) {
		// Sinon la valeur est juste celle du nom si elle existe
		$contexte['valeur'] = $env[$contexte['nom']];
	} else {
		// Sinon rien
		$contexte['valeur'] = null;
	}

	// Si ya des enfants on les remonte dans le contexte
	if (is_array($champ['saisies'] ?? '')) {
		$contexte['saisies'] = $champ['saisies'];
	}
	// On génère la saisie
	return recuperer_fond(
		'saisies/_base',
		$contexte
	);
}

/**
 * Génère une vue d'une saisie à partir d'un tableau la décrivant.
 *
 * @see saisies_generer_html()
 *
 * @param array $saisie
 *                               Tableau de description d'une saisie
 * @param array $env
 *                               L'environnement, contenant normalement la réponse à la saisie
 * @param array $env_obligatoire
 *                               Ce qui doit toujours être passé à l'environnement
 *
 * @return string
 *                Code HTML de la vue de la saisie
 */
function saisies_generer_vue($saisie, $env = [], $env_obligatoire = []) {
	// Si le paramètre n'est pas bon, on génère du vide
	if (!is_array($saisie)) {
		return '';
	}

	$contexte = [];

	// On sélectionne le type de saisie
	$contexte['type_saisie'] = $saisie['saisie'];

	// Peut-être des transformations à faire sur les options textuelles
	$options = $saisie['options'];
	foreach ($options as $option => $valeur) {
		if (substr($option, 0, 4) === 'data' && !is_array($valeur)) {//data, ou datas, ou data_rows, ou data_cols
			$options[$option] = _T_ou_typo(saisies_chaine2tableau($valeur), 'multi');
		} else {
			$options[$option] = _T_ou_typo($valeur, 'multi');
		}
	}

	// On indique si c'est dépubliée
	if ($options['depublie'] ?? '') {
		if ($options['label'] ?? '') {
			$options['label'] = $options['label'] . ' (' . _T('saisies:saisie_depublie') . ')';
		}
		if ($options['label_case'] ?? '') {
			$options['label_case'] = $options['label_case'] . ' (' . _T('saisies:saisie_depublie') . ')';
		}
	}

	// On ajoute les options propres à la saisie
	$contexte = array_merge($contexte, $options);

	$contexte['_env'] = $env;
	// Si env est définie dans les options ou qu'il y a des enfants, on ajoute tout l'environnement
	if (isset($contexte['env']) || is_array($saisie['saisies'] ?? '')) {
		unset($contexte['env']);

		// À partir du moment où on passe tout l'environnement, il faut enlever
		// certains éléments qui ne doivent absolument provenir que des options
		$saisies_disponibles = saisies_lister_disponibles();

		if (is_array($saisies_disponibles[$contexte['type_saisie']]['options'] ?? '')) {
			$options_a_supprimer = array_merge(
				saisies_lister_champs($saisies_disponibles[$contexte['type_saisie']]['options']),
				saisies_lister_champs($saisies_disponibles[$contexte['type_saisie']]['options_dev'] ?? [])
			);
			foreach ($options_a_supprimer as $option_a_supprimer) {
				unset($env[$option_a_supprimer]);
			}
		}
		$contexte = array_merge($env, $contexte);
	}

	// Faut-il aussi afficher les explications ?
	if (isset($env['voir_explications'])) {
		$contexte['voir_explications'] = $env['voir_explications'];
	}
	// Dans tous les cas on récupère de l'environnement la valeur actuelle du champ

	// On regarde en priorité s'il y a un tableau listant toutes les valeurs, sinon on cherchera dans l'env
	$contexte['valeur'] = saisies_request($contexte['nom'], (!empty($env['valeurs']) && is_array($env['valeurs'])) ? $env['valeurs'] : $env);
	if (is_null($contexte['valeur'])) {
		$contexte['valeur'] = '';
	}

	// Si ya des enfants on les remonte dans le contexte
	if (is_array($saisie['saisies'] ?? '')) {
		$contexte['saisies'] = $saisie['saisies'];
	}

	if (is_array($env_obligatoire)) {
		$contexte = array_merge($contexte, $env_obligatoire);
	}

	// On génère la saisie
	return recuperer_fond(
		'saisies-vues/_base',
		$contexte
	);
}

/**
 * Récupère l'erreur d'une saisie particulière au sein d'un tableau d'erreur
 * @param ?array $erreurs
 *	Tableau d'erreurs, 3 formats possibles
 *	- * Arborescents SPIP 'niveau1/niveau2/niveau3' => 'erreur'
 *	- * Arborescents HTML 'niveau1[niveau2][niveau3]' => 'erreur'
 *	- * Arborescents PHP ['niveau1' => ['niveau2' => 'niveau3' => 'erreur']]]
 *	@param string $nom_ou_$name
 *	- * Soit nom SPIP 'niveau1/niveau2/niveau3'
 *	- * Soit name HTML 'niveau1[niveau2][niveau3]'
 *	@return string
 **/
function saisies_trouver_erreur(?array $erreurs, string $nom_ou_name): string {
	if (!$erreurs) {
		return '';
	}
	$nom = saisie_name2nom($nom_ou_name);
	$name = saisie_nom2name($nom_ou_name);

	$retour = $erreurs[$nom] ?? '';

	if (!$retour) {
		$retour = table_valeur($erreurs, $nom);
	}

	if (!$retour) {
		$retour = $erreurs[$name] ?? '';
	}

	return interdire_scripts($retour);
}

/**
 * Normaliser l'option `attributs` d'une saisie individuelle
 * En mergeant les attributs_data
 * @param array $options liste des options de la saisie
 * @return array $options liste des options, normalisée
**/
function saisies_afficher_normaliser_options_attributs(array $options) :array {
	$attributs_data = $options['attributs_data'] ?? null;
	if (is_array($attributs_data)) {
		$attributs = $options['attributs'] ?? '';
		foreach ($attributs_data as $cle => $valeur) {
			if (is_null($valeur)) {
				$attributs .= " data-{$cle}";
			} else {
				// Si c'est un tableau, on encode en JSON, sinon on force en string
				$valeur = is_array($valeur) ? json_encode($valeur) : (string)$valeur;
				// On échappe toujours le contenu de l'attribut
				$valeur = attribut_html($valeur);
				// On remplit la valeur de l'attribut
				$attributs .= " data-{$cle}=\"{$valeur}\"";
			}
		}
		$attributs = trim($attributs);
		$options['attributs'] = $attributs;
		unset($options['attributs_data']); // pas besoin de garder ça dans l'env
	}
	return $options;
}
