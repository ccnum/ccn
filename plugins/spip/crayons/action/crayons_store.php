<?php

/**
 * Crayons
 * plugin for spip
 * (c) Fil, toggg 2006-2013
 * licence GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
if (function_exists('include_fichiers_fonctions')) {
	include_fichiers_fonctions();
}


/**
 * Point d'entrée de l'action
 */
function action_crayons_store_dist() {
	// ne pas se fatiguer si le visiteur n'a aucun droit
	if (!(function_exists('analyse_droits_rapide') ? analyse_droits_rapide() : analyse_droits_rapide_dist())) {
		action_crayons_store_return(['$erreur' => 'acces interdit']);
	}
	action_crayons_store_args();
}

/**
 * permettre de passer une autre fonction de stockage des informations
 * @param string $store
 */
function action_crayons_store_args($store = 'crayons_store') {
	header('Content-Type: text/plain; charset=' . $GLOBALS['meta']['charset']);

	include_spip ('inc/session');
	$lang = session_get('lang');
	if (!is_null($lang)) {
		lang_select($lang);
	}

	/** @uses crayons_store()  $r */
	$r = $store();

	// Si on a ete appeles par jQuery, on renvoie tout, c'est le client
	// crayons.js qui va traiter l'affichage du resultat et status
	# Attention le test $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest"
	# n'est pas bon car le cas d'un fichier uploade via iframe n'est pas detecte

	action_crayons_store_return($r);
}

function action_crayons_store_return($r) {
	// S'il y a une adresse de redirection, on renvoie vers elle
	// En cas d'erreur il faudrait ajouter &err=... dans l'url ?
	if (_request('redirect')) {
		if (!$r['$erreur'] || $r['$annuler']) {
			include_spip('inc/headers');
			redirige_par_entete(_request('redirect'));
		} else {
			echo "<h4 class='status'>" . $r['$erreur'] . "</h4>\n";

			foreach ($r as $wid => $v) {
				if ($wid !== '$erreur') {
					echo "<div id='$wid'>$v</div><hr />\n";
				}
			}
			echo "<a href='" . quote_amp(_request('redirect')) . "'>"
				. quote_amp(_request('redirect'))
				. "</a>\n";
		}
	} else {
		// Cas normal : JSON
		echo crayons_json_encode($r);
	}

	exit;

}

/**
 * Verifier le hash de securite
 * @param $w
 * @param $secu
 * @return bool
 */
function crayons_verif_secu($w, $secu) {
	return (
		$secu == md5($GLOBALS['meta']['alea_ephemere'] . '=' . $w)
		|| $secu == md5($GLOBALS['meta']['alea_ephemere_ancien'] . '=' . $w)
	);
}

/**
 * Recuperer les valeurs postees par les crayons
 * @return array|bool
 */
function crayons_recupere_post() {
	$results = [];

	if (isset($_POST['crayons']) && is_array($_POST['crayons'])) {
		foreach ($_POST['crayons'] as $crayon) {
			$name = $_POST['name_' . $crayon];
			$content = [];
			if ($_POST['fields_' . $crayon]) {
				foreach (explode(',', $_POST['fields_' . $crayon]) as $field) {
					// cas particulier d'un envoi de fichier
					if (isset($_FILES['content_' . $crayon . '_' . $field])) {
						if ($_FILES['content_' . $crayon . '_' . $field]['size'] > 0) {
							$content[$field] = $_FILES['content_' . $crayon . '_' . $field];
						} else {
							$content[$field] = false;
						}
						// cf. valeur passee dans crayon->md5() : false ou filemtime() du logo
					} else {
						/**
						 * le changement de charset n'est plus necessaire
						 * depuis jquery 1.5 (feature non documentee de jquery!)
						 */
						if (isset($_POST['content_' . $crayon . '_' . $field])) {
							$content[$field] = is_array($_POST['content_' . $crayon . '_' . $field])
								? implode(',', $_POST['content_' . $crayon . '_' . $field])
								: $_POST['content_' . $crayon . '_' . $field];
						} else {
							$content[$field] = null;
						}
					}
				}
			}

			// Si les donnees POSTees ne correspondent pas a leur md5,
			// il faut les traiter
			if (
				isset($name)
				&& md5(serialize($content)) != $_POST['md5_' . $crayon]
			) {
				if (
					!isset($_POST['secu_' . $crayon])
					|| crayons_verif_secu($name, $_POST['secu_' . $crayon])
				) {
					$results[] = [$name, $content, $_POST['md5_' . $crayon], $crayon];
				} else {
					return false; // erreur secu
				}
			} else {
				// cas inchange
				$results[] = [$name, $content, false, $crayon];
			}
		}
	}
	return $results;
}

/**
 * @param array $options
 * @return array
 */
function crayons_store($options = []) {
	// permettre de surcharger les fonctions de recuperation des valeurs
	// et de sauvegardes de celles-ci
	$options = array_merge([
			'f_get_valeur' => 'crayons_store_get_valeur',
			'f_set_modifs' => 'crayons_store_set_modifs',
		], $options);

	include_spip('inc/crayons');
	$wdgcfg = wdgcfg();

	$return = ['$erreur' => ''];

	$postees = crayons_recupere_post();

	$invalides = $modifs = $updates = [];

	if (!is_array($postees)) {
		$return['$erreur'] = _U('crayons:donnees_mal_formatees');
	} else {
		foreach ($postees as $postee) {
			if ($postee[2] !== false) {
				$name = $postee[0];
				$content = $postee[1];

				if ($content && preg_match(_PREG_CRAYON, 'crayon ' . $name, $regs)) {
					[, $crayon, $crayon_type, $modele, $id] = $regs;
					$wid = $postee[3];
					$table_sql = '';

					$champs = array_keys($content);
					$champs_str_log = implode(',', $champs);
					[$distant, $table, $type] = distant_table($crayon_type);
					/** @uses autoriser_crayonner_dist() */
					if (!crayons_get_table($crayon_type, $table_sql)
					  || !autoriser('crayonner', $type, $id, null, ['crayon_type' => $crayon_type, 'type' => $type, 'distant' => $distant, 'modele' => $modele, 'champ' => $champs])) {
						spip_log("autoriser('crayonner', $type, $id, null, ['crayon_type' => $crayon_type, 'type' => $type, 'distant' => $distant, 'modele' => $modele, 'champ' => [$champs_str_log]]) : NIET", 'crayons' . _LOG_ERREUR);
						$return['$erreur'] =
							"$crayon_type $id: " . _U('crayons:non_autorise');
					} else {
						spip_log("autoriser('crayonner', $type, $id, null, ['crayon_type' => $crayon_type, 'type' => $type, 'distant' => $distant, 'modele' => $modele, 'champ' => [$champs_str_log]]) : OK", 'crayons' . _LOG_DEBUG);
						// recuperer l'existant pour calculer son md5 et verifier
						// qu'il n'a pas ete modifie entre-temps
						$get_valeur = $options['f_get_valeur'];
						/** @uses crayons_store_get_valeur() */
						$data = $get_valeur($content, $regs);

						$md5 = md5(serialize($data));

						// est-ce que le champ a ete modifie dans la base entre-temps ?
						// si oui, la modif demandee correspond peut-etre
						// a la nouvelle valeur ? dans ce cas on procede
						// comme si "pas de modification", sinon erreur
						if ($md5 != $postee[2] && $md5 !== md5(serialize($content))) {
							$return['$erreur'] = "$crayon_type $id $modele: " . _U('crayons:modifie_par_ailleurs');
						}

						// Vérifications, en retournant presque comme le pipeline formulaire_verifier
						// On permet aussi de modifier la valeur soumise, si une valeur est retournée dans normaliser.
						$data = pipeline(
							'crayons_verifier',
							[
								'args' => [
									'type' => $crayon_type,
									'modele' => $modele,
									'id' => $id,
									'content' => $content,
									'wid' => $wid,
								],
								'data' => [
									'erreurs' => [], // couples : champ => texte d'erreur
									'normaliser' => [], // couples : champ => valeur à utiliser
								],
							]
						);

						if (is_countable($data['normaliser']) ? count($data['normaliser']) : 0) {
							$content = $data['normaliser'] + $content;
						}

						if ($data['erreurs']) {
							foreach ($data['erreurs'] as $c => $e) {
								$invalides[$wid . '_' . $c]['msg'] = $e;
							}
						}

						$modifs[] = [$crayon_type, $modele, $id, $content, $wid];

						// Anciennes méthodes de vérifications.
						// Aiguillage pour verification de la saisie
						// Pour traitement ulterieur les fonctions de verifications doivent renvoyer $invalides :
						// $invalides[wid_champ]['msg'] -> message de saisie invalide
						// $invalides[wid_champ]['retour'] -> caracteres invalides
						$f = 'verifier_' . $crayon_type . '_' . $modele;
						if (function_exists($f)) {
							$_invalides = $f($modifs);
							if ($_invalides && is_array($invalides)) {
								$invalides = array_merge($invalides, $_invalides);
							}
						}
					}
				}
			}
		}
	}

	if (!$modifs && !$return['$erreur']) {
		$return['$erreur'] = $wdgcfg['msgNoChange'] ? _U('crayons:pas_de_modification') : ' ';
		$return['$annuler'] = true;
	}

	// un champ invalide ... ou rien ==> on ne fait rien !
	if ($invalides !== []) {
		$return['$invalides'] = $invalides;
		return $return;
	}

	// une quelconque erreur ... ou rien ==> on ne fait rien !
	if (isset($return['$erreur']) && $return['$erreur']) {
		return $return;
	}

	// on traite toutes les modifications
	// en appelant la fonction adequate de traitement
	$set_modifs = $options['f_set_modifs'];
	/** @uses crayons_store_set_modifs() */
	$return = $set_modifs($modifs, $return);

	// une quelconque erreur ... ou rien ==> on ne fait rien !
	if ($return['$erreur']) {
		return $return;
	}

	// et maintenant refaire l'affichage des crayons modifies
	include_spip('inc/texte');
	foreach ($modifs as $m) {
		[$crayon_type, $modele, $id, $content, $wid] = $m;
		if (
			!(
				($f = charger_fonction($crayon_type . '_' . $modele, 'vues', true))
				|| ($f = charger_fonction($modele, 'vues', true))
				|| ($f = charger_fonction($crayon_type, 'vues', true))
			)
		) {
			$f = 'vues_dist';
		}
		/** @see  vues_dist() */
		$return[$wid] = $f($crayon_type, $modele, $id, $content, $wid);
	}
	return $return;
}

/**
 * recuperer une valeur en fonction des parametres recuperes
 * cette fonction cherche une valeur d'une colonne d'une table SQL
 *
 * @param $content
 * @param $regs
 * @return array
 */
function crayons_store_get_valeur($content, $regs) {
	[, $crayon, $crayon_type, $modele, $id] = $regs;
	return valeur_colonne_table($crayon_type, array_keys($content), $id);
}

/**
 * stocke les valeurs envoyees dans des colonnes de table SQL
 *
 * @param $modifs
 * @param $return
 * @return mixed
 */
function crayons_store_set_modifs($modifs, $return) {
	static $tables_objet;

	// sinon on bosse : toutes les modifs ont ete acceptees
	// verifier qu'on a tout ce qu'il faut pour mettre a jour la base
	// et regrouper les mises a jour par type/id
	foreach ($modifs as $modif) {
		[$crayon_type, $modele, $id, $content, $wid] = $modif;
		[$distant, $table, $type] = distant_table($crayon_type);

		$fun = '';
		// si le crayon est un MODELE avec une fonction xxx_revision associee
		// cas ou une fonction xxx_revision existe
		if (
			function_exists($f = $crayon_type . '_' . $modele . '_revision')
			|| function_exists($f = $modele . '_revision')
			|| function_exists($f = $crayon_type . '_revision')
		) {
			$fun = $f;
		}

		if (!$fun
			&& !$distant
			&& is_numeric($id)
			&& crayons_is_table_interne(table_objet_sql($table), true)) {
			// si on edite un objet editorial bien declare
			// passer par l'API objet_modifier
			$fun = 'crayons_objet_modifier';
		}

		// c'est pas un objet editorial connu, mais on a peut etre une fonction revision_xxx specifique
		if (!$fun) {
			include_spip('inc/modifier');
			if (function_exists('revision_' . $crayon_type)) {
				$fun = 'revision_' . $crayon_type;
			}
		}

		// si on a pas reussi on passe par crayons_update() qui fera un update sql brutal
		if (!$fun || !function_exists($fun)) {
			$fun = 'crayons_update';
			// $return['$erreur'] = "$type: " . _U('crayons:non_implemente');
			// break;
		}

		if (!isset($updates[$crayon_type][$fun])) {
			$updates[$crayon_type][$fun] = [];
		}
		if (!isset($updates[$crayon_type][$fun][$id])) {
			$updates[$crayon_type][$fun][$id] = ['wdg' => [], 'chval' => []];
		}
		// pour reaffecter le retour d'erreur sql au cas ou
		$updates[$crayon_type][$fun][$id]['wdg'][] = $wid;
		foreach ($content as $champtable => $val) {
			$updates[$crayon_type][$fun][$id]['chval'][$champtable] = $val;
		}
	}

	// il manque une fonction de mise a jour ==> on ne fait rien !
	if ($return['$erreur']) {
		return $return;
	}

	// hop ! mises a jour table par table et id par id
	foreach ($updates as $crayon_type => $idschamps) {
		foreach ($idschamps as $fun => $ids) {
			foreach ($ids as $id => $champsvaleurs) {
				/* cas particulier du logo dans un crayon complexe :
				   ce n'est pas un champ de la table */

				if (isset($champsvaleurs['chval']['logo'])) {
					spip_log("crayons_store_set_modifs: logo_revision($id, , $crayon_type)" , 'crayons' . _LOG_DEBUG);
					logo_revision($id, $champsvaleurs['chval'], $crayon_type, $champsvaleurs['wdg']);
					unset($champsvaleurs['chval']['logo']);
				}
				if ($champsvaleurs['chval'] !== []) {
					// -- revisions_articles($id_article, $c) --
					spip_log("crayons_store_set_modifs: $fun($id, , $crayon_type)" , 'crayons' . _LOG_DEBUG);
					/** @see crayons_objet_modifier() */
					/** @see document_fichier_revision() */
					/** @see vignette_revision() */
					/** @see revision_meta() */
					/** @see revision_traduction() */
					/** @see crayons_update() */
					$updok = $fun($id, $champsvaleurs['chval'], $crayon_type, $champsvaleurs['wdg']);
					// Renvoyer erreur si update base distante echoue,
					// on ne regarde pas les updates base local car ils ne renvoient rien
					[$distant, $table, $type] = distant_table($crayon_type);
					if ($distant && !$updok) {
						$return['$erreur'] = "$crayon_type: " . _U('crayons:update_impossible');
					}
				}
			}
		}
	}
	return $return;
}

/**
 * VUE
 *
 * @param $crayon_type
 * @param $modele
 * @param $id
 * @param $content
 * @param $wid
 * @return array|mixed|string
 */
function vues_dist($crayon_type, $modele, $id, $content, $wid) {
	// pour ce qui a une {lang_select} par defaut dans la boucle,
	// la regler histoire d'avoir la bonne typo dans le propre()
	// NB: ceci n'a d'impact que sur le "par defaut" en bas
	[$distant, $table, $type] = distant_table($crayon_type);
	if (colonne_table($crayon_type, 'lang')) {
		$b = valeur_colonne_table($crayon_type, 'lang', $id);
		lang_select($a = array_pop($b));
	} else {
		lang_select($a = $GLOBALS['meta']['langue_site']);
	}

	// chercher vues/article_toto.html
	// sinon vues/toto.html
	$vues = [];
	if ($crayon_type === 'meta') {
		$config = explode('__', $id);
		$vues = [];
		while (!empty($config)) {
			$vues[] = 'vues/' . $type . '_' . implode('_', $config);
			array_pop($config);
		}
		$vues[] = 'vues/' . $type;
	} else {
		$vues = [
			'vues/' . $type . '_' . $modele,
			'vues/' . $modele,
			'vues/' . $type,
		];
	}
	spip_log("vues_dist: cherche " . implode(', ', $vues), 'crayons' . _LOG_DEBUG);

	foreach ($vues as $fond) {
		if (find_in_path($fond . '.html')) {
			spip_log("vues_dist: trouvé $fond", 'crayons' . _LOG_DEBUG);
			$primary = id_table_objet($table);
			$contexte = [
				$primary => $id,
				'crayon_type' => $crayon_type,
				'type' => $type,
				'crayon_modele' => $modele,
				'champ' => $modele,
				'class' => _request('class_' . $wid),
				'self' => _request('self'),
				'lang' => $GLOBALS['spip_lang']
			];
			$contexte = array_merge($contexte, $content);
			include_spip('public/assembler');
			return recuperer_fond($fond, $contexte);
		}
	}

	// vue par defaut
	// Par precaution on va rechercher la valeur
	// dans la base de donnees (meme si a priori la valeur est
	// ce qu'on vient d'envoyer, il y a nettoyage des caracteres et
	// eventuellement d'autres filtres de saisie...)
	$bdd = valeur_colonne_table($crayon_type, $modele, $id);
	if ($bdd !== []) {
		$valeur = array_pop($bdd);
	} else {
		// les champs n'ont pas ete retrouves dans la base
		// ce qui signifie a priori que nous sommes en face d'une cle primaire compose
		// et qu'un crayon a modifie un element de cette cle (c'est pas malin !)
		// dans ce cas, on reaffiche a minima ce qu'on vient de publier
		// mais il sera impossible de le reediter dans la foulee avec le meme crayon
		// (car l'identifiant du crayon se base sur l'id).
		// Il faudra donc recharger la page pour pouvoir reediter.
		if (is_scalar($id)) {
			$valeur = $content[$modele] ?? '';
		}
	}

	if (!empty($valeur) || is_scalar($valeur) && strlen($valeur)) {
		// seul spip core sait rendre les donnees
		if (function_exists('appliquer_traitement_champ')) {
			$valeur = appliquer_traitement_champ($valeur, $modele, table_objet($table));
		} else {
			$valeur = in_array($modele, ['chapo', 'texte', 'descriptif', 'ps', 'bio'])
				? propre($valeur)
				: typo($valeur);
		}
		$valeur = pipeline('crayons_vue_affichage_final', [
			'args' => [
				'type'   => $crayon_type,
				'modele' => $modele,
				'id'     => $id
			],
			'data' => $valeur
		]);
	}

	return $valeur;
}


/**
 * Fonction de mise a jour par API editer_objet
 * @param $id
 * @param $data
 * @param $crayon_type
 * @param $ref
 * @return bool|mixed|string
 */
function crayons_objet_modifier($id, $data, $crayon_type, $ref) {
	[$distant, $table, $type] = distant_table($crayon_type);
	// on ne sait pas faire distant ici
	if ($distant) {
		return false;
	}
	include_spip('action/editer_objet');

	// objet_modifier attend id_parent pour le parent et pas id_rubrique
	if (isset($data['id_rubrique']) && !isset($data['id_parent']) && $crayon_type !== 'rubrique') {
		$data['id_parent'] = $data['id_rubrique'];
	}
	return objet_modifier($type, $id, $data);
}

/**
 * Fonctions de mise a jour generique
 * fallback utilise par crayons_store_set_modifs
 *
 * @param $id
 * @param array $colval
 * @param string $crayon_type
 * @return bool|string
 */
function crayons_update($id, $colval = [], $crayon_type = '') {
	if (!$colval || $colval === []) {
		return false;
	}
	include_spip('base/abstract_sql');
	[$distant, $table, $type] = distant_table($crayon_type);

	[$nom_table, $where] = table_where($crayon_type, $id, true);
	#spip_log("crayons_update:" . $nom_table . " " . json_encode($where), 'crayons' . _LOG_DEBUG);
	if (!$nom_table || !$where || !is_array($where)) {
		return false;
	}

	// sanitizer les noms de champs dans tous les cas
	$set = [];
	foreach ($colval as $k => $v) {
		$set[crayons_sanitize_sql_field_name($k)] = $v;
	}

	if ($distant) {
		// Sur une bdd externe on utilise sql_updateq de preference ;
		// l'api sql sait gerer les prefixes contrairement a spip_query.
		$a = sql_updateq($nom_table, $set, $where, '', $distant);

		include_spip('inc/invalideur');

		// Pour une base externe doit on prefixer le type avec le nom du connecteur?
		// ex: nomconnect_objet
		suivre_invalideur($crayon_type, $modif = true);
	} else {
		// si id n'est pas scalaire ou numerique
		// ou si la cle primaire est composee : 3-4-rubrique et produit un where multiple
		// on passe par sql_updateq
		if (!is_scalar($id) || !is_numeric($id) || count($where) > 1) {
			#spip_log("crayons_update: sql_updateq $nom_table, " . json_encode($set) . ", " . json_encode($where), 'crayons' . _LOG_DEBUG);
			$a = sql_updateq($nom_table, $set, $where);
		} else {
			// modification d'une table standard via objet_modifier_champs()
			include_spip('inc/modifier');
			#spip_log("crayons_update: objet_modifier_champs $nom_table, " . ((int)$id) . ", " . json_encode($set), 'crayons' . _LOG_DEBUG);
			$res = objet_modifier_champs($crayon_type, (int)$id, [], $set);
			$a = ($res === '');
		}
	}
	return $a;
}


/**
 * Enregistre les modifications sur une configuration
 * suite à un crayon sur une meta
 *
 * La colonne est toujours 'valeur' pour ces données.
 * La donnée à enregistrer peut-être une sous partie de configuration.
 * Si c'est le cas, on gère l'enregistrement via ecrire_config.
 *
 * @param string $a
 *   Nom ou clé de la meta (descriptif_site ou demo__truc pour demo/truc)
 * @param bool|array $c
 *   Liste des champs modifiés
 *   Ici, 'valeur' normalement.
 * @return void
**/
function revision_meta($a, $c = false) {
	if (isset($c['valeur'])) {
		// Certaines cles de configuration sont echapées ici (cf #EDIT_CONFIG{demo/truc})
		$a = str_replace('__', '/', $a);
		spip_log("revision_meta: meta '$a' = '$c[valeur]'", 'crayons' . _LOG_DEBUG);
		include_spip('inc/config');
		ecrire_config($a, $c['valeur']);
		include_spip('inc/invalideur');
		suivre_invalideur('meta');
	}
}

/**
 * Enregistre les modifications dans un fichier de langue
 * suite à un crayon sur une chaine de langue
 *
 * @param string $a
 *   Nom du module de langue sous la forme module_lang (ex local_fr)
 * @param bool|array $c
 *   Liste des champs modifiés
 *   'motif_chaine_traduction' => valeur saisie
 * @return void
**/
function revision_traduction($a, $c = false) {
	$module = substr($a, 0, -3);
	$lang = substr($a, -2);
	$i18n = 'i18n_' . $module . '_' . $lang;
	if ($module == 'public' && ! test_espace_prive()) {
		$module = 'local';
	}
	foreach (chercher_module_lang($module, $lang) as $fichier_lang) {
		$action = 'maj';
		$motif = array_keys($c)[0];
		$valeur = array_values($c)[0];
		$contenu_original = file_get_contents($fichier_lang);
		$quoted_motif = "'" . str_replace("'", "\'", $motif) . "'";
		if (
			$lang !== $GLOBALS['meta']['langue_site']
			&& strpos($contenu_original, $quoted_motif) === false) {
			// motif absent du fichier et langue différente de la langue du site : ajout
			spip_log("revision_traduction: $quoted_motif absent de $fichier_lang", 'crayons' . _LOG_INFO_IMPORTANTE);
			$action = 'ajout';
			$contenu_lang_defaut = file_get_contents(str_replace("_$lang.php", '_' . $GLOBALS['meta']['langue_site'] . '.php', $fichier_lang));
			// mais il faut que la chaine de langue soit présente dans le fichier de langue de la langue par défaut
			if (strpos($contenu_lang_defaut, $quoted_motif) === false) {
			  continue; // fichier suivant
			}
		}

		$GLOBALS[$i18n][$motif] = $valeur;
		spip_log("revision_traduction: $action du motif '$motif' dans $fichier_lang", 'crayons' . _LOG_INFO_IMPORTANTE);
		$contenu_modifie = '<?php
if (!defined(\'_ECRIRE_INC_VERSION\')) return;
return [';
		foreach ($GLOBALS[$i18n] as $key => $value) {
			$quoted_key = "'" . str_replace("'", "\'", $key) . "'";
			$quoted_value = "'" . str_replace("'", "\'", $value) . "'";
			// MAJ ou insertion du motif
			if (strpos($contenu_original, "'$key'") !== false || $motif == $key) {
				$contenu_modifie .= "\t" . $quoted_key . ' => ' . $quoted_value . ",\n";
			}
		}
		$contenu_modifie .= '];';
		if (strpos($contenu_modifie, "' => '") !== false) {
			include_spip('inc/flock');
			ecrire_fichier($fichier_lang, $contenu_modifie);
			break; // on modifie uniquement le 1er fichier retourné, les autres sont des surcharges
		}
	}
}

// TODO:
// Ce modele est cense enregistrer les tags sous forme de ??
// une ligne dans un champ spip_articles.tags, et/ou des mots-cles...
function modeles_tags($id, $c) {
	var_dump($id); #id_article
	var_dump($c); # perturbant : ici on a array('id_article'=>'valeur envoyee')
}
