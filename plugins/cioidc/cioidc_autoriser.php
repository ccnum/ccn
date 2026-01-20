<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction d'appel pour le pipeline
 *
 * @pipeline autoriser
 */
function cioidc_autoriser() {
}

/**
 * Autorisation de voir le menu configurer_cioidc
 *
 * Il faut avoir accès à la page configurer_cioidc
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 **/
function autoriser_configurercioidc_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', '_cioidc', $id, $qui, $opt);
}

/**
 * Autorisation de voir la page configurer_cioidc
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_cioidc_configurer(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (defined('_CIOIDC_CONFIG_UNIQUEMENT_PAR_WEBMESTRE') && _CIOIDC_CONFIG_UNIQUEMENT_PAR_WEBMESTRE == 'non') {
		return autoriser('configurer');
	} else {
		return autoriser('webmestre');
	}
}
