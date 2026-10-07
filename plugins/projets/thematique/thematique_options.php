<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

define('_cookie_affichage', 'laclasse_affichage');
define('_cookie_rubrique', 'laclasse_rubrique_admin');

$GLOBALS['ext_audio'] = 'mp3|ogg|wav';
$GLOBALS['ext_video'] = 'avi|mpg|flv|mp4|mov';
$GLOBALS['ext_photo'] = 'jpg|png|gif';

$flag_preserver = true;

$pagination_item_avant = '';
$pagination_item_apres = '';
$pagination_separateur = '&nbsp;|&nbsp;';

define('_FORUM_LONGUEUR_MAXI', 10000);

// RNE des établissements dont les comptes ENS reçoivent le statut webmestre
// (0minirezo + webmestre, sans rubrique restreinte), séparés par des virgules
//define('_THEMATIQUE_RNE_WEBMESTRES', '0000001A');
define('_THEMATIQUE_RNE_WEBMESTRES', '00000CCN');

// Groupe libre ENT des intervenants : rattachés à la même structure que l'équipe
// projet (00000CCN) avec le profil ENS, ils seraient sinon promus webmestres.
if (!defined('_THEMATIQUE_GROUPE_INTERVENANTS')) {
	define('_THEMATIQUE_GROUPE_INTERVENANTS', 'CCN Intervenants');
}
// uid ENT (ex: VBZ63652) qui restent webmestres même membres de ce groupe (équipe
// projet inscrite aussi comme intervenant), séparés par des virgules.
if (!defined('_THEMATIQUE_UID_WEBMESTRES')) {
	define('_THEMATIQUE_UID_WEBMESTRES', '');
}

// Choix de la classe active d'un prof rattaché à plusieurs classes (menu haut,
// action thematique_choisir_classe). Désactivé : l'ENT renvoie aussi les
// anciennes classes d'un prof (ENTClassesGroupes n'a pas d'année), le menu en
// listait donc qui ne participent pas au projet. Sans choix, la première classe
// (par id) fait foi.
if (!defined('_THEMATIQUE_CHOIX_CLASSE')) {
	define('_THEMATIQUE_CHOIX_CLASSE', false);
}
