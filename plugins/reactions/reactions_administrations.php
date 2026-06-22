<?php
/**
 * Fonctions d'installation et de désinstallation du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Administrations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

/**
 * Installation / mise à jour de la base et des métas
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function reactions_upgrade($nom_meta_base_version, $version_cible) {
	include_spip('base/upgrade');

	$maj = [];

	$maj['create'] = [
		['maj_tables', ['spip_reactions']],
		['reactions_initialiser_config'],
	];

	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Initialise la configuration par défaut du plugin (smileys actifs,
 * options anonymes/multi-réactions), uniquement si elle n'existe pas
 * déjà (pour ne jamais écraser une config existante lors d'une
 * réinstallation ou d'une mise à jour).
 *
 * Appelée depuis le tableau $maj de reactions_upgrade(), suivant le
 * pattern recommandé par SPIP pour les actions propres au plugin
 * (cf. revisions_update_meta dans la documentation officielle).
 */
function reactions_initialiser_config() {
	include_spip('inc/config');

	if (!isset($GLOBALS['meta']['reactions/types_actifs'])) {
		ecrire_config('reactions/types_actifs', serialize([
			'coeur' => '❤️',
			'pouce' => '👍',
			'feu'   => '🔥',
			'rire'  => '😂',
			'triste' => '😢',
		]));
	}

	if (!isset($GLOBALS['meta']['reactions/anonymes_autorises'])) {
		ecrire_config('reactions/anonymes_autorises', 'oui');
	}

	if (!isset($GLOBALS['meta']['reactions/multi_reactions'])) {
		ecrire_config('reactions/multi_reactions', 'oui');
	}
}

/**
 * Désinstallation : supprime la table et les métas associées
 *
 * @param string $nom_meta_base_version
 */
function reactions_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_reactions');
	effacer_meta('reactions/types_actifs');
	effacer_meta('reactions/anonymes_autorises');
	effacer_meta('reactions/multi_reactions');
	effacer_meta($nom_meta_base_version);
}