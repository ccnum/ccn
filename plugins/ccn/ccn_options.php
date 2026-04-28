<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Calcul de l'année scolaire — cookie partagé entre tous les plugins CCN
$_annee_cookie = isset($_COOKIE['laclasse_annee_scolaire']) ? intval($_COOKIE['laclasse_annee_scolaire']) : 0;
if ($_annee_cookie > 2011 && $_annee_cookie < 2100) {
	$annee_scolaire = $_annee_cookie;
} else {
	if (intval(date('m')) >= 8) {
		$annee_scolaire = intval(date('Y'));
	} else {
		$annee_scolaire = intval(date('Y')) - 1;
	}
}
unset($_annee_cookie);

if (isset($_GET['annee_scolaire'])) {
	$_annee_get = intval($_GET['annee_scolaire']);
	if ($_annee_get > 2011 && $_annee_get < 2100) {
		$annee_scolaire = $_annee_get;
		setcookie('laclasse_annee_scolaire', $annee_scolaire, ['expires' => time() + 3600 * 12, 'path' => '/']);
	}
	unset($_annee_get);
}

$annee_scolaire = intval($annee_scolaire);
define('_annee_scolaire', $annee_scolaire);
define('_date_debut', $annee_scolaire . '-09-01');
define('_date_fin', ($annee_scolaire + 1) . '-09-01');
