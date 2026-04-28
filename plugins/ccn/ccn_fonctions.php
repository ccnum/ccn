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

function annee_rub1($idr) {
	$annee_scolaire = 0;
	$date = sql_getfetsel('id_rubrique', 'spip_rubriques', 'id_rubrique=' . intval($idr));
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
	// _annee_scolaire est calculé et validé dans ccn_options.php
	$p->code = '_annee_scolaire';
	return $p;
}

function balise_ANNEE_ACTUELLE_dist($p) {
	if (intval(date('m')) >= 8) {
		$p->code = intval(date('Y'));
	} else {
		$p->code = intval(date('Y')) - 1;
	}
	return $p;
}

function afficher_options_date($annee, $mois, $annee_scolaire) {
	$texte = '';
	if (intval(date('m')) >= 8) {
		$annee_actuelle = intval(date('Y'));
	} else {
		$annee_actuelle = intval(date('Y')) - 1;
	}
	if ($mois < 8) {
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
