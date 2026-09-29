<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un simple formulaire de config,
 * on a juste à déclarer les saisies
 **/
function formulaires_configurer_tarteaucitron_panneau_saisies_dist() {
	// $saisies est un tableau décrivant les saisies à afficher dans le formulaire de configuration
	$saisies = [
		[
			'saisie' => 'textarea',
			'options' => [
				'nom' => 'lang_disclaimer',
				'label' => '<:tarteaucitron:cfg_text_disclaimer:>',
				'explication' => '<:tarteaucitron:cfg_text_disclaimer_explication:>',
				'rows' => 3,
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'mandatory',
				'label' => '<:tarteaucitron:cfg_mandatory:>',
				'explication' => '<:tarteaucitron:cfg_mandatory_explication:>',
				'datas' => [
					'true' => '<:item_oui:>',
				],
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'moreInfoLink',
				'label' => '<:tarteaucitron:cfg_moreinfolink:>',
				'datas' => [
					'true' => '<:item_oui:>',
				],
			],
		],
		[
			'saisie' => 'input',
			'options' => [
				'nom' => 'readmoreLink',
				'label' => '<:tarteaucitron:cfg_readmorelink:>',
				'explication' => '<:tarteaucitron:cfg_readmorelink_explication:>',
				'afficher_si' => '@moreInfoLink@ == "true"',
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'groupServices',
				'label' => '<:tarteaucitron:cfg_group_services:>',
				'explication' => '<:tarteaucitron:cfg_group_services_explication:>',
				'datas' => [
					'true' => '<:item_oui:>',
				],
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'removeCredit',
				'label' => '<:tarteaucitron:cfg_remove_credit:>',
				'explication' => '<:tarteaucitron:cfg_remove_credit_explication:>',
				'attention' => '<:tarteaucitron:cfg_remove_credit_attention:>',
				'datas' => [
					'true' => '<:item_oui:>',
				],
			],
		],
		[
			'saisie' => 'hidden',
			'options' => [
				'nom' => '_meta_casier',
				'defaut' => 'tarteaucitron',
			],
		],
	];
	return $saisies;
}
