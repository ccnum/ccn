<?php
/**
 * Autorisations du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Autorisation : voir les réactions et leurs compteurs
 *
 * Ouvert à tout le monde par défaut. Surcharger cette fonction dans
 * mes_options.php si besoin de restreindre (ex: contenu privé).
 *
 * @param string $faire
 * @param string $type
 * @param int $id
 * @param array|null $qui
 * @param array $opt
 * @return bool
 */
function autoriser_reactionvoir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autorisation : poser une réaction
 *
 * Par défaut : ouvert aux connectés, et aux anonymes si la config
 * du plugin l'autorise (reactions_anonymes_autorises()).
 *
 * @param string $faire
 * @param string $type
 * @param int $id
 * @param array|null $qui
 * @param array $opt
 * @return bool
 */
function autoriser_reactionposer_dist($faire, $type, $id, $qui, $opt) {
	if (!empty($qui['id_auteur'])) {
		return true;
	}
	include_spip('reactions_fonctions');
	return reactions_anonymes_autorises();
}

/**
 * Autorisation : configurer le plugin (page de config)
 *
 * Réservé aux administrateurs complets, comme toute config de plugin.
 *
 * @param string $faire
 * @param string $type
 * @param int $id
 * @param array|null $qui
 * @param array $opt
 * @return bool
 */
function autoriser_reactionconfigurer_dist($faire, $type, $id, $qui, $opt) {
	return !empty($qui['statut']) && $qui['statut'] === '0minirezo' && empty($qui['restreint']);
}