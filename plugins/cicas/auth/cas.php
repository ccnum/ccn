<?php

/**
 * Plugin Authentification CAS
 * @copyright 2010 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Modifier le mot de passe d'un auteur dont la source est 'cas'.
 *
 * Les comptes CAS peuvent avoir un mot de passe SPIP de secours (champ pass non vide).
 * Sans cette fonction, auth_administrer() retourne false et le formulaire de récupération
 * de mot de passe affiche "impossible de modifier le mot de passe" (#267).
 *
 * On délègue à auth_spip_modifier_pass() qui gère le hachage et la mise à jour en base.
 */
function auth_cas_modifier_pass(
	$login,
	$new_pass,
	$id_auteur,
	$serveur = ''
): bool {
	include_spip('auth/spip');
	return (bool) auth_spip_modifier_pass($login, $new_pass, $id_auteur, $serveur);
}
