<?php

/*
 * Plugin Thématiques
 * Licence GPL
 * Auteur Patrick Vincent
 */

/****************************/
/*    VARIABLES GLOBALES    */
/****************************/
define('_cookie_affichage', 'laclasse_affichage');
define('_cookie_rubrique', 'laclasse_rubrique_admin');

// Qualifie les médias pour les tris
$GLOBALS['ext_audio'] = 'mp3|ogg|wav';
$GLOBALS['ext_video'] = 'avi|mpg|flv|mp4|mov';
$GLOBALS['ext_photo'] = 'jpg|png|gif';

//include_spip('base/abstract_sql');
//if ($id = sql_getfetsel("id_form", "spip_forms", "titre='form_webnapperon'")) define('_id_form_webnapperon',$id);
//if ($id = sql_getfetsel("id_form", "spip_forms", "titre='form_carte'")) define('_id_form_carte',$id);
//if ($id = sql_getfetsel("id_form", "spip_forms", "titre='form_client_mail'")) define('_id_form_client_mail',$id);
//if ($id = sql_getfetsel("id_groupe", "spip_groupes_mots", "titre='carte'")) define('_id_groupe_mot_badge',$id);

/************************************************************************************/
/*    REQUETES DANS LA FENETRE ACTIVE ANNEE_SCOLAIRE  : SURCHARGE DU PIPELINE PRE_BOUCLE */
/************************************************************************************/
// Calcul de l'année scolaire en lien avec le dernier article en cours
if (
	(isset($_COOKIE['laclasse_annee_scolaire']))
	&& ($_COOKIE['laclasse_annee_scolaire'] != 0)
	&& ($_COOKIE['laclasse_annee_scolaire'] != '')
	&& ($_COOKIE['laclasse_annee_scolaire'] > 2011)
) {
	$annee_scolaire = $_COOKIE['laclasse_annee_scolaire'];
} else {
	if (date('m') >= '08') {
		$annee_scolaire = date('Y');
	} else {
		$annee_scolaire = date('Y') - 1;
	}
}

if (
	(isset($_GET['annee_scolaire']))
	&& ($_GET['annee_scolaire'] != 0)
	&& ($_GET['annee_scolaire'] != '')
) {
	$annee_scolaire = $_GET['annee_scolaire'];
	setcookie('laclasse_annee_scolaire', $annee_scolaire);
}

define('_annee_scolaire', $annee_scolaire);
define('_date_debut', $annee_scolaire . '-08-01');
define('_date_fin', ($annee_scolaire + 1) . '-08-01');

/************************************************************************************/
/*    OPTIONS SPIP                                                                */
/************************************************************************************/

//Désactiver les boutons administrateur
//$flag_preserver = true;

// Personnalisation des items de pagination (a changer si besoin)
$pagination_item_avant = '';
$pagination_item_apres = '';
$pagination_separateur = '&nbsp;|&nbsp;';

// Forcer la langue selon le choix du visiteur
$GLOBALS['forcer_lang'] = true;

// Ne pas transformer toutes les urls en lien !
//define('_EXTRAIRE_LIENS',',^$,');

// Limiter la longueur des messages de forum
define('_FORUM_LONGUEUR_MAXI', 10000);
