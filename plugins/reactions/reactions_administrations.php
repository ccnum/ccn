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
    include_spip('inc/config');

    $maj = [];
    $maj['create'] = [
        ['maj_tables', ['spip_reactions']],
    ];

    maj_plugin($nom_meta_base_version, $version_cible, $maj);
    if (lire_config($nom_meta_base_version) && !lire_config('reactions/types_actifs')) {
        include_spip('formulaires/configurer_reactions');
        if (function_exists('reactions_catalogue_smileys')) {
            $defaut_actifs = array_keys(reactions_catalogue_smileys());
        } else {
            $defaut_actifs = ['coeur', 'pouce', 'feu'];
        }
        ecrire_config('reactions/anonymes_autorises', 'oui');
        ecrire_config('reactions/multi_reactions', 'oui');
        ecrire_config('reactions/types_actifs', $defaut_actifs);
        include_spip('inc/meta');
        lire_metas();
    }
}

/**
 * Fonction de désinstallation (Obligatoire si on veut être propre)
 */
function reactions_vider_tables($nom_meta_base_version) {
    include_spip('base/abstract_sql');
    include_spip('inc/config');
    sql_drop_table('spip_reactions');
    effacer_config('reactions');
    effacer_meta($nom_meta_base_version);
}


