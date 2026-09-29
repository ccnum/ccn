<?php
/**
 * Ce fichier contient les fonctions d'autorisations du plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction appelée par le pipeline.
 */
function simplog_autoriser() {
}

/**
 * Autorisation d'accès à la visualisation de tous les logs spip.
 * Par défaut, seuls les administrateurs complets sont autorisés à utiliser cette page.
 *
 * @param string         $faire   L'action : `voir`
 * @param string         $type    Le type d'objet ou nom de table : `simplog` (ce n'est pas un objet au sens SPIP)
 * @param int            $id      Id de l'objet sur lequel on veut agir : 0, inutilisé
 * @param null|array|int $qui     L'initiateur de l'action:
 *                                - si null on prend alors visiteur_session
 *                                - un id_auteur (on regarde dans la base)
 *                                - un tableau auteur complet, y compris [restreint]
 * @param null|array     $options Tableau d'options sous forme de tableau associatif : `null`, inutilisé
 *
 * @return bool `true`si l'auteur est autorisée à exécuter l'action, `false` sinon.
 */
function autoriser_simplog_voir_dist($faire, $type, $id, $qui, $options) {
	return ($qui['statut'] === '0minirezo');
}

/**
 * Autorisation d'affichage du menu d'accès aux logs (page=simplog).
 * Il faut être autorisé à voir la page des logs.
 *
 * @param string         $faire   L'action : `menu`
 * @param string         $type    Le type d'objet ou nom de table : `simplog` (ce n'est pas un objet au sens SPIP)
 * @param int            $id      Id de l'objet sur lequel on veut agir : 0, inutilisé
 * @param null|array|int $qui     L'initiateur de l'action:
 *                                - si null on prend alors visiteur_session
 *                                - un id_auteur (on regarde dans la base)
 *                                - un tableau auteur complet, y compris [restreint]
 * @param null|array     $options Tableau d'options sous forme de tableau associatif : `null`, inutilisé
 *
 * @return bool `true`si l'auteur est autorisée à exécuter l'action, `false` sinon.
 */
function autoriser_simplog_menu_dist($faire, $type, $id, $qui, $options) {
	return autoriser('voir', 'simplog');
}
