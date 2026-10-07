<?php
/**
 * Ce fichier contient l'action `telecharger_log` utilisée pour télécharger un fichier log de SPIP.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action de téléchargement d'un log contenu dans _DIR_LOG.
 * Cette action est possible dans le privé lorsqu'un fichier log est en cours d'affichage.
 *
 * @return void
 */
function action_telecharger_log_dist() : void {
	// Securisation: le nom du fichier est attendu en argument
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$nom_log = $securiser_action();

	include_fichiers_fonctions();
	$fichier = simplog_fichier_log_de($nom_log);
	if (!$fichier || !@is_readable($fichier)) {
		spip_log("Téléchargement impossible du fichier log, $nom_log | $fichier : pas accessible en lecture", 'simplog' . _LOG_ERREUR);
		return;
	}

	include_spip('inc/autoriser');
	if (!autoriser('telecharger', 'simplog', $nom_log)) {
		spip_log("Téléchargement impossible du fichier log, $nom_log | $fichier: accès interdit", 'simplog' . _LOG_ERREUR);
		return;
	}

	include_spip('inc/livrer_fichier');
	spip_livrer_fichier($fichier, 'Content-Type: texte/plain',['attachment' => basename($fichier)]);
}
