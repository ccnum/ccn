<?php
/**
 * Fichier de langue française du plugin Reactions
 *
 * @plugin     Reactions
 * @package    SPIP\Reactions\Lang
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [
	// Messages d'erreur du formulaire
	'erreur_type_invalide'      => 'Ce type de réaction n’existe pas ou n’est pas activé.',
	'erreur_non_autorise'       => 'Vous n’êtes pas autorisé à réagir à ce contenu.',
	'erreur_anonymes_interdits' => 'Les visiteurs non connectés ne peuvent pas réagir à ce contenu.',
	'erreur_deja_pose'          => 'Vous avez déjà posé cette réaction.',
	'erreur_type_inconnu'       => 'Type de réaction inconnu.',
	'erreur_generique'          => 'Une erreur est survenue, veuillez réessayer.',

	// Page de configuration
	'config_titre'              => 'Configuration des réactions',
	'config_types_actifs'       => 'Smileys actifs',
	'config_anonymes_autorises' => 'Autoriser les visiteurs non connectés à réagir',
	'config_multi_reactions'    => 'Autoriser plusieurs réactions différentes par visiteur et par contenu',
	'config_enregistrer'        => 'Enregistrer',
	'config_enregistree'        => 'Configuration enregistrée.',
	'erreur_minimum_un_type'    => 'Choisissez au moins un smiley.',

	// Boutons de réaction
	'reagir_avec_un'            => 'Réagir avec @label@, @nb@ réaction',
	'reagir_avec_plusieurs'     => 'Réagir avec @label@, @nb@ réactions',
	'connectez_vous_pour_reagir' => 'Connectez-vous pour réagir',

	// Libellés des réactions (aria-label)
	'label_coeur'               => 'Cœur',
	'label_amour'               => 'Amour',
	'label_pouce'               => 'Pouce en l’air',
	'label_pouce_bas'           => 'Pouce en bas',
	'label_feu'                 => 'Feu',
	'label_rire'                => 'Rire',
	'label_triste'              => 'Triste',
	'label_etonne'              => 'Étonné',
	'label_colere'              => 'Colère',
	'label_clap'                => 'Applaudissements',
	'label_fete'                => 'Fête',
];