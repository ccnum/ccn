<?php

/**
 * Crayons
 * plugin for spip
 * (c) Fil, toggg 2006-2019
 * licence GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

define('_PREG_CRAYON', ',crayon\b[^<>\'"]+?\b((\w+)-(\w+)-(\w+(?:-\w+)*))\b,');

// Autoriser les crayons sur les tables non SPIP ?
// Par defaut : oui (pour les admins complets, si autoriser_defaut_dist()) ;
// mettre a false en cas de mutualisation par prefixe de table,
// sinon on ne peut pas garantir que les sites sont hermetiques
if (!defined('_CRAYONS_TABLES_EXTERNES')) {
	define('_CRAYONS_TABLES_EXTERNES', true);
}

// Autorisations non prevues par le core
include_spip('inc/autoriser');

include_spip('inc/crayons-json');

// Si un logo est demande, on renvoie la date dudit logo (permettra de gerer
// un "modifie par ailleurs" si la date a change, rien de plus)
function valeur_champ_logo($table, $id, $champ) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$on = $chercher_logo($id, id_table_objet($table), 'on');
	return $on ? filemtime($on[0]) : false;
}

// Idem : si un doc est demande, on renvoie la date du doc
function valeur_champ_document($table, $id, $champ) {
	return sql_getfetsel('date', 'spip_documents', 'id_document=' . (int) $id);
}

function valeur_champ_vignette($table, $id, $champ) {
	$date = null;
 $vignette = sql_getfetsel('id_vignette', 'spip_documents', 'id_document=' . (int) $id);
	if (is_numeric($vignette) && ($vignette > 0)) {
		$date = sql_getfetsel('date', 'spip_documents', 'id_document=' . (int) $vignette);
	}
	return $date ?: false;
}
// cette fonction de revision recoit le fichier upload a passer en logo
// en reference : le nom du widget, pour aller chercher d'autres donnees
// (ex: supprimer)
function logo_revision($id, $file, $type, $ref) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$_id_objet = id_table_objet($type);

	// s'assurer que $type référence bien un type d'objet connu de SPIP
	$table_sql = '';
	if (!crayons_get_table($type, $table_sql)) {
		return false;
	}

	// Chargement d'un nouveau logo ? vérifier l'extension
	if ($file['logo']) {
		include_spip('action/editer_logo');
		if (!crayons_verifier_format_image_upload(is_array($file['logo']) ? $file['logo']['name'] : $file['logo'], $type, $id)) {
			return false;
		}
		logo_modifier($type, $id, 'on', $file['logo']);
	} else {
		// Suppression du logo ?
		if (
			($wid = array_pop($ref))
			&& $_POST['content_' . $wid . '_logo_supprimer'] == 'on'
		) {
			include_spip('action/editer_logo');
			logo_supprimer($type, $id, 'on');
		}
	}

	// Reduire le logo ?
	if (
		is_array($cfg = @unserialize($GLOBALS['meta']['crayons']))
		&& ($max = (int) $cfg['reduire_logo'])
	) {
		$on = $chercher_logo($id, $_id_objet, 'on');
		include_spip('inc/filtres');
		@copy($on[0], $temp = _DIR_VAR . 'tmp' . random_int(0, 999) . '.' . $on[3]);
		$img1 = filtrer('image_reduire', $temp, $max);
		$img2 = preg_replace(',[?].*,', '', extraire_attribut($img1, 'src'));
		if (@file_exists($img2) && $img2 != $temp) {
			include_spip('action/editer_logo');
			logo_modifier($type, $id, 'on', $img2);
		}
		@unlink($temp);
	}

	return true;
}


// cette fonction de revision recoit le fichier upload a passer en document
function document_fichier_revision($id, $data, $crayon_type, $ref) {

	if (!$t = sql_fetsel('*', 'spip_documents', 'id_document=' . (int) $id)) {
		return false;
	}

	/*
	// Envoi d'une URL de document distant ?
	// TODO: verifier l'extension distante, sinon tout explose
	if ($data['fichier']
	AND preg_match(',^(https?|ftp)://.+,', $data['fichier'])) {
		include_spip('inc/modifier');
		modifier_contenu('document', $id,
			array('champs' => array('fichier', 'distant')),
			array('fichier' => $data['fichier'], 'distant' => 'oui')
		);
		return true;
	}
	else
	*/

	// Chargement d'un nouveau doc ?
	if ($data['document']) {
		$arg = $data['document'];
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');
		$actifs = $ajouter_documents($id, [$arg], '', 0, $t['mode']);
		$x = reset($actifs);
		if (is_numeric($x)) {
			return true;
		} else {
			return false;
		}
	}
}

// cette fonction de revision soit supprime la vignette d'un document,
// soit recoit le fichier upload a passer ou remplacer la vignette du document
function vignette_revision($id, $data, $crayon_type, $ref) {
	$id_vignette = null;
	if (!$s = sql_fetsel('id_document,id_vignette', 'spip_documents', 'id_document = ' . (int) $id)) {
		return false;
	}

	include_spip('inc/modifier');
	include_spip('inc/documents');
	include_spip('action/editer_document');//pour revision_document
	// Chargement d'un nouveau doc ?
	if ($data['vignette']) {
		if (!crayons_verifier_format_image_upload($data['vignette']['name'], $crayon_type, $id)) {
			return false;
		}
		if (is_numeric($s['id_vignette']) && $s['id_vignette'] > 0) {
			spip_log("vignette_revision: doc #$id suppression de la vignette #" . $s['id_vignette'], 'crayons' . _LOG_DEBUG);
			// Suppression du document
			$vignette = sql_getfetsel('fichier', 'spip_documents', 'id_document=' . (int) $s['id_vignette']);
			if (@file_exists($f = get_spip_doc($vignette))) {
				spip_log("vignette_revision: doc #$id suppression fichier vignette $f", 'crayons' . _LOG_DEBUG);
				supprimer_fichier($f);
			}
			sql_delete('spip_documents', 'id_document=' . (int) $s['id_vignette']);
			sql_delete('spip_documents_liens', 'id_document=' . (int) $s['id_vignette']);

			pipeline(
				'post_edition',
				[
					'args' => [
						'operation' => 'supprimer_document',
						'table' => 'spip_documents',
						'id_objet' => $s['id_vignette']
					],
					'data' => null
				]
			);
			$id_vignette = 0;
		}

		$arg = $data['vignette'];
		check_upload_error($arg['error']);

		spip_log("vignette_revision: doc #$id ajout vignette fichier " . json_encode($arg), 'crayons' . _LOG_DEBUG);
		// Ajout du document comme vignette
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');
		$x = $ajouter_documents(null, [$arg], '', 0, 'vignette');
		$vignette = reset($x);
		if ((int) $vignette) {
			document_modifier($id, ['id_vignette' => $vignette]);
		} elseif ($id_vignette) {
			document_modifier($id, ['id_vignette' => $id_vignette]);
		}
	} elseif (
		($wid = array_pop($ref))
		&& $_POST['content_' . $wid . '_vignette_supprimer'] == 'on'
	) {
		if (is_numeric($s['id_vignette']) && $s['id_vignette'] > 0) {
			// Suppression du document
			$vignette = sql_getfetsel('fichier', 'spip_documents', 'id_document=' . (int) $s['id_vignette']);
			if (@file_exists($f = get_spip_doc($vignette))) {
				spip_log("vignette_revision: doc #$id suppression fichier vignette $f", 'crayons' . _LOG_DEBUG);
				supprimer_fichier($f);
			}
			sql_delete('spip_documents', 'id_document=' . (int) $s['id_vignette']);
			sql_delete('spip_documents_liens', 'id_document = ' . (int) $s['id_vignette']);

			pipeline(
				'post_edition',
				[
					'args' => [
						'operation' => 'supprimer_document',
						'table' => 'spip_documents',
						'id_objet' => $s['id_vignette']
					],
					'data' => null
				]
			);

			// On remet l'id_vignette a 0
			document_modifier($s['id_document'], ['id_vignette' => 0]);
		}
	}
	return true;
}

function crayons_verifier_format_image_upload($fichier, $type, $id) {
	include_spip('inc/filtres_images_lib_mini');
	$extensions_possibles =
		function_exists('_image_extensions_logos') ?
			_image_extensions_logos(['objet' => $type, 'id_objet' => $id])
			:
			($GLOBALS['formats_logos'] ?? ['jpg', 'png', 'svg', 'gif', 'webp']);
	if (in_array('jpg', $extensions_possibles)) {
		$extensions_possibles[] = 'jpeg';
	}
	if (
		in_array(
			strtolower(pathinfo($fichier, PATHINFO_EXTENSION)),
			$extensions_possibles
		)
	) {
		return true;
	}

	return false;
}

function colonne_table($crayon_type, $col) {
	[$distant, $table, $type] = distant_table($crayon_type);
	$nom_table = '';
	if (
		!(($tabref = &crayons_get_table($crayon_type, $nom_table))
		&& isset($tabref['field'][$col])
		&& ($brut = $tabref['field'][$col]))
	) {
			return false;
	}
	$ana = explode(' ', $brut);
	$sta = 0;
	$sep = '';
	$ret = ['brut' => $brut,
		'type' => '', 'notnull' => false, 'long' => 0, 'def' => ''];
	foreach ($ana as $mot) {
		switch ($sta) {
			case 0:
				$ret['type'] = ($mot = strtolower($mot));
			case 1:
				if ($mot[strlen($mot) - 1] == ')') {
					$pos = strpos($mot, '(');
					$ret['type'] = strtolower(substr($mot, 0, $pos++));
					$vir = explode(',', substr($mot, $pos, -1));
					if ($ret['type'] == 'enum') {
						$ret['enum'] = $vir;
					} elseif (count($vir) > 1) {
						$ret['long'] = $vir;
					} else {
						$ret['long'] = $vir[0];
					}
					$sta = 1;
					break;
				}
				if (!$sta) {
					$sta = 1;
					break;
				}
			case 2:
				switch (strtolower($mot)) {
					case 'not':
						$sta = 3;
						break;
					case 'default':
						$sta = 4;
						break;
				}
				break;
			case 3:
				$ret['notnull'] = strtolower($mot) == 'null';
				$sta = 2;
				break;
			case 4:
				$df1 = strpos('"\'', (string) $mot[0]) !== false ? $mot[0] : '';
				$sta = 5;
			case 5:
				$ret['def'] .= $sep . $mot;
				if (!$df1) {
					$sta = 2;
					break;
				}
				if ($df1 == $mot[strlen($mot) - 1]) {
					$ret['def'] = substr($ret['def'], 1, -1);
					$sta = 2;
				}
				$sep = ' ';
				break;
		}
	}
	return $ret;
}


/**
 * Obtient le nom de la table ainsi que sa ou ses clés primaires
 *
 * @param string $crayon_type
 *     Table sur laquelle s'applique le crayon.
 *     Ce type peut contenir le nom d'un connecteur distant tel que `{connect}__{table}`
 *
 * @return array|bool
 *     - false si on ne trouve pas de table ou de table ayant de clé primaire
 *     - liste :
 *     - - nom de la table sql
 *     - - tableau des noms de clés primaires
**/
function crayons_get_table_name_and_primary($crayon_type) {
	static $types = [];
	if (isset($types[$crayon_type])) {
		return $types[$crayon_type];
	}

	$nom_table = '';
	if (
		($tabref = &crayons_get_table($crayon_type, $nom_table))
		&& ($tabid = explode(',', $tabref['key']['PRIMARY KEY']))
	) {
		return $types[$crayon_type] = [$nom_table, $tabid];
	}
	spip_log('crayons: table ' . $crayon_type . ' inconnue ou sans cle primaire', 'crayons' . _LOG_ERREUR);
	return $types[$crayon_type] = false;
}


function table_where($crayon_type, $id, $where_en_tableau = false) {
	if (!$infos = crayons_get_table_name_and_primary($crayon_type)) {
		return [false, false];
	}

	[$nom_table, $tabid] = $infos;

	if (is_scalar($id)) {
		$id = explode('-', $id);
	}
	$where = [];
	foreach ($id as $idcol => $idval) {
		$col_name = is_int($idcol) ? trim($tabid[$idcol]) : $idcol;
		$where[] = '`' . crayons_sanitize_sql_field_name($col_name) . '`=' . sql_quote($idval);
	}

	// sortie tableau pour sql_updateq
	if (!$where_en_tableau) {
		$where = "(" . implode(' AND ', $where) . ")";
	}

	return [$nom_table, $where];
}

/**
 * @param string $crayon_type
 * @param array $cols
 * @param $id
 * @return array|false
 */
function valeur_colonne_table_dist($crayon_type, $cols, $id) {

	#spip_log("valeur_colonne_table_dist $type $id cols: ".json_encode($col), 'crayons');
	// Table introuvable ou sans clé primaire
	if (!$infos = crayons_get_table_name_and_primary($crayon_type)) {
		#spip_log("valeur_colonne_table_dist $type $id infos:".json_encode($infos), 'crayons');
		return false;
	}
	#spip_log("valeur_colonne_table_dist $type $id infos:".json_encode($infos), 'crayons');
	$table = reset($infos);

	$r = [];

	// valeurs non SQL
	foreach ($cols as $champ) {
		if (
			$champ === crayons_sanitize_sql_field_name($champ)
			&& (
				function_exists($f = 'valeur_champ_' . $table . '_' . $champ)
				|| function_exists($f = 'valeur_champ_' . $champ)
			)
		) {
			/** @see valeur_champ_document() */
			/** @see valeur_champ_logo() */
			/** @see valeur_champ_vignette() */
			$r[$champ] = $f($table, $id, $champ);
			$cols = array_diff($cols, [$champ]);
		}
	}

	// valeurs SQL
	if (is_countable($cols) ? count($cols) : 0) {
		include_spip('base/abstract_sql');
		[$distant, $table, $type] = distant_table($crayon_type);
		[$nom_table, $where] = table_where($crayon_type, $id, true);
		if ($nom_table && $where) {
			$select = array_map( 'crayons_sanitize_sql_field_name', $cols);
			$select = '`' . implode('`, `', $select) . '`';
			$row = sql_fetsel($select, $nom_table, $where, '', '', '', '', $distant);
			if (!empty($row) && is_array($row)) {
				$r = array_merge($r, $row);
			}
		}
	}

	return $r;
}

/**
 * Sanitizer un nom de champ SQL
 * @param string $field
 * @return string
 */
function crayons_sanitize_sql_field_name(string $field) {
	if (strlen($field) > 256) {
		$field = substr($field, 0, 256);
	}
	return preg_replace('/\W+/i', '_', trim($field)) ?? '';
}

/**
 * Extrait la valeur d'une ou plusieurs colonnes d'une table
 *
 * @param string $crayon_type
 *   Type étendu d'objet de la table (article ou serveur__article pour un article distant)
 * @param string|array $cols
 *   Nom de la ou des colonnes (ps)
 * @param string $id
 *   Identifiant de l'objet
 * @return array
 *   Couples Nom de la colonne => Contenu de la colonne
**/
function valeur_colonne_table($crayon_type, $cols, $id) {
	if (!is_array($cols)) {
		$cols = [$cols];
	}

	if (
		function_exists($f = $crayon_type . '_valeur_colonne_table')
		|| function_exists($f .= '_dist')
		|| ($f = 'valeur_colonne_table_dist')
	) {
		/** @see  valeur_colonne_table_dist() */
		/** @see  meta_valeur_colonne_table_dist() */
		/** @see  traduction_valeur_colonne_table_dist() */
		return $f($crayon_type, $cols, $id);
	}
}

/**
 * Extrait la valeur d'une configuration en meta
 *
 * Pour ces données, il n'y a toujours qu'une colonne (valeur),
 * mais on gère l'enregistrement et la lecture via lire_config ou ecrire_config
 * dès que l'on demande des sous parties d'une configuration.
 *
 * On ne retourne alors ici dans 'valeur' que la sous-partie demandée si
 * c'est le cas.
 *
 * @param string $crayon_type
 *   type d'objet etendu
 * @param array $col
 *   Nom des colonnes (valeur)
 * @param string $id
 *   Nom ou clé de configuration (descriptif_site ou demo__truc pour demo/truc)
 * @return array
 *   Couple valeur => Contenu de la configuration
**/
function meta_valeur_colonne_table_dist($crayon_type, $col, $id) {
	// Certaines clés de configuration sont echapées ici (cf #EDIT_CONFIG{demo/truc})
	$id = str_replace('__', '/', $id);

	include_spip('inc/config');
	$config =  lire_config($id, '');

	return ['valeur' => $config];
}

/**
 * Extrait la valeur d'une chaine de langue
 *
 * @param string $crayon_type
 *   type d'objet etendu
 * @param array $motifs
 *   Motifs a traduire
 * @param string $module
 *   Module de langue sous la forme module_lang, ex local_fr
 * @return array
 *   Couple motif_chaine_de_langue => valeur traduite
**/
function traduction_valeur_colonne_table_dist($crayon_type, $motifs, $module) {
	$lang = substr($module, -2);
	$mod = substr($module, 0, -3);
	$valeur = _T("$mod:$motifs[0]", ['spip_lang' => $lang], ['force' => false]);
	// valeur vide si traduction trouvée dans la langue du site
	if ($lang != $GLOBALS['meta']['langue_site'] && $valeur == _T("$mod:$motifs[0]", ['spip_lang' => $GLOBALS['meta']['langue_site']])) {
		$valeur = '';
	}
	return [$motifs[0] => $valeur];
}


function return_log($var) {
	die(crayons_json_encode(['$erreur' => var_export($var, true)]));
}

function _U($texte, $params = []) {
	include_spip('inc/charsets');
	return unicode2charset(html2unicode(_T($texte, $params)));
}

/**
 * Obtenir la configuration des crayons
 *
 * @note wdgcfg = widget config :-)
 *
 * @return array
 *     Couples : attribut => valeur
**/
function wdgcfg() {
	$php = function_exists('crayons_config') ? crayons_config() : [];
	include_spip('inc/meta');
	lire_metas();
	global $meta;
	$metacrayons = empty($meta['crayons']) ? [] : unserialize($meta['crayons']);
	$wdgcfg = [];
	foreach (
		[
		'msgNoChange' => false,
		'msgAbandon' => false,  /* etait: true */
		'yellow_fade' => false,
		'clickhide' => false /* etait: true */
		] as $cfgi => $def
	) {
		$wdgcfg[$cfgi] = $php[$cfgi] ?? $metacrayons[$cfgi] ?? $def;
	}
	return $wdgcfg;
}

function &crayons_get_table($crayon_type, &$nom_table) {
	[$distant, $table, $type] = distant_table($crayon_type);
	static $return = [];
	static $noms = [];
	if (!isset($return[$table])) {
		$return[$table] = null;

		$trouver_table = charger_fonction('trouver_table', 'base');
		$try = [table_objet_sql($table), 'spip_' . table_objet($table), 'spip_' . $type . 's', $type . 's', 'spip_' . $table, $table];
		foreach ($try as $nom) {
			if ($q = $trouver_table($nom, $distant, !$distant)) {
				$noms[$table] = $q['table_sql'];
				$return[$table] = $q;
				break;
			}
		}
	}

	$nom_table = $noms[$table] ?? '';
	return $return[$table];
}

function distant_table($crayon_type) {
	//separation $type en $distant $table
	//separateur double underscore "__"
	if (strstr($crayon_type, '__')) {
		[$distant, $type] = explode('__', $crayon_type, 2);
		// distant ne peut pas être n'importe quoi
		$distant = preg_replace('/\W+/i', '_', $distant);
	} else {
		$distant = '';
		$type = $crayon_type;
	}

	// normaliser type et table via les fonctions internes SPIP
	$type = objet_type($type, $distant);
	$table = table_objet($type, $distant);
	return [$distant, $table, $type];
}
