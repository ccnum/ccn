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
