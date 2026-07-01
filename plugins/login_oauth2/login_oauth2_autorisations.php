<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Autorise l'accès à la configuration du plugin login_oauth2.
 *
 * Seuls les administrateurs complets (statut 0minirezo)
 * peuvent accéder à la page de configuration du plugin.
 *
 */
function autoriser_configurer_login_oauth2_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] === '0minirezo';
}
