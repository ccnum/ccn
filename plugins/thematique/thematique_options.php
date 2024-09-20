<?php

/*
 * Plugin Thématiques
 * Licence GPL
 * Auteur Patrick Vincent
 */

/************************************************************************************/

/*    VARIABLES GLOBALES                                    */
/************************************************************************************/
define('_cookie_affichage', 'laclasse_affichage');
define('_cookie_rubrique', 'laclasse_rubrique_admin');
define('_cookie_annee_scolaire', 'laclasse_annee_scolaire');

//Ces variables ne sont plus utilisées à vérifier
//Tailles
define('_hauteur', 800);
define('_largeur', 1500);
//Type d'affichage des popups : detail (iframe), ajax (modalbox sans iframe)
define('_MODE_POPUP', 'complet');
//Fin vérification

$annee_scolaire = 2024;
define('_annee_cours', $annee_scolaire);

//Qualifie les médias pour les tris
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
//Calcul de l'année scolaire en lien avec le dernier article en cours
if (
	(isset($_COOKIE['_cookie_annee_scolaire']))
	&& ($_COOKIE['_cookie_annee_scolaire'] != 0)
	&& ($_COOKIE['_cookie_annee_scolaire'] != '')
	&& ($_COOKIE['_cookie_annee_scolaire'] > 2011)
) {
	$annee_scolaire = $_COOKIE['_cookie_annee_scolaire'];
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
	setcookie('_cookie_annee_scolaire', $annee_scolaire);
}

define('_annee_scolaire', $annee_scolaire);

/************************************************************************************/
/*    OPTIONS SPIP                                                                */
/************************************************************************************/

//Retire jQuery d'insert head pour insertion personnalisée et bug ajax callback
//$GLOBALS['spip_pipeline']['insert_head'] = str_replace('|f_jQuery', '', $GLOBALS['spip_pipeline']['insert_head']);

//Désactiver les boutons administrateur
$flag_preserver = true;

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
