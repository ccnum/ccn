<?php

/**
 * Crayons
 * plugin for spip
 * (c) Fil, toggg 2006-2019
 * licence GPL
 *
 * @package SPIP\Crayons\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function crayons_autoriser() {
}


if (!function_exists('autoriser_meta_modifier_dist')) {
/**
 * Autorisation d'éditer les configurations dans spip_meta
 *
 * Les admins complets OK pour certains champs,
 * Sinon, il faut être webmestre
 *
 * ici $id est une chaine
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  string $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_meta_modifier_dist($faire, $type, $id, $qui, $opt) {
	// Certaines cles de configuration sont echapées ici (cf #EDIT_CONFIG{demo/truc})
	// $id = str_replace('__', '/', $id);
	if (in_array($id, ['nom_site', 'slogan_site', 'descriptif_site', 'email_webmaster'])) {
		return autoriser('configurer', null, null, $qui);
	} else {
		return autoriser('webmestre', null, null, $qui);
	}
}
}

// table spip_messages, la c'est tout simplement non (peut mieux faire,
// mais c'est a voir dans le core/organiseur ou dans autorite)
if (defined('_DIR_PLUGIN_ORGANISEUR')) {
	include_spip('organiseur_autoriser');
}

if (!function_exists('autoriser_message_modifier_dist')) {
	function autoriser_message_modifier_dist($faire, $type, $id, $qui, $opt) {
		return false;
	}
}

function autoriser_crayonnertabledistante_dist($faire, $type, $id, $qui, $opt) {
	// si crayon_type est fourni en opt
	if (isset($opt['crayon_type'])) {
		[$distant, $table, $type] = distant_table($opt['crayon_type']);
	}
	if (!empty($qui['statut']) && $qui['statut'] === '0minirezo') {
		return true;
	}
	return false;
}

function autoriser_crayonnertableexterne_dist($faire, $type, $id, $qui, $opt) {
	// si crayon_type est fourni en opt
	if (isset($opt['crayon_type'])) {
		[$distant, $table, $type] = distant_table($opt['crayon_type']);
	}
	if (!empty($qui['statut']) && $qui['statut'] === '0minirezo') {
		return true;
	}
	return false;
}

function autoriser_crayonnerchampnoneditable_dist($faire, $type, $id, $qui, $opt) {
	// si crayon_type est fourni en opt
	if (isset($opt['crayon_type'])) {
		[$distant, $table, $type] = distant_table($opt['crayon_type']);
	}
	// cas particulier du modele=joindredocument passé par action crayons_upload
	if (!empty($opt['modele']) && $opt['modele'] === 'joindredocument') {
		return autoriser('joindredocument', $type, $id, $qui, $opt);
	}
	if (!empty($qui['statut']) && $qui['statut'] === '0minirezo') {
		return true;
	}
	return false;
}

// Autoriser l'usage des crayons ?
function autoriser_crayonner_dist($faire, $type, $id, $qui, $opt) {
	// si crayon_type est fourni en opt
	if (isset($opt['crayon_type'])) {
		[$distant, $table, $type] = distant_table($opt['crayon_type']);
	} else {
		$distant = '';
	}

	// Traduire le modele en liste de champs si pas fournie
	if (isset($opt['modele']) && !isset($opt['champ'])) {
		$opt['champ'] = crayons_lister_champs_modele($type, $id, $opt['modele']) ?? $opt['modele'];
	}
	$champs = $opt['champ'] ?? [];
	$champs = is_array($champs) ? $champs : [$champs];

	include_spip('inc/crayons');
	$table_sql = '';
	$infos_table = crayons_get_table($opt['crayon_type'] ?? $type, $table_sql);
	if (!$infos_table) {
		return false;
	}
	if ($distant) {
		// on vérifie une autorisation spéciale sur les distant
		if (!autoriser('crayonnertabledistante', $type, $id, $qui, $opt)) {
			return false;
		}
	} elseif (!crayons_is_table_interne($table_sql)) {
		// si on a pas _CRAYONS_TABLES_EXTERNES = true c'est niet
		if (!defined('_CRAYONS_TABLES_EXTERNES') || !_CRAYONS_TABLES_EXTERNES) {
			return false;
		}
		// sinon on vérifie une autorisation spéciale
		if (!autoriser('crayonnertableexterne', $type, $id, $qui, $opt)) {
			return false;
		}
	} elseif(!crayons_is_champ_editable($infos_table, $champs)) {
		if (!autoriser('crayonnerchampnoneditable', $type, $id, $qui, $opt)) {
			return false;
		}
	}

	// Pour un auteur, les champs sensibles doivent être passé en clé des options
	/** @see autoriser_auteur_modifier_dist() */
	if ($type === 'auteur'
		&& !empty($champs)) {
		foreach ($champs as $champ) {
			if (in_array($champ, ['statut', 'email', 'login', 'pass', 'webmestre', 'restreintes'])) {
				$opt[$champ] = true;
			}
		}
	}

	return autoriser('modifier', $type, $id, $qui, $opt);
}


function crayons_lister_champs_modele(string $type, $id, ?string $modele): ?array {
	if (!empty($modele)) {
		include_spip('action/crayons_html');
		$controleur = crayons_trouver_controleur($type, $id, $modele);
		return crayons_controleur_lister_champs($controleur);
	}
	return null;
}

function crayons_is_champ_editable(array $infos_table, ?array $champs) {
	if (!empty($champs)
		&& !empty($infos_table['champs_editables'])
		&& is_array($infos_table['champs_editables'])) {
		foreach ($champs as $champ) {
			if (!in_array($champ, $infos_table['champs_editables'], true)) {
				return false;
			}
		}
		return true;
	}
	return false;
}

function crayons_is_table_interne($table_sql, $tables_principales_uniquement = false) {
	include_spip('base/objets');
	$tables_objets = lister_tables_objets_sql();
	include_spip('public/parametrer');
	if (
		!isset($tables_objets[$table_sql])
		&& !isset($GLOBALS['tables_principales'][$table_sql])
		&& ($tables_principales_uniquement || !isset($GLOBALS['tables_auxiliaires'][$table_sql]))
	) {
		return false;
	}
	return true;
}
