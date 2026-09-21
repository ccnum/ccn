<?php
/**
 * Déclaration de la page exec configurer_reactions
 *
 * Réutilise le mécanisme standard SPIP de page de contenu privé :
 * le fichier prive/squelettes/contenu/configurer_reactions.html
 * fait tout le travail d'affichage.
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function exec_configurer_reactions_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('reactionconfigurer')) {
		include_spip('inc/minipres');
		echo minipres();
		return;
	}

	include_spip('inc/headers');
	$contexte = [];
	echo recuperer_fond('prive/squelettes/contenu/configurer_reactions', $contexte);
}