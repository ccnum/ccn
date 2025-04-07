<?php

/**
 * Utilisation des pipelines
 *
 * @package SPIP\Saisies\Pipelines
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Préambule, les constantes pour afficher_si
 **/
if (!defined('_SAISIES_AFFICHER_SI_JS_SHOW')) {
	define('_SAISIES_AFFICHER_SI_JS_SHOW', 'slideDown(800)');
}
if (!defined('_SAISIES_AFFICHER_SI_JS_HIDE')) {
	define('_SAISIES_AFFICHER_SI_JS_HIDE', 'slideUp(800)');
}

/**
 * Ajoute les scripts JS et CSS de saisies dans l'espace privé
 *
 * @param string $flux
 * @return string
 **/
function saisies_header_prive($flux) {
	foreach (['javascript/saisies.js', 'javascript/saisies_afficher_si.js'] as $script) {
		$js = timestamp(find_in_path($script));
		$flux .= "\n<script type='text/javascript' src='$js'></script>\n";
	}
	$js = timestamp(find_in_path('javascript/saisies_textarea_counter.js'));
	$flux .= '<script type="text/javascript">saisies_caracteres_restants = "' . _T('saisies:caracteres_restants') . '";</script>';
	$flux .= "\n<script type='text/javascript' src='$js'></script>\n";
	$flux .= afficher_si_definir_fonctions();
	include_spip('inc/filtres');
	$css = timestamp(find_in_path('css/saisies.css'));
	$flux .= "\n<link rel='stylesheet' href='$css' type='text/css' media='all' />\n";
	$css_constructeur = timestamp(find_in_path('css/formulaires_constructeur.css'));
	$flux .= "\n<link rel='stylesheet' href='$css_constructeur' type='text/css' />\n";

	return $flux;
}

/**
 * Insérer automatiquement les scripts JS et CSS de saisies dans toutes les pages de l'espace public
 * @param array $flux
 * @return array $flux modifié
 **/
function saisies_insert_head($flux) {
	include_spip('inc/config');
	if (lire_config('saisies/assets_global')) {
		$flux .= saisies_generer_head();
	}
	return $flux;
}

/**
 * Ajoute les scripts JS et CSS de saisies dans l'espace public
 *
 * Ajoute également de quoi gérer le datepicker de la saisie date si
 * celle-ci est utilisée dans la page.
 *
 * @param string $flux
 * @return string
 **/
function saisies_affichage_final($flux) {
	include_spip('inc/config');
	if (
		!lire_config('saisies/assets_global')
		&& isset($GLOBALS['html'])
		&& $GLOBALS['html'] // si c'est bien du HTML
		&& strpos($flux, '<!--!inserer_saisie_editer-->') !== false // et qu'on a au moins une saisie
		&& strpos($flux, '<head') !== false // et qu'on a la balise <head> quelque part
	) {
		$head = saisies_generer_head($flux, true);
		$flux = str_replace('</head>', "$head</head>", $flux);
	}

	return $flux;
}

/**
 * Génère le contenu du head pour les saisies (css et js)
 * @param string $html_content le contenu html où l'on teste la présence de saisies
 * @param bool (false) $tester_saisies
 *
 * @return string
 */
function saisies_generer_head($html_content = '', $tester_saisies = false) {

	$flux = '';
	include_spip('inc/filtres');
	// Pas de saisie alors qu'on veux tester leur présence > hop, on retourne direct
	if ($tester_saisies && strpos($html_content, '<!--!inserer_saisie_editer-->') === false) {
		return $flux;
	}

	$css = timestamp(find_in_path('css/saisies.css'));
	$ins_css = "\n<link rel='stylesheet' href='$css' type='text/css' media='all' />\n";

	$flux = $ins_css . $flux;

	// on insère le JS à la fin du <head>
	$ins_js = '';
	// JS général
	$js = timestamp(find_in_path('javascript/saisies.js'));
	$ins_js .= "\n<script type='text/javascript' src='$js'></script>\n";


	// si on a une saisie de type textarea avec maxlength, on va charger un script
	if (!$tester_saisies || (strpos($html_content, 'textarea') !== false && strpos($html_content, 'maxlength') !== false)) {
		$js = timestamp(find_in_path('javascript/saisies_textarea_counter.js'));
		$ins_js .= '<script type="text/javascript">saisies_caracteres_restants = "' . _T('saisies:caracteres_restants') . '";</script>';
		$ins_js .= "\n<script type='text/javascript' src='$js'></script>\n";
	}
	// Afficher_si
	if (!$tester_saisies || strpos($html_content, 'afficher_si') !== false) {
		$ins_js .= afficher_si_definir_fonctions();
		$js = timestamp(find_in_path('javascript/saisies_afficher_si.js'));
		$ins_js .= "\n<script type='text/javascript' src='$js'></script>\n";
	}

	$flux = $flux . $ins_js;

	return $flux;
}

/**
 * Déclarer automatiquement les champs d'un formulaire CVT qui déclare des saisies
 *
 * Recherche une fonction `formulaires_XX_saisies_dist` et l'utilise si elle
 * est présente. Cette fonction doit retourner une liste de saisies dont on se
 * sert alors pour calculer les champs utilisés dans le formulaire.
 *
 * @param array $flux
 * @return array
 **/
function saisies_formulaire_charger($flux) {
	// Si le flux data est inexistant, on quitte : Le CVT d'origine a décidé de ne pas continuer
	if (!is_array($flux['data'])) {
		return $flux;
	}

	// Il faut que la fonction existe et qu'elle retourne bien un tableau
	include_spip('inc/saisies');
	$saisies = saisies_chercher_formulaire($flux['args']['form'], $flux['args']['args'], $flux['args']['je_suis_poste']);

	if ($saisies) {
		// Si c'est un formulaire de config, on va régler automatiquement les defaut
		if (strpos($flux['args']['form'], 'configurer_') === 0) {
			$par_nom = saisies_lister_par_nom($saisies);
			$contexte = [];
			if (isset($par_nom['_meta_casier'])) {
				$meta = $par_nom['_meta_casier']['options']['defaut'];
			} else {
				$meta = str_replace('configurer_', '', $flux['args']['form']);
			}
			$saisies = saisies_preremplir_defaut_depuis_config($saisies, $meta);
		}

		// On rajoute ce contexte en défaut de ce qui existe déjà (qui est prioritaire)
		$contexte = saisies_lister_valeurs_defaut($saisies);
		$flux['data'] = array_merge($contexte, $flux['data']);

		// On cherche si on gère des étapes
		if ($etapes = saisies_lister_par_etapes($saisies, false, $flux['data'])) {
			$flux['data']['_etapes'] = count($etapes);
			$flux['data']['_saisies_par_etapes'] = $etapes;

			$etape_courante = _request('_etape') ?? 1;
			$flux['data']['depublie'] = $etapes["etape_{$etape_courante}"]['options']['depublie'] ?? '';

			// Construction du tableau resumé des étapes futures
			$options_resume = saisies_determiner_options_demandees_resumer_etapes_futures($saisies['options']);
			$resume_etapes_futures = saisies_resumer_etapes_futures($etapes, $etape_courante, $options_resume);
			// Convertir les afficher_si en code JS
			$resume_etapes_futures = array_map(function ($i) use ($etapes) {
				if (!isset($i['afficher_si'])) {
					return $i;
				}
				$i['afficher_si'] = saisies_afficher_si_js($i['afficher_si'], $etapes);
				$i['afficher_si'] = str_replace('&quot;', '"', $i['afficher_si']);// Pour éviter que les &quot; soit à nouveau encodé par json_encode, ce qui fout la merde au decodage en JS
			  return $i;
			}, $resume_etapes_futures);
			$flux['data']['_resume_etapes_futures'] = $resume_etapes_futures;
		}
		// On ajoute le tableau complet des saisies
		$flux['data']['_saisies'] = $saisies;

		// On ajoute également un bouton submit caché qui sert exclusivement à la validation au clavier
		if (!isset($flux['data']['_hidden'])) {
			$flux['data']['_hidden'] = '';
		}
		$flux['data']['_hidden'] .= '<!--Saisies : bouton caché pour validation au clavier--><button type="submit" value="1" hidden tabindex="-1" style="display:none"></button>';

		// On ajoute les anciennes valeurs si besoin
		$flux['data']['_hidden'] .= saisies_formulaire_charger_generer_hidden_ancienne_valeur_depubliee($flux);
	}

	return $flux;
}

/**
 * Envoie sous forme d'hidden chiffré les anciennes valeurs
 * lorsqu'une saisie à des choix
 * dépubliés
 * uniquement si on vérifie les valeurs acceptables
 * @param array $flux, le flux du pipeline saisies_charger()
 * @return string
 **/
function saisies_formulaire_charger_generer_hidden_ancienne_valeur_depubliee($flux) {
	$saisies = $flux['data']['_saisies'];
	$form = $flux['args']['form'];
	$retenir_ancienne_valeur = saisies_lister_necessite_retenir_ancienne_valeur($saisies);
	$anciennes_valeurs = array_intersect_key($flux['data'], array_flip($retenir_ancienne_valeur));
	if ($anciennes_valeurs) {
		$encode = encoder_contexte_ajax($anciennes_valeurs, $form);
		return "<input type='hidden' name='_anciennes_valeurs' value='$encode' />";
	} else {
		return '';
	}
}

/**
 * Pre remplir les options 'defaut' des saisies depuis `lire_config()`
 * @param array $saisies;
 * @param string $meta_case;
 * @return array $saisies
 **/
function saisies_preremplir_defaut_depuis_config($saisies, $meta_case) {
	include_spip('inc/config');
	foreach ($saisies as &$saisie) {
		if (isset($saisie['options']['nom']) && $saisie['options']['nom'] !== '_meta_casier') {
			$nom = $saisie['options']['nom'];
			$nom = str_replace('[', '/', $nom);
			$nom = str_replace(']', '', $nom);
			$nom = trim($nom, '/');
			$config = lire_config("$meta_case/$nom");
			if ($config !== null) {
				$saisie['options']['defaut'] = $config;
			}
			$contexte[$nom] = isset($saisies['options']['defaut']) ? $saisies['options']['defaut'] : '';
			if (isset($saisie['saisies'])) {
				$saisie['saisies'] = saisies_preremplir_defaut_depuis_config($saisie['saisies'], $meta_case);
			}
		}
	}
	return $saisies;
}

/**
 * Aiguiller CVT vers un squelette propre à Saisies lorsqu'on a déclaré des saisies et qu'il n'y a pas déjà un HTML
 *
 * Dans le cadre d'un formulaire CVT demandé, si ce formulaire a déclaré des saisies, et
 * qu'il n'y a pas de squelette spécifique pour afficher le HTML du formulaire,
 * alors on utilise le formulaire générique intégré au plugin saisie, qui calculera le HTML
 * à partir de la déclaration des saisies indiquées.
 *
 * @see saisies_formulaire_charger()
 *
 * @param array $flux
 * @return array
 **/
function saisies_styliser($flux) {
	if (
		// Si on cherche un squelette de formulaire
		strncmp($flux['args']['fond'], 'formulaires/', 12) == 0
		// Et que ce n'est pas une inclusion (on teste ça pour l'instant mais c'est pas très générique)
		&& strpos($flux['args']['fond'], 'inc-', 12) === false
		// Et qu'il y a des saisies dans le contexte
		&& isset($flux['args']['contexte']['_saisies'])
		// Et que le fichier choisi est vide ou n'existe pas
		&& include_spip('inc/flock')
		&& ($ext = $flux['args']['ext'])
		&& lire_fichier($flux['data'] . '.' . $ext, $contenu_squelette)
		&& !trim($contenu_squelette)
	) {
		$flux['data'] = preg_replace("/\.$ext$/", '', find_in_path("formulaires/inc-saisies-cvt.$ext"));
	}

	return $flux;
}

/**
 * Ajouter les vérifications déclarées dans la fonction "saisies" du CVT
 *
 * Si un formulaire CVT a déclaré des saisies, on utilise sa déclaration
 * pour effectuer les vérifications du formulaire.
 *
 * @see saisies_formulaire_charger()
 * @uses saisies_verifier()
 *
 * @param array $flux
 *     'data' Liste des erreurs du formulaire
 *     'args' Arguments du pipeline
 * @return array
 *     $flux ajusté
 */
function saisies_formulaire_verifier($flux) {
	// Il faut que la fonction existe et qu'elle retourne bien un tableau
	include_spip('inc/saisies');
	$form = $flux['args']['form'];
	$args_du_form = $flux['args']['args'];

	$saisies = saisies_chercher_formulaire($form, $args_du_form, true);
	if ($saisies && !saisies_lister_par_etapes($saisies, true)) {
		$erreurs = saisies_verifier($saisies);

		if ($erreurs && !isset($erreurs['message_erreur'])) {
			$erreurs['message_erreur'] = _T('saisies:erreur_generique');
		}
		if (!is_array($flux['data'])) {
			$flux['data'] = [];
		}

		$flux['data'] = array_merge($erreurs, $flux['data']);
	}

	// Vérification du formulaire après la vérification des saisies
	$verifier_post_saisies = charger_fonction('verifier_post_saisies', "formulaires/$form/", true);
	if ($verifier_post_saisies) {
		$flux['data'] =  array_merge($flux['data'], call_user_func_array($verifier_post_saisies, $args_du_form));// Lorsqu'on sera en PHP 8 ++ only, transformer en = $verifier_post_saisies(...$args_du_form);
	}
	$flux['data'] = pipeline('formulaire_verifier_post_saisies', $flux);

	// Prévisu au dessus du formulaire
	$flux = saisies_verifier_previsualisation_au_dessus(
		$flux,
		$saisies,
		saisies_request('_valider_previsu')
	);

	return $flux;
}

/**
 * Modifie si besoin le $flux de vérification pour activer la prévisualisation au dessus
 * @param array $flux comme pour le pipeline
 * @param array $saisies tableau de saisies
 * @param string|null $valider_previsu, le résultat de _request('valider_previsu')
 * @return array $flux idem
**/
function saisies_verifier_previsualisation_au_dessus(array $flux, array $saisies, ?string $valider_previsu): array {
	$previsualisation_mode = $saisies['options']['previsualisation_mode'] ?? '';
	if (!$previsualisation_mode || $previsualisation_mode != 'dessus') {
		return $flux;
	}

	if (!$valider_previsu && !$flux['data']) {
		$flux['data']['_previsu'] = true;
	}

	return $flux;
}

/**
 * Ajouter les vérifications déclarées dans la fonction "saisies" du CVT mais pour les étapes
 *
 * @see saisies_formulaire_charger()
 * @uses saisies_verifier()
 *
 * @param array $flux
 *     Liste des erreurs du formulaire
 * @return array
 *     iste des erreurs
 */
function saisies_formulaire_verifier_etape($flux) {
	$form = $flux['args']['form'];
	$args_du_form = $flux['args']['args'];

	$saisies = saisies_chercher_formulaire($form, $args_du_form, true);
	if ($saisies) {
		//A quelle étape est-on ?
		$etape = $flux['args']['etape'];
		$erreurs = saisies_verifier($saisies, true, $etape);
		$flux['data'] = array_merge($erreurs, $flux['data']);
		// Si on est en train de vérifier la dernière étape courante et qu'elle n'a pas d'erreur, alors voir si on peut sauter les étapes suivantes
		if (
			$etape == ($flux['args']['etape_saisie'] ?? -1)
			&& empty($flux['data'])
			&& (!$flux['args']['etape_demandee'] || $flux['args']['etape_demandee'] > $etape)
		) {
			$avance_rapide = saisies_determiner_avance_rapide($saisies, $etape);
			if ($avance_rapide && $avance_rapide != $etape + 1) {
				set_request('aller_a_etape', $avance_rapide);
				$flux['args']['etape_demandee'] = $avance_rapide;
			}
		}
	}


	// Vérification du formulaire après la vérification des saisies
	$verifier_etape_post_saisies = charger_fonction('verifier_etape_post_saisies', "formulaires/$form/", true);
	if ($verifier_etape_post_saisies) {
		$flux['data'] =  array_merge($flux['data'], call_user_func_array($verifier_etape_post_saisies, array_merge([$etape], $args_du_form)));// Lorsqu'on sera en PHP 8 ++ only, transformer en = $verifier_post_saisies(...$args_du_form);
	}
	$flux['data'] = pipeline('formulaire_verifier_etape_post_saisies', $flux);

	return $flux;
}

/**
 * 1. À la reception d'un formulaire, rechercher les saisie qui autorise choix_alternatif, et mettre si besoin la valeur envoyé en choix alternatif comme valeur principal.
 * 2. À la réception d'un formulaire de config
 * rechercher les input avec l'option cle_secrete.
 * Si la valeur postée est vide, cela veut dire qu'on conserve celle en base.
 * Dans ce cas, reinjecter cette dernière en `set_request'.
 * Cela permet de ne pas perdre la valeur à chaque configuration du formulaire
 * @param array $flux;
 * @return array $flux;
 **/
function saisies_formulaire_receptionner($flux) {
	$saisies = saisies_chercher_formulaire($flux['args']['form'], $flux['args']['args'], true);
	if ($saisies) {
		saisies_formulaire_receptionner_retablir_cle_secrete($flux, $saisies);
		saisies_formulaire_receptionner_set_request_anciennes_valeurs($flux, $saisies);
		saisies_formulaire_receptionner_deplacer_choix_alternatif($flux, $saisies);
	}
	return $flux;
}

/**
 * Regarde les saisies qui ont un choix alternatif,
 * et met dans _request() la valeur de ce choix alternatif si jamais ce n'est pas une saisie tabulaire
 * @param array $flux le flux, a priori ne sert pas, mais permet d'avoir une signature similaire à d'autres sous fonction du pipeline _receptionner
 * @param array $saisies
 * @return void
**/
function saisies_formulaire_receptionner_deplacer_choix_alternatif(array $flux, array $saisies): void {
	$avec_choix_alternatif = saisies_lister_avec_option('choix_alternatif', $saisies);
	foreach ($avec_choix_alternatif as $saisie => $description) {
		if (saisies_request($saisie) === '@choix_alternatif') {
			saisies_set_request($saisie, saisies_request(saisies_name_suffixer($saisie, 'choix_alternatif')));
		}
	}
}
/**
 * Rétablir si besoin les anciennes clés secretes à réceptions
 * @param array $flux description d'un $flux de receptions
 * @param array $saisies description des saisies
 * @return void:
**/
function saisies_formulaire_receptionner_retablir_cle_secrete($flux, $saisies) {
	if (strpos($flux['args']['form'], 'configurer_') === 0) {
		$config = str_replace('configurer_', '', $flux['args']['form']);
		$avec_cle_secrete = saisies_lister_avec_option('cle_secrete', $saisies);
		foreach ($avec_cle_secrete as $name => $description) {
			if (!saisies_request($name)) {
				$name = saisie_name2nom($name);
				saisies_set_request($name, lire_config("$config/$name"));
			}
		}
	}
}
/**
 * Retourne une chaine renvoyant les functions js de masquage/affiche
 **/
function afficher_si_definir_fonctions() {
	return '<script>
		function afficher_si_show(src) {
			src.' . _SAISIES_AFFICHER_SI_JS_SHOW . ';
}
function afficher_si_hide(src) {
	src.' . _SAISIES_AFFICHER_SI_JS_HIDE . ';
}
	</script>';
}

/**
 * Bien que proposé avec le plugin verifier
 * la vérification `fichiers` n'a de sens que si la saisie `fichiers` du plugin CVT-Upload est disponible.
 * @param array $flux
 * @return array $flux
**/
function saisies_saisies_verifier_lister_disponibles(array $flux): array {
	unset($flux['data']['disponibles']['fichiers']);// CVTUpload s'occupe tout seul de remettre cette verification
}

/**
 * Bien que livrée avec saisies,
 * la saisie `mot` ne doit pas être proposée si les mots ne sont pas activés
 * On se branche sur le pipeline pour que la fonction originelle soit la plus simple possible,
 * qu'elle n'ait pas à se préoccuper de l'état de la config SPIP
 * @param array $saisies les saisies
 * @return array $saisies les saisies sans la saisies mot
**/
function saisies_saisies_lister_disponibles(array $saisies): array {

	include_spip('inc/config');
	if (lire_config('articles_mots') !== 'oui') {
		unset($saisies['mot']);
	}
	return $saisies;
}

/**
 * Appliquer le chiffrement des name de nospam sur les `afficher_si`
 * @param array $flux
 * @return array
**/
function saisies_formulaire_fond(array $flux): array {
	// Si pas de nospam, on n'a besoin de rien
	if (!test_plugin_actif('nospam')) {
		return $flux;
	}

	$data = &$flux['data'];
	$args = $flux['args'];
	$form = $args['form'];

	// Si chiffrement, au boulot!
	if (
		(
			defined('_SPAM_ENCRYPT_NAME')
			&& _SPAM_ENCRYPT_NAME
			&& in_array($form, nospam_lister_formulaires())
		)
		|| !empty($args['contexte']['_nospam_encrypt'])
	) {
		include_spip('inc/nospam_encrypt');
		preg_match_all('#data-afficher_si=(".*"|\'.*\')#U', $data, $matches);
		$data_afficher_si = $matches[1];
		$data_afficher_si = array_unique($data_afficher_si);


		// Le jeton permettant de chiffrer
		$jeton = '';
		if (preg_match(",<input type='hidden' name='_jeton' value='([^>]*)' />,Uims", $data, $m)) {
			$jeton = $m[1];
		}

		foreach ($data_afficher_si as $afficher_si) {
			// Chercher tous les tests individuels
			preg_match_all('#afficher_si\((.*)\)#U', $afficher_si, $tests_individuels);

			$tests_individuels[1] = array_unique($tests_individuels[1]);
			foreach ($tests_individuels[1] as $test) {
				// Décoder le json, chercher le champ
				$json_test = str_replace('&quot;', '"', $test);
				$tableau_test = json_decode($json_test, true);
				$champ_test = $tableau_test['champ'];

				// Chiffrer le champ.
				// Dans le cas des truc[chose] : on ne brouille que truc, dixit le code de nospam
				$champ_test = explode('[', $champ_test);
				$champ_test[0] = nospam_name_encode($champ_test[0], $jeton);
				$champ_test = implode('[', $champ_test);

				// A condition que par ailleurs nospam ait deja mis cette valeur chiffré dans le formulaire
				// Mettre la valeur chiffrée dans le tableau decrivant, rencoder le json, injecter le résultat dans le formulaire à la place de la version non chiffré
				// Le `à condition` permet de ne pas chiffrer indument un test sur un champ hidden
				if (strpos($data, $champ_test) !== false) {
					$tableau_test['champ'] = $champ_test;
					$json_test = json_encode($tableau_test);
					$json_test = str_replace('"', '&quot;', $json_test);
					$data = str_replace($test, $json_test, $data);
				}
			}
		}
	}

	return $flux;
}

/**
 * Prendre les anciennes valeurs envoyées par _request et les mettres en contexte globale après les avoirs décodées
 * @param string $flux
 * @param array $saisies (ne sert à rien, mais pour // d'écriture)
 * @return void
 **/
function saisies_formulaire_receptionner_set_request_anciennes_valeurs(array $flux): void {
	include_spip('inc/filtres');
	$form = $flux['args']['form'] ?? '';
	$anciennes_valeurs = saisies_request('_anciennes_valeurs');
	if ($anciennes_valeurs) {
		$anciennes_valeurs = decoder_contexte_ajax($anciennes_valeurs, $form);
		saisies_set_request('anciennes_valeurs', $anciennes_valeurs);
	}
}
