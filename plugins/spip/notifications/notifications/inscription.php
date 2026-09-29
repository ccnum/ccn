<?php

/*
 * Plugin Notifications
 * (c) 2009-2012 SPIP
 * Distribue sous licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * inscription d'un nouvel auteur => mail aux admins
 *
 * @param string $quoi
 * @param int $id_auteur
 * @options array $options
 */
function notifications_inscription_dist($quoi, $id_auteur, $options) {
	if (!isset($GLOBALS['notifications']['inscription'])
	  or !$GLOBALS['notifications']['inscription']) {
		return;
	}

	$modele = 'notifications/inscription';

	$destinataires = [];

	$where = "statut = '0minirezo'";
	// notifier uniquement les webmestres ?
	if ($GLOBALS['notifications']['inscription'] == 'webmestres') {
		$where .= " AND webmestre = 'oui'";
	}

	$query = sql_select('email', 'spip_auteurs', $where);

	while ($row = sql_fetch($query)) {
		$destinataires[] = $row['email'];
	}

	$destinataires = pipeline(
		'notifications_destinataires',
		[
			'args' => ['quoi' => $quoi, 'id' => $id_auteur, 'options' => $options],
			'data' => $destinataires]
	);

	$envoyer_mail = charger_fonction('envoyer_mail', 'inc'); // pour nettoyer_titre_email
	$texte = recuperer_fond($modele, ['id_auteur' => $id_auteur]);

	notifications_envoyer_mails($destinataires, $texte);

}
