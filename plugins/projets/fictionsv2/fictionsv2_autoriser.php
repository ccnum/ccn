<?php
/**
 * Autorisations de fictionsv2, chargées via le pipeline "autoriser" (cf paquet.xml).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function fictionsv2_autoriser() {
}

/**
 * Association de mots-clés réservée aux admins complets (issue #274).
 *
 * Tous les mots de fictionsv2 sont techniques (rubrique-contenant-annees,
 * blog_pedagogique, presentation, prologue, chapitre1, footer-*...) et pilotent
 * la structure du site pour tout le monde : un article tagué blog_pedagogique
 * apparaît dans le footer de toutes les pages, une rubrique taguée
 * rubrique-contenant-annees fausse le sélecteur d'années. La règle native
 * (autoriser_associermots_dist) laisse un admin restreint (prof, lié à ses
 * rubriques de classe) poser n'importe lequel de ces mots depuis /ecrire sur
 * ce qu'il peut modifier.
 */
function autoriser_associermots($faire, $type, $id, $qui, $opt) {
	if (($qui['statut'] ?? '') !== '0minirezo' || !empty($qui['restreint'])) {
		return false;
	}
	return autoriser_associermots_dist($faire, $type, $id, $qui, $opt);
}
