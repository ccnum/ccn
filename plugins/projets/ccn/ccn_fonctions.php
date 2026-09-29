<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function annee_rub($idr) {
	$annee_scolaire = 0;
	$date = sql_getfetsel('maj', 'spip_rubriques', 'id_rubrique=' . intval($idr));
	if ($date != '') {
		$annee_scolaire = intval(substr($date, 0, 4));
		$mois_scolaire = intval(substr($date, 5, 2));
		if ($mois_scolaire < 9) {
			$annee_scolaire--;
		}
	}
	return $annee_scolaire;
}

function balise_ANNEE_SCOLAIRE_dist($p) {
	// _ANNEE_SCOLAIRE est calculé et validé dans ccn_options.php
	$p->code = '_ANNEE_SCOLAIRE';
	return $p;
}

/**
 * Valeur de l'attribut HTML accept="" à poser sur un input file, pour que
 * le sélecteur de fichiers du navigateur ne propose pas d'emblée des
 * formats de toute façon rejetés côté serveur par ccn_verifier_uploads()
 * (cf inc/uploads.php, chargé ici à la volée car pas autoload).
 */
function balise_ATTRIBUT_ACCEPT_DOCUMENTS_dist($p) {
	$p->code = 'ccn_attribut_accept_documents_balise()';
	return $p;
}

function ccn_attribut_accept_documents_balise() {
	include_spip('inc/uploads');
	return ccn_attribut_accept_documents();
}

function balise_ANNEE_ACTUELLE_dist($p) {
	if (intval(date('m')) >= 9) {
		$p->code = intval(date('Y'));
	} else {
		$p->code = intval(date('Y')) - 1;
	}
	return $p;
}

function afficher_options_date($annee, $mois, $annee_scolaire, $annee_max = null) {
	$texte = '';
	// _ANNEE_SCOLAIRE (ccn_options.php) plutôt qu'un calcul calendaire brut :
	// la rubrique racine de l'année scolaire réelle peut ne pas encore exister
	// (rentrée pas encore jouée) - _ANNEE_SCOLAIRE se replie déjà sur la
	// dernière année existante, ce qui évite de lister ici une option d'année
	// sans contenu. Si $annee_max est passé, il est utilisé à la place (utile
	// pour le sélecteur de footer où on veut toujours afficher toutes les
	// années disponibles, pas seulement celles jusqu'à l'année sélectionnée).
	$annee_actuelle = $annee_max ?? _ANNEE_SCOLAIRE;
	if ($mois < 9) {
		$annee--;
	}
	for ($i = $annee_actuelle; $i >= $annee; $i--) {
		$j = $i + 1;
		$texte .= "<option value='$i'";
		if ($i == $annee_scolaire) {
			$texte .= ' selected';
		}
		$texte .= ">$i/$j</option>";
	}
	return $texte;
}
