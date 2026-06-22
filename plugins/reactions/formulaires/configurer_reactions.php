<?php
/**
 * Formulaire de configuration du plugin Reactions
 *
 * Accessible depuis l'espace privé, permet d'activer/désactiver des
 * smileys et de régler les options anonymes/multi-réactions sans
 * toucher au code.
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
 * Catalogue complet des smileys proposables (au-delà de ceux actifs).
 * Liste fermée volontairement simple : on ajoute ici si besoin d'un
 * nouveau smiley au catalogue, sans jamais toucher au schéma SQL.
 *
 * @return array
 */
function reactions_catalogue_smileys() {
	return [
		'coeur'   => '❤️',
		'amour'   => '😍',
		'pouce'   => '👍',
		'pouce_bas' => '👎',
		'feu'     => '🔥',
		'rire'    => '😂',
		'triste'  => '😢',
		'etonne'  => '😮',
		'colere'  => '😠',
		'clap'    => '👏',
		'fete'    => '🎉',
	];
}

function formulaires_configurer_reactions_charger_dist() {
	return [
		'catalogue_smileys'     => reactions_catalogue_smileys(),
		'types_actifs_courants' => array_keys(reactions_types_actifs()),
		'anonymes_autorises'    => reactions_anonymes_autorises() ? 'oui' : 'non',
		'multi_reactions'       => reactions_multi_reactions_autorisees() ? 'oui' : 'non',
	];
}

function formulaires_configurer_reactions_verifier_dist() {
	$erreurs = [];

	$types_choisis = _request('types_actifs') ?: [];
	if (!is_array($types_choisis) || count($types_choisis) === 0) {
		$erreurs['types_actifs'] = _T('reactions:erreur_minimum_un_type') ?: 'Choisissez au moins un smiley.';
	}

	return $erreurs;
}

function formulaires_configurer_reactions_traiter_dist() {
	$catalogue = reactions_catalogue_smileys();
	$types_choisis = _request('types_actifs') ?: [];

	$types_actifs = [];
	foreach ($types_choisis as $cle) {
		if (isset($catalogue[$cle])) {
			$types_actifs[$cle] = $catalogue[$cle];
		}
	}

	ecrire_config('reactions/types_actifs', serialize($types_actifs));
	ecrire_config('reactions/anonymes_autorises', _request('anonymes_autorises') === 'oui' ? 'oui' : 'non');
	ecrire_config('reactions/multi_reactions', _request('multi_reactions') === 'oui' ? 'oui' : 'non');

	include_spip('inc/urls');

	return [
		'message_ok' => _T('reactions:config_enregistree') ?: 'Configuration enregistrée.',
		'redirect'   => generer_url_ecrire('admin_plugin'),
	];
}

/**
 * Filtre de squelette : indique si une clé de smiley fait partie
 * des types actuellement actifs (pour cocher la case correspondante).
 *
 * @param string $cle
 * @param array $types_actifs_courants
 * @return bool
 */
function reaction_est_actif($cle, $types_actifs_courants) {
	$types_actifs_courants = is_array($types_actifs_courants) ? $types_actifs_courants : [];
	return in_array($cle, $types_actifs_courants, true) ? 'checked="checked"' : '';
}