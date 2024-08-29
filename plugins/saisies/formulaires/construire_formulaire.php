<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/saisies');
/**
 * Formulaire permettant de construire un formulaire  ! En agençant des champs
 * Chargement.
 * @param string $identifiant identifiant unique du formulaire
 * @param array $formulaires_initial, formulaire initial (par exemple si on modifie un formulaire déjà construit)
 * @param array $options tableau d'options
 *		- array options_globales : proposer des options globales pour le formulaire, liste de ces options
 *		- array saisies_exclues : liste des saisies à ne pas proposer (= à exclure du choix)
 *		- bool uniquement_sql : ne proposer que les saisies qui permettent de remplir un champ sql
 * @return array $contexte
**/
function formulaires_construire_formulaire_charger($identifiant, $formulaire_initial = [], $options = []) {
	$contexte = [];

	// On ajoute un préfixe devant l'identifiant, pour être sûr
	$identifiant = 'constructeur_formulaire_' . $identifiant;
	$contexte['_identifiant_session'] = $identifiant;

	// On vérifie ce qui a été passé en paramètre
	if (!is_array($formulaire_initial)) {
		$formulaire_initial = [];
	}

	// On s'assure que toutes les saisies ont un identifiant (en cas de bug lors de la création, par ex.)
	$formulaire_initial = saisies_identifier($formulaire_initial);

	// Construire le md5 du paramètre $formulaire_initial, et trouver celui qu'on avait stocké
	$md5_formulaire_initial = md5(serialize($formulaire_initial));
	$md5_precedent_formulaire_initial = session_get($identifiant . '_md5_formulaire_initial');

	// Si pas de session, on prend le formulaire initial comme formulaire actuel,
	// ou bien si la session est trop trop veille, on prend le formulaire initial comme formulaire
	$formulaire_actuel = session_get($identifiant);
	if (
		is_null($formulaire_actuel)
		|| (
			$md5_precedent_formulaire_initial
			&& $md5_formulaire_initial != $md5_precedent_formulaire_initial
			&& $_SERVER['REQUEST_METHOD'] === 'GET'
		)
	) {
		session_set($identifiant, $formulaire_initial);
		session_set($identifiant . '_md5_formulaire_initial', $md5_formulaire_initial);
		$formulaire_actuel = $formulaire_initial;
	}

	// Si le formulaire actuel est différent du formulaire initial on agite un drapeau pour le dire
	if ($formulaire_actuel != $formulaire_initial) {
		$contexte['formulaire_modifie'] = true;
	}
	$contexte['_message_attention'] = _T('saisies:construire_attention_modifie');

	// On passe ça pour l'affichage
	$contexte['_contenu'] = $formulaire_actuel;

	// On passe ça pour la récup plus facile des champs
	$contexte['_saisies_par_nom'] = saisies_lister_par_nom($formulaire_actuel);
	// Pour déclarer les champs modifiables à CVT
	foreach (array_keys($contexte['_saisies_par_nom']) as $nom) {
		$contexte["saisie_modifiee_$nom"] = [];
	}

	// La liste des options globales qu'on peut configurer, si elles existent
	if (is_array($options['options_globales'] ?? '')) {
		$contexte['_activer_options_globales'] = true;
		if (isset($formulaire_actuel['options'])) {
			$contexte['options_globales'] = $formulaire_actuel['options'];
		}
		else {
			$contexte['options_globales'] = [];
		}
	}

	// La liste des saisies
	if ($options['uniquement_sql'] ?? '') {
		$saisies_disponibles = saisies_lister_disponibles_sql('saisies', false);
	} else {
		$saisies_disponibles = saisies_lister_disponibles('saisies', false);
	}
	if (is_array($options['saisies_exclues'] ?? '')) {
		$saisies_disponibles = array_diff_key($saisies_disponibles, array_flip($options['saisies_exclues']));
	}
	$contexte['_saisies_disponibles_par_categories'] = saisies_regrouper_disponibles_par_categories($saisies_disponibles);

	// Vient-on de déplacer une saisie en éditant son onglet ? On l'indique en contexte
	$contexte['_saisie_deplacee_par_select'] = _request('_saisie_deplacee_par_select');

	// La liste des groupes de saisies
	$saisies_groupes_disponibles = saisies_groupes_lister_disponibles('saisies/groupes');
	$contexte['_saisies_groupes_disponibles'] = $saisies_groupes_disponibles;

	$contexte['fond_generer'] = 'formulaires/inc-generer_saisies_configurables';

	if (_request('configurer_saisie')) {
		$contexte['_configurer_saisie'] = 'configurer_saisie';
	}
	return $contexte;
}

function formulaires_construire_formulaire_verifier($identifiant, $formulaire_initial = [], $options = []) {
	$erreurs = [];
	// l'une ou l'autre sera presente
	$configurer_saisie = $enregistrer_saisie = '';
	$configurer_globales = '';
	$enregistrer_globales = '';
	// On ne fait rien du tout si on n'est pas dans l'un de ces cas
	if (
		!(
			($nom_ou_id = $configurer_saisie  = _request('configurer_saisie'))
			|| ($nom_ou_id = $enregistrer_saisie = _request('enregistrer_saisie'))
			|| ($configurer_globales = _request('configurer_globales'))
			|| ($enregistrer_globales = _request('enregistrer_globales'))
		)
	) {
		return $erreurs;
	}

	// On ajoute un préfixe devant l'identifiant
	$identifiant = 'constructeur_formulaire_' . $identifiant;
	// On récupère le formulaire à son état actuel
	$formulaire_actuel = session_get($identifiant);

	// Gestion de la config globales
	if ($configurer_globales || $enregistrer_globales) {
		$options['options_globales'] = saisies_fieldsets_en_onglets($options['options_globales'], $identifiant, true);
		$options['options_globales'] = saisies_transformer_option($options['options_globales'], 'conteneur_class', '#(.*)#', '\1 pleine_largeur');
		array_walk_recursive($options['options_globales'], 'construire_formulaire_transformer_nom', 'options_globales[@valeur@]');
		array_walk_recursive($options['options_globales'], 'construire_formulaire_transformer_afficher_si', 'options_globales');
		$erreurs['configurer_globales'] = $options['options_globales'];

		if ($enregistrer_globales) {
			$vraies_erreurs = saisies_verifier($erreurs['configurer_globales']);
		}
	}
	// Sinon c'est la gestion d'une saisie précise
	else {
		// On récupère les saisies actuelles, par identifiant ou par nom
		if ($nom_ou_id[0] == '@') {
			$saisies_actuelles = saisies_lister_par_identifiant($formulaire_actuel);
			$nom = $saisies_actuelles[$nom_ou_id]['options']['nom'];
		}
		else {
			$saisies_actuelles = saisies_lister_par_nom($formulaire_actuel);
			$nom = $nom_ou_id;
		}
		$noms_autorises = array_keys($saisies_actuelles);

		// le nom (ou identifiant) doit exister
		if (!in_array($nom_ou_id, $noms_autorises)) {
			return $erreurs;
		}
		// La liste des saisies
		if ($options['uniquement_sql'] ?? '') {
			$saisies_disponibles = saisies_lister_disponibles_sql('saisies', true);
		} else {
			$saisies_disponibles = saisies_lister_disponibles('saisies', true);
		}
		if (is_array($options['saisies_exclues'] ?? '')) {
			$saisies_disponibles = array_diff_key($saisies_disponibles, array_flip($options['saisies_exclues']));
		}

		$saisie = $saisies_actuelles[$nom_ou_id];
		$formulaire_config = $saisies_disponibles[$saisie['saisie']]['options'];
		// Ajouter l'option pour depublier la saisie
		$formulaire_config = construire_formulaire_config_inserer_option_depublie($formulaire_config);

		array_walk_recursive($formulaire_config, 'construire_formulaire_transformer_nom', "saisie_modifiee_{$nom}[options][@valeur@]");
		array_walk_recursive($formulaire_config, 'construire_formulaire_transformer_afficher_si', "saisie_modifiee_{$nom}[options]");
		$formulaire_config = saisie_identifier(['saisies' => $formulaire_config]);
		$formulaire_config = $formulaire_config['saisies'];


		// S'il y a l'option adéquat, on ajoute le champ pour modifier le nom
		if (
			($options['modifier_nom'] ?? '')
			&& ($chemin_nom = saisies_chercher($formulaire_config, "saisie_modifiee_{$nom}[options][description]", true))
		) {
			$chemin_nom[] = 'saisies';
			$chemin_nom[] = '0';

			$formulaire_config = saisies_inserer(
				$formulaire_config,
				[
					'saisie' => 'input',
					'options' => [
						'nom' => "saisie_modifiee_{$nom}[options][nom]",
						'label' => _T('saisies:option_nom_label'),
						'explication' => _T('saisies:option_nom_explication'),
						'obligatoire' => 'oui',
						'size' => 50
					],
					'verifier' => [
						'type' => 'slug',
						'options' => [
							'normaliser_suggerer' => true
						]
					]
				],
				$chemin_nom
			);
		}


		// liste des options de vérification
		$verif_options = [];

		// S'il y a un groupe "validation" alors on va construire le formulaire des vérifications
		if ($chemin_validation = saisies_chercher($formulaire_config, "saisie_modifiee_{$nom}[options][validation]", true)) {
			include_spip('inc/verifier');
			$liste_verifications = verifier_lister_disponibles();


			// Filtrer, si besoin, les vérifications par type de saisie
			$verifications = pipeline(
				'saisies_verifier_lister_disponibles',
				['args' => ['saisie' => $saisie['saisie']],
				'data' => [
						'disponibles' => $liste_verifications,
						'obligatoires' => []
					]
				]
			);
			$liste_verifications = $verifications['disponibles'];
			$liste_verifications_obligatoires = $verifications['obligatoires'];


			uasort($liste_verifications, 'verifier_trier_par_titre');

			$chemin_validation[] = 'saisies';
			$chemin_validation[] = 1000000; // à la fin

			// On construit la saisie à insérer et les fieldset des options
			$saisie_liste_verif = [
				'saisie' => 'selection',
				'options' => [
					'nom' => "saisie_modifiee_{$nom}[verifier][type]",
					'label' => _T('saisies:construire_verifications_label'),
					'cacher_option_intro' => true,
					'conteneur_class' => 'liste_verifications',
					'data' => [],
					'class' => 'select2',
					'multiple' => true
				]
			];

			foreach ($liste_verifications as $type_verif => $verif) {
				if (!in_array($type_verif, $liste_verifications_obligatoires)) {
					$saisie_liste_verif['options']['data'][$type_verif] = $verif['titre'];
				} else {
					$verif_options[] = [
						'saisie' => 'hidden',
						'options' => [
							'nom' => "saisie_modifiee_{$nom}[verifier][type][]",
							'valeur_forcee' => $type_verif,
						]
					];
				}
				// Si le type de vérif a des options, on ajoute un fieldset
				if (is_array($verif['options'] ?? []) && ($verif['options'] ?? '')) {
					$groupe = [
						'saisie' => 'fieldset',
						'options' => [
							'nom' => 'options',
							'label' => $verif['titre'],
							'explication' => $verif['description'] ?? '',
							'conteneur_class' => "$type_verif options_verifier",
							'afficher_si' => "@saisie_modifiee_{$nom}[verifier][type]@ IN '$type_verif'"
						],
						'saisies' => $verif['options']
					];
					array_walk_recursive($groupe, 'construire_formulaire_transformer_nom', "saisie_modifiee_{$nom}[verifier][$type_verif][@valeur@]");
					array_walk_recursive($groupe, 'construire_formulaire_transformer_afficher_si', "saisie_modifiee_{$nom}[verifier][$type_verif]");
					$verif_options[$type_verif] = $groupe;
				}
			}
			if (!empty($saisie_liste_verif['options']['data'])) {
				$verif_options = array_merge([$saisie_liste_verif], $verif_options);
			}
		}

		// Permettre d'intégrer des saisies et fieldset au formulaire de configuration.
		// Si des vérifications sont à faire, elles seront prises en compte
		// lors des tests de vérifications à l'enregistrement.
		$formulaire_config = pipeline('saisies_construire_formulaire_config', [
			'data' => $formulaire_config,
			'args' => [
				'identifiant' => $identifiant,
				'action' => $enregistrer_saisie ? 'enregistrer' : 'configurer',
				'options' => $options,
				'nom' => $nom,
				'saisie' => $saisie,
			],
		]);


		// Si la saisie possede un identifiant, on l'ajoute
		// au formulaire de configuration pour ne pas le perdre en route
		if ($saisie['identifiant'] ?? '') {
			$formulaire_config = saisies_inserer(
				$formulaire_config,
				[
					'saisie' => 'hidden',
					'options' => [
						'nom' => "saisie_modifiee_{$nom}[identifiant]",
						'defaut' => $saisie['identifiant']
					],
				]
			);
		}

		if ($enregistrer_saisie) {
			// La saisie modifié
			$saisie_modifiee = _request("saisie_modifiee_{$nom}");//contient tous les paramètres de la saisie
			// On cherche les erreurs de la configuration
			$vraies_erreurs = saisies_verifier($formulaire_config);

			// Si on autorise à modifier le nom ET qu'il doit être unique : on vérifie
			if (
				($options['modifier_nom'] ?? '')
				&& ($options['nom_unique'] ?? '')
			) {
				$nom_modifie = $saisie_modifiee['options']['nom'];
				if ($nom_modifie != $enregistrer_saisie && saisies_chercher($formulaire_actuel, $nom_modifie)) {
					$vraies_erreurs["saisie_modifiee_{$nom}[options][nom]"] = _T('saisies:erreur_option_nom_unique');
				}
			}

			// On regarde s'il a été demandé des vérifs, et on vérifie les options des vérif (!)
			// Note : les options de verif sont en afficher_si, donc ne sont vérifié que celles des vérifs choisies.
			$vraies_erreurs = array_merge($vraies_erreurs, saisies_verifier($verif_options));
		}

		// On insère chaque saisie des options de verification
		if ($verif_options) {
			foreach ($verif_options as $saisie_verif) {
				$formulaire_config = saisies_inserer($formulaire_config, $saisie_verif, $chemin_validation);
			}
		}
		$erreurs['configurer_' . $nom] = $formulaire_config;
	}

	// S'il y a des vraies erreurs au final
	if ($enregistrer_globales || $enregistrer_saisie) {
		if ($vraies_erreurs) {
			$erreurs = array_merge($erreurs, $vraies_erreurs);
			$erreurs['message_erreur'] = singulier_ou_pluriel(count($vraies_erreurs), 'avis_1_erreur_saisie', 'avis_nb_erreurs_saisie');
			set_request('configurer_saisie', true);// Si erreur, ca veut dire qu'on va continuer à configurer la saisie, donc il faut que charger le sache (rah, ce truc où vérifier ne peut pas renvoyer autre chose que des erreurs dans le #ENV...)
		} else {
			$erreurs = [];
		}
	} else {
		$erreurs['message_erreur'] = ''; // on ne veut pas du message_erreur automatique
	}

	return $erreurs;
}

function formulaires_construire_formulaire_traiter($identifiant, $formulaire_initial = [], $options = []) {
	$retours = [];
	if ($options['uniquement_sql'] ?? '') {
		$saisies_disponibles = saisies_lister_disponibles_sql('saisies', true);
	} else {
		$saisies_disponibles = saisies_lister_disponibles('saisies', true);
	}
	if (is_array($options['saisies_exclues'] ?? '')) {
		$saisies_disponibles = array_diff_key($saisies_disponibles, array_flip($options['saisies_exclues']));
	}

	// On ajoute un préfixe devant l'identifiant
	$identifiant = 'constructeur_formulaire_' . $identifiant;
	// On récupère le formulaire à son état actuel
	$formulaire_actuel = session_get($identifiant);

	// Si on demande à ajouter un groupe
	if ($ajouter_saisie = _request('ajouter_groupe_saisie')) {
		$formulaire_actuel = saisies_groupe_inserer($formulaire_actuel, $ajouter_saisie);
	}

	// Si on demande à ajouter une saisie
	if ($ajouter_saisie = _request('ajouter_saisie')) {
		$nom = saisies_generer_nom($formulaire_actuel, $ajouter_saisie);
		$saisie = [
			'saisie' => $ajouter_saisie,
			'options' => [
				'nom' => $nom
			]
		];
		// S'il y a des valeurs par défaut pour ce type de saisie, on les ajoute
		$defaut = $saisies_disponibles[$ajouter_saisie]['defaut'] ?? '';
		if (is_array($defaut)) {
			$defaut = _T_ou_typo($defaut, 'multi');

			$saisie = array_replace_recursive($saisie, $defaut);
		}
		// Si la dernière saisies est un fieldset (ou un type dérivé de fieldset, c'est à dire si peut contenir des sous saisies), inserer à la fin du fieldset, sauf si saisie à insérer est un fieldset
		if (!empty($formulaire_actuel)) {
			$saisie_de_fin = &$formulaire_actuel[max(array_keys($formulaire_actuel))];
		} else {
			$saisie_de_fin = ['saisie' => 'nope'];
		}
		if (isset($saisie_de_fin['saisies']) && ($saisie['saisie'] !== $saisie_de_fin['saisie'])) {
			$saisies_fieldset_fin = &$saisie_de_fin['saisies'];
			while (
				is_array($saisies_fieldset_fin)
				&& isset(array_slice($saisies_fieldset_fin, -1, 1)[0]['saisies'])
				&& !isset($saisie['saisies'])
			) {
				$fin = &$saisies_fieldset_fin[count($saisies_fieldset_fin) - 1];
				$saisies_fieldset_fin = &$fin['saisies'];
			}
			$saisies_fieldset_fin = saisies_inserer($saisies_fieldset_fin, $saisie);
		} else {
			$formulaire_actuel = saisies_inserer($formulaire_actuel, $saisie);
		}
	}

	// Si on demande à dupliquer une saisie
	if ($dupliquer_saisie = _request('dupliquer_saisie')) {
		$formulaire_actuel = saisies_dupliquer($formulaire_actuel, $dupliquer_saisie);
	}

	// Si on demande à supprimer une saisie
	if ($supprimer_saisie = _request('supprimer_saisie')) {
		$formulaire_actuel = saisies_supprimer($formulaire_actuel, $supprimer_saisie);
	}

	// Si on enregistre la conf globale
	if (_request('enregistrer_globales')) {
		$options_globales = _request('options_globales');
		$formulaire_actuel['options'] = $options_globales;
	}

	// Si on enregistre la conf d'une saisie
	if ($nom = _request('enregistrer_saisie')) {
		// On récupère ce qui a été modifié
		$saisie_modifiee = _request("saisie_modifiee_$nom");

		// On regarde s'il y a une position à modifier
		if (isset($saisie_modifiee['position'])) {
			$position = $saisie_modifiee['position'];
			unset($saisie_modifiee['position']);
			// On ne déplace que si ce n'est pas la même chose
			if ($position != $nom) {
				$formulaire_actuel = saisies_deplacer($formulaire_actuel, $nom, $position);
			}
			set_request('_saisie_deplacee_par_select', $nom);
		}

		// On regarde s'il y a des options de vérification à modifier
		$verifier_format_api = [];
		if (isset($saisie_modifiee['verifier']['type'])) {
			foreach ($saisie_modifiee['verifier']['type'] as $type_verif) {
				$verifier_format_api[] = [
					'type' => $type_verif,
					'options' => array_filter($saisie_modifiee['verifier'][$type_verif] ?? [], 'saisie_option_contenu_vide') ?? []
				];
			}
		}
		$saisie_modifiee['verifier'] = $verifier_format_api;

		// On récupère les options postées en enlevant les chaines vides
		$saisie_modifiee['options'] = array_filter($saisie_modifiee['options'], 'saisie_option_contenu_vide');


		// On modifie enfin
		$formulaire_actuel = saisies_modifier($formulaire_actuel, $nom, $saisie_modifiee);
	}

	// Si on demande à réinitialiser
	if (_request('reinitialiser') == 'oui') {
		$formulaire_actuel = $formulaire_initial;
	}

	// On enregistre en session la nouvelle version du formulaire
	session_set($identifiant, $formulaire_actuel);

	// Le formulaire reste éditable
	$retours['editable'] = true;

	return $retours;
}

/**
 * Permet de modifier l'option `nom` d'une saisie.
 * Fonction de callback pour array_walk_recursive().
 * @internal
 * @param string &$valeur
 *	la valeur du tableau d'options
 * @param string $cle
 *	la clé du tableau, permet de savoir si on traite une option `nom` ou pas
 * @param string $transformation
 *	 chaine contenant notamment `@valeur@`, le `@valeur@` sera remplacé par la valeur original de `$valeur`, puis le tout reinjecté dans `$&valeur`.
 * @return void
 *
**/
function construire_formulaire_transformer_nom(&$valeur, string $cle, string $transformation) {
	if ($cle == 'nom' && is_string($valeur)) {
		$valeur = str_replace('@valeur@', $valeur, $transformation);
	}
}

/**
 * Permet de transformer les `afficher_si` présent au sein d'un YAML décrivant les options d'une saisie (abstraite)
 * de sorte qu'ils fonctionnent au sein du formulaire permettant de construire une saisie (concrète).
 * Pour ce faire il faut préserver les tests portant sur les plugins et les config
 * mais transformer ceux portant sur des options décrites dans le tableau YAML.
 * Il faut également éviter de modifier un name s'il contient `saisie_modifiee` pour éviter une réentrance.
 * Fonction de callback pour array_walk_recursive().
 * @internal
 * @param string &$valeur
 *	la valeur au sein du tableau d'options
 * @param string $cle
 *	la clé du tableau, permet de savoir si on traite une option `afficher_si` ou pas
 * @param string $name_html
 * Le name global dans le html du constructeur de saisie
 * L'option testée par un afficher_si (`@option@ == 'truc'`) deviendra une clé de ce name (`name['option']`)
 * Le tout sera reinjecté dans `$valeur` (`@name[option]@== 'truc'`)
 * @return void
**/
function construire_formulaire_transformer_afficher_si(&$valeur, string $cle, $name_html) {
	if ($cle == 'afficher_si' && is_string($valeur)) {
		$matches = [];
		preg_match_all('#@(.*)@#U', $valeur, $matches, PREG_PATTERN_ORDER);
		foreach ($matches as $champ_potentiel) {
			$nouveau_champ_potentiel = preg_replace('#@((?!saisie_modifiee)(?!config:)(?!plugin:).*)@#U', '@' . $name_html . '[${1}]@', $champ_potentiel);
			$valeur = str_replace($champ_potentiel, $nouveau_champ_potentiel, $valeur);
		}
	}
}

/**
 * Préparer une saisie pour la transformer en truc configurable
 * @param array $saisie description de la saisie
 * @param array $env environnement d'appel
 * @return string fond du formulaire
**/
function construire_formulaire_generer_saisie_configurable(array $saisie, array $env): string {
	// On récupère le nom
	$nom = $saisie['options']['nom'];
	$identifiant = isset($saisie['identifiant']) ? $saisie['identifiant'] : '';

	// On désactive les onglets
	unset($saisie['options']['onglet']) ;

	// On cherche si ya un formulaire de config
	$formulaire_config = isset($env['erreurs']['configurer_' . $nom]) ? $env['erreurs']['configurer_' . $nom] : '';

	// On ajoute une classe
	if (!isset($saisie['options']['conteneur_class'])) {
		$saisie['options']['conteneur_class'] = ''; // initialisation
	}
	// Compat ancien nom li_class
	if (isset($saisie['options']['li_class'])) {
		$saisie['options']['conteneur_class'] .= ' ' . $saisie['options']['li_class']; // initialisation
	}
	$saisie['options']['conteneur_class'] .= ' configurable';

	// On ajoute l'option "tout_afficher"
	$saisie['options']['tout_afficher'] = 'oui';

	// On précise (pour certaines saisies très spécifiques) que c'est dans un constructeur, pour pouvoir adapter le markup
	$saisie['options']['constructeur'] = true;

	// On ajoute les boutons d'actions, mais seulement s'il n'y a pas de configuration de lancée
	if (!$env['erreurs']) {
		$saisie['options']['conteneur_class'] .= ' actionable';
		$saisie = saisies_inserer_html(
			$saisie,
			recuperer_fond(
				'formulaires/inc-construire_formulaire-actions',
				[
					'nom' => $nom,
					'identifiant' => $identifiant,
					'formulaire_config' => $formulaire_config,
					'deplacable' => true,
				]
			),
			'debut'
		);
	} else {
		// Si une config de lancée, mettre l'ancre just au dessus
		// On ajoute une ancre pour s'y déplacer
		$saisie = saisies_inserer_html(
			$saisie,
			"\n<a id=\"configurer_$nom\"></a>\n",
			'debut'
		);
	}

	// Si ya un form de config on l'ajoute à la fin
	if (is_array($formulaire_config)) {
		// On double l'environnement
		$env2 = $env;
		// On ajoute une classe
		$saisie['options']['conteneur_class'] .= ' en_configuration';

		// Si possible on met en readonly
		$saisie['options']['readonly'] = 'oui';

		// On vire les sous-saisies s'il y en a
		if (is_array($saisie['saisies'] ?? '') && $saisie['saisies']) {
			$nb_champs_masques = count(saisies_lister_champs($saisie['saisies']));
			$saisie['saisies'] = [
				[
					'saisie' => 'explication',
					'options' => [
						'nom' => 'truc',
						'texte' => _T('saisies:construire_info_nb_champs_masques', ['nb' => $nb_champs_masques]),
					]
				]
			];
		}

		// On va ajouter le champ pour la position
		if (!($chemin_description = saisies_chercher($formulaire_config, "saisie_modifiee_{$nom}[options][description]", true))) {
			$chemin_description = [0];
			$formulaire_config = saisies_inserer(
				$formulaire_config,
				[
					'saisie' => 'fieldset',
					'options' => [
						'nom' => "saisie_modifiee_{$nom}[options][description]",
						'label' => _T('saisies:option_groupe_description'),

					],
					'saisies' => []
				],
				0
			);
		}
		$chemin_description[] = 'saisies';
		$chemin_description[] = '0'; // tout au début
		$formulaire_config = saisies_inserer(
			$formulaire_config,
			[
				'saisie' => 'position_construire_formulaire',
				'options' => [
					'nom' => "saisie_modifiee_{$nom}[position]",
					'label' => _T('saisies:construire_position_label'),
					'explication' => _T('saisies:construire_position_explication'),
					'formulaire' => $env['_contenu'],
					'saisie_a_positionner' => $nom
				]
			],
			$chemin_description
		);

		// Fieldsets racines en onglets forcés + identifiant stable
		$formulaire_config = saisies_fieldsets_en_onglets($formulaire_config, $env['_identifiant_session'], true);
		// Tout les saisies en pleine largeur
		$formulaire_config = saisies_transformer_option($formulaire_config, 'conteneur_class', '#(.*)#', '\1 pleine_largeur');

		$env2['saisies'] = $formulaire_config;

		// Un test pour savoir si on prend le _request ou bien
		$erreurs_test = $env['erreurs'];
		unset($erreurs_test['configurer_' . $nom]);
		unset($erreurs_test['message_erreur']);

		if (!$erreurs_test) {
			$env2["saisie_modifiee_$nom"] = $env2['_saisies_par_nom'][$nom];

			// Support de l'ancien format avec une seule saisie
			if (isset($env2["saisie_modifiee_$nom"]['verifier']['type'])) {
				$env2["saisie_modifiee_$nom"]['verifier'] = [
					[
						'type' => $env2["saisie_modifiee_$nom"]['verifier']['type'],
						'options' => $env2["saisie_modifiee_$nom"]['verifier']['options'] ?? []
					]
				];
			}
			// Puis convertir le tableau depuis le format API vers le format constructeur
			$verif_format_constructeur = ['type' => []];
			foreach ($env2["saisie_modifiee_$nom"]['verifier'] ?? [] as $verif) {
				$verif_format_constructeur['type'][] = $verif['type'];
				$verif_format_constructeur[$verif['type']] = [];
				$options_verif = $verif['options'] ?? [];
				foreach ($options_verif as $option_verif => $valeur_option_verif) {
					$verif_format_constructeur[$verif['type']][$option_verif] = $valeur_option_verif;
				}
			}
			$env2["saisie_modifiee_$nom"]['verifier'] = $verif_format_constructeur;
		}

		$env2['fond_generer'] = 'inclure/generer_saisies';
		$saisie = saisies_inserer_html(
			$saisie,
			'<div class="formulaire_configurer"><div class="editer-groupe formulaire_configurer-contenus">'
			. recuperer_fond(
				'inclure/generer_saisies',
				$env2
			)
			. '<div class="boutons">
				<input type="hidden" name="enregistrer_saisie" value="' . $nom . '" />
				<div class="groupe-btns">
					<button type="submit" class="submit btn_secondaire noscroll" name="enregistrer_saisie" value="">' . _T('bouton_annuler') . '</button>
					<input type="submit" class="submit noscroll" name="enregistrer" value="' . _T('bouton_valider') . '" />
				</div>
			</div>'
			. '</div></div>',
			'fin'
		);
	}

	// On effacer l'afficher_si de la saisie qu'on édite car vu qu'on l'édite on veut systématiquement la voir. En revanche, les options globales peuvent encore avoir des afficher_si.
	if (substr($saisie['options']['nom'], 0, 16) !== 'options_globales') {
		unset($saisie['options']['afficher_si']);
	}

	// On gère les problématiques de saisies dépubliées
	if ($saisie['options']['depublie'] ?? '') {
		$saisie['options']['conteneur_class'] .=  ' depublie';
	}

	// On génère le HTML de la saisie
	$html = saisies_generer_html($saisie, array_merge($env, ['_toujour_editable' => true]));
	return $html;
}

/**
 * Callback d'array_filter()
 * Permet de tester tout ce qui n'est pas un contenu vide
 * En revanche la valeur '0' renvoie true
 * @internal
 * @param $var La variable a tester
 * @return bool L'accepte-t-on ?
**/
function saisie_option_contenu_vide($var): bool {
	if (!$var) {
		if (is_string($var) && strlen($var)) {
			return true;
		}
		return false;
	}
	return true;
}

function saisies_groupe_inserer($formulaire_actuel, $saisie) {
	include_spip('inclure/configurer_saisie_fonctions');

	//le groupe de saisies
	$saisies_charger_infos = saisies_charger_infos($saisie, $saisies_repertoire = 'saisies/groupes');

	//le tableau est-il en options ou en saisies ?
	$classique_yaml = count($saisies_charger_infos['options']);
	$formidable_yaml = count($saisies_charger_infos['saisies']);
	if ($classique_yaml > 0) {
		$champ_options = 'options';
	}
	if ($formidable_yaml > 0) {
		$champ_options = 'saisies';
	}

	//les champs du groupe
	foreach ($saisies_charger_infos[$champ_options] as $info_saisie) {
		unset($info_saisie['identifiant']);
		$construire_nom = $info_saisie[$champ_options]['nom'] ? $info_saisie[$champ_options]['nom'] : $info_saisie['saisie'];
		$nom = $info_saisie[$champ_options]['nom'] = saisies_generer_nom($formulaire_actuel, $construire_nom);

		$formulaire_actuel = saisies_inserer($formulaire_actuel, $info_saisie);
	}

	return $formulaire_actuel;
}

/**
 * Insérer dans le formulaire de config
 * l'option pour dépublier une saisie
 * @param array $saisies
 * @return array
**/
function construire_formulaire_config_inserer_option_depublie(array $saisies): array {


	$inserer = [
		'saisie' => 'case',
		'options' => [
			'nom' => 'depublie',
			'label_case' => '<:saisies:option_depublie_label_case:>',
			'explication' => '<:saisies:option_depublie_explication:>',
			'conteneur_class' => 'pleine_largeur',
		]
	];

	// Cas de la saisie `conteneur_inline`
	if (!saisies_chercher($saisies, 'affichage')) {
		$saisies = saisies_inserer($saisies, $inserer, [0, 'saisies', 0]);
	} else {
		$saisies = saisies_inserer($saisies, $inserer, '[affichage][0]');
	}

	return $saisies;
}

