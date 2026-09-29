<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Calcul de l'année scolaire — cookie partagé entre tous les plugins CCN
$_annee_cookie = isset($_COOKIE['laclasse_annee_scolaire']) ? intval($_COOKIE['laclasse_annee_scolaire']) : 0;
if ($_annee_cookie > 2011 && $_annee_cookie < 2100) {
	$annee_scolaire = $_annee_cookie;
} else {
	if (intval(date('m')) >= 9) {
		$annee_scolaire = intval(date('Y'));
	} else {
		$annee_scolaire = intval(date('Y')) - 1;
	}
}
unset($_annee_cookie);

// Année courante calculée (sans cookie) — utile pour le sélecteur de footer qui
// doit toujours afficher toutes les années disponibles, pas seulement celles
// jusqu'à l'année sélectionnée.
if (intval(date('m')) >= 9) {
	define('_ANNEE_ACTUELLE_CALCULEE', intval(date('Y')));
} else {
	define('_ANNEE_ACTUELLE_CALCULEE', intval(date('Y')) - 1);
}

if (isset($_GET['annee_scolaire'])) {
	$_annee_get = intval($_GET['annee_scolaire']);
	if ($_annee_get > 2011 && $_annee_get < 2100) {
		$annee_scolaire = $_annee_get;
		setcookie('laclasse_annee_scolaire', $annee_scolaire, ['expires' => time() + 3600 * 12, 'path' => '/']);
	}
	unset($_annee_get);
}

// Ne jamais retenir une année dont la rubrique n'existe pas encore sur CETTE
// instance : le calcul calendaire ci-dessous suppose que la rentrée a déjà
// créé la rubrique de l'année en cours (cf genie/thematique_rentree_annee.php,
// déclenché le 1er septembre mais pas instantané - fenêtre de battement avant
// le prochain passage du cron), et une instance CCN peut aussi ne jamais ouvrir
// une année donnée. Sans ce repli, tout le site (menu, filtrage des boucles
// RUBRIQUES/ARTICLES par #CONST{_ANNEE_SCOLAIRE}) pointe sur une année vide.
// Repli sur la dernière rubrique d'année réellement existante (titre
// numérique pur, ex. "2025"). Les années peuvent être à la racine
// (thematique), enfants d'une rubrique tagée "rubrique-contenant-annees"
// (fictionsv2) ou enfants d'une rubrique parente arbitraire (ex: 177 sur
// certains sites) — on cherche le titre de l'année où qu'elle se trouve.
//
// Ce repli ne s'applique que si l'année vient du calcul calendaire pur
// (pas de cookie, pas de GET) : si l'utilisateur a explicitement choisi
// une année (via le sélecteur du footer), on conserve son choix même si
// la rubrique n'existe pas — le site affichera alors simplement du vide
// plutôt que de masquer le choix en pointant vers une autre année.
$_choix_explicite = isset($_COOKIE['laclasse_annee_scolaire'])
	|| isset($_GET['annee_scolaire']);
if (!$_choix_explicite) {
	include_spip('base/abstract_sql');
	$_annee_existante = sql_getfetsel(
		'titre',
		'spip_rubriques',
		'titre REGEXP ' . sql_quote('^[0-9]{4}$') . ' AND titre<=' . sql_quote((string) $annee_scolaire),
		'',
		'titre DESC',
		'0,1'
	);
	if ($_annee_existante !== null && $_annee_existante !== false && $_annee_existante !== '') {
		$annee_scolaire = intval($_annee_existante);
	}
	unset($_annee_existante);
}
unset($_choix_explicite);

$annee_scolaire = intval($annee_scolaire);
define('_ANNEE_SCOLAIRE', $annee_scolaire);
define('_COOKIE_ANNEE_SCOLAIRE', 'laclasse_annee_scolaire');
define('_DATE_DEBUT', $annee_scolaire . '-09-01');
define('_DATE_FIN', ($annee_scolaire + 1) . '-09-01');

// Limite de taille des documents (hors mp4, poussés vers Vimeo), en Mo.
// Utilisée par ccn_verifier_uploads() (inc/uploads.php) et affichable en
// squelette via #CONST{_CCN_UPLOAD_TAILLE_MAX_MO}.
define('_CCN_UPLOAD_TAILLE_MAX_MO', 100);
