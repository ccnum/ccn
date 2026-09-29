<?php
/**
 * Formulaire de configuration du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

include_spip('reactions_fonctions');
include_spip('inc/config');

/**
 * Catalogue complet des smileys proposables
 */
function reactions_catalogue_smileys() {
    return [
        'coeur' => [
            'html_encoding' => '&#10084;&#65039;',
            'php_encoding'  => "\u{2764}\u{FE0F}",
            'no_encoding'   => '❤️'
        ],
        'amour' => [
            'html_encoding' => '&#128525;',
            'php_encoding'  => "\u{1F60D}",
            'no_encoding'   => '😍'
        ],
        'pouce' => [
            'html_encoding' => '&#128077;',
            'php_encoding'  => "\u{1F44D}",
            'no_encoding'   => '👍'
        ],
        'pouce_bas' => [
            'html_encoding' => '&#128078;',
            'php_encoding'  => "\u{1F44E}",
            'no_encoding'   => '👎'
        ],
        'feu' => [
            'html_encoding' => '&#128293;',
            'php_encoding'  => "\u{1F525}",
            'no_encoding'   => '🔥'
        ],
        'rire' => [
            'html_encoding' => '&#128514;',
            'php_encoding'  => "\u{1F602}",
            'no_encoding'   => '😂'
        ],
        'triste' => [
            'html_encoding' => '&#128546;',
            'php_encoding'  => "\u{1F622}",
            'no_encoding'   => '😢'
        ],
        'etonne' => [
            'html_encoding' => '&#128558;',
            'php_encoding'  => "\u{1F62E}",
            'no_encoding'   => '😮'
        ],
        'colere' => [
            'html_encoding' => '&#128544;',
            'php_encoding'  => "\u{1F620}",
            'no_encoding'   => '😠'
        ],
        'clap' => [
            'html_encoding' => '&#128079;',
            'php_encoding'  => "\u{1F44F}",
            'no_encoding'   => '👏'
        ],
        'fete' => [
            'html_encoding' => '&#127881;',
            'php_encoding'  => "\u{1F389}",
            'no_encoding'   => '🎉'
        ]
    ];
}

function formulaires_configurer_reactions_charger_dist() {
    include_spip('inc/config');
    
    $config_existe = lire_config('reactions/types_actifs', null);
    if (is_null($config_existe)) {
        $types_actifs = ['amour', 'pouce', 'feu'];
        ecrire_config('reactions/types_actifs', $types_actifs);
        ecrire_config('reactions/anonymes_autorises', 'oui');
        ecrire_config('reactions/multi_reactions', 'oui');
    } else {
        $types_actifs = $config_existe;
    }
    return [
        'catalogue_smileys'     => reactions_catalogue_smileys(),
        'types_actifs_courants' => $types_actifs,
        'anonymes_autorises'    => lire_config('reactions/anonymes_autorises', 'oui'),
        'multi_reactions'       => lire_config('reactions/multi_reactions', 'oui'),
    ];
}

function formulaires_configurer_reactions_verifier_dist() {
    $erreurs = [];
    $recu = _request('types_actifs_courants');
    if (!is_array($recu) || empty($recu)) {
        $erreurs['types_actifs_courants'] = 'Choisissez au moins un smiley.';
    }

    return $erreurs;
}

function formulaires_configurer_reactions_traiter_dist() {
    include_spip('inc/config');
    $types_actifs = _request('types_actifs_courants') ?: [];
    $anonymes_autorises = _request('anonymes_autorises') ?: 'non';
    $multi_reactions    = _request('multi_reactions') ?: 'non';
    ecrire_config('reactions/types_actifs', $types_actifs);
    ecrire_config('reactions/anonymes_autorises', $anonymes_autorises);
    ecrire_config('reactions/multi_reactions', $multi_reactions);

    include_spip('inc/meta');
    lire_metas();

    return [
        'message_ok' => 'La configuration a bien été mise à jour !',
    ];
}

/**
 * Filtre de squelette : indique si une case doit être cochée
 */
function reaction_est_actif($cle, $types_actifs_courants) {
    $types_actifs_courants = is_array($types_actifs_courants) ? $types_actifs_courants : [];
    return in_array($cle, $types_actifs_courants, true) ? 'checked="checked"' : '';
}