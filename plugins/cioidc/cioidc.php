<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

// Si le serveur d'identité n'accepte pas le point d'interrogation
// dans request_uri et qu'une réécriture d'url n’est pas possible
// (pour transformer "cioidc.php" en "spip.php?action=login_cioidc"
// par exemple : RewriteRule ^cioidc.php$ spip.php?action=login_cioidc [QSA,L] ),
// alors il convient de copier ce fichier à la racine du site.
//
// ATTENTION : si le plugin cioidc est dans plugins-dist
// remplacer ci-dessous "/plugins/" par "/plugins-dist/"

require_once __DIR__ . '/plugins/cioidc/inc/cioidc_auth.php';
