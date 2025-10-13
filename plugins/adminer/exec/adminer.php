<?php
/**
 * Plugin Adminer pour Spip
 * Licence GPL 3
 *
 */

use function \SpipLeague\Component\Kernel\param;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function exec_adminer_dist(){

	include_spip('inc/autoriser');
	if (!autoriser('menu','adminer')) {
		include_spip("inc/minipres");
		echo minipres(_T('info_acces_interdit'));
		die();
	}

	$ecrire = (_SPIP_VERSION_ID >= 44000) ? param('spip.routes.back_office') : _DIR_RESTREINT_ABS;
	$url_adminer = url_de_base() . $ecrire . 'prive.php?page=adminer';

	include_spip('inc/headers');
	redirige_par_entete($url_adminer);
}