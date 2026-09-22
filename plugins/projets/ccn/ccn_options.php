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

if (isset($_GET['annee_scolaire'])) {
	$_annee_get = intval($_GET['annee_scolaire']);
	if ($_annee_get > 2011 && $_annee_get < 2100) {
		$annee_scolaire = $_annee_get;
		setcookie('laclasse_annee_scolaire', $annee_scolaire, ['expires' => time() + 3600 * 12, 'path' => '/']);
	}
	unset($_annee_get);
}

// Ne jamais retenir une année dont la rubrique n'existe pas encore sur CETTE
// instance : le calcul calendaire ci-dessus suppose que la rentrée a déjà
// créé la rubrique de l'année en cours (cf genie/thematique_rentree_annee.php,
// déclenché le 1er septembre mais pas instantané - fenêtre de battement avant
// le prochain passage du cron), et une instance CCN peut aussi ne jamais ouvrir
// une année donnée. Sans ce repli, tout le site (menu, filtrage des boucles
// RUBRIQUES/ARTICLES par #CONST{_ANNEE_SCOLAIRE}) pointe sur une année vide.
// Repli sur la dernière rubrique d'année réellement existante (titre
// numérique pur, ex. "2025"). Deux conventions coexistent selon le plugin :
// rubriques années à la racine (thematique, cf thematique_assurer_structure_annee())
// ou enfants d'une rubrique repérée par le mot-clé "rubrique-contenant-annees"
// (fictionsv2/petitfablabv2, cf #453 sur fictionsv2/squelettes/footer.html) -
// on cherche dans les deux structures.
include_spip('base/abstract_sql');
$_id_rubrique_contenant_annees = sql_getfetsel(
	'r.id_rubrique',
	['spip_rubriques AS r', 'spip_mots_liens AS ml', 'spip_mots AS m'],
	[
		'ml.id_objet=r.id_rubrique',
		'ml.objet=' . sql_quote('rubrique'),
		'ml.id_mot=m.id_mot',
		'm.titre=' . sql_quote('rubrique-contenant-annees'),
	],
	'',
	'r.id_rubrique',
	'0,1'
);
$_id_parents_annees = [0];
if ($_id_rubrique_contenant_annees) {
	$_id_parents_annees[] = intval($_id_rubrique_contenant_annees);
}
$_annee_existante = sql_getfetsel(
	'titre',
	'spip_rubriques',
	sql_in('id_parent', $_id_parents_annees) . ' AND titre REGEXP ' . sql_quote('^[0-9]{4}$') . ' AND titre<=' . sql_quote((string) $annee_scolaire),
	'',
	'titre DESC',
	'0,1'
);
if ($_annee_existante !== null && $_annee_existante !== false && $_annee_existante !== '') {
	$annee_scolaire = intval($_annee_existante);
}
unset($_annee_existante, $_id_rubrique_contenant_annees, $_id_parents_annees);

$annee_scolaire = intval($annee_scolaire);
define('_ANNEE_SCOLAIRE', $annee_scolaire);
define('_COOKIE_ANNEE_SCOLAIRE', 'laclasse_annee_scolaire');
define('_DATE_DEBUT', $annee_scolaire . '-09-01');
define('_DATE_FIN', ($annee_scolaire + 1) . '-09-01');

// Limite de taille des documents (hors mp4, poussés vers Vimeo), en Mo.
// Utilisée par ccn_verifier_uploads() (inc/uploads.php) et affichable en
// squelette via #CONST{_CCN_UPLOAD_TAILLE_MAX_MO}.
define('_CCN_UPLOAD_TAILLE_MAX_MO', 100);
