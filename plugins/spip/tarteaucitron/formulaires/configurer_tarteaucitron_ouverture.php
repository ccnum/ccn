<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Nécessaire pour bigup
/**
 * @param array $args
 * @param \Spip\Bigup\Formulaire $formulaire
 * @return \Spip\Bigup\Formulaire
 */
function inc_bigup_medias_formulaire_configurer_tarteaucitron_ouverture_dist($args, $formulaire) {
	$formulaire->preparer_input_class('bigup', [
		'previsualiser' => true,
		'editer_class' => '',
	]);
	return $formulaire;
}

function formulaires_configurer_tarteaucitron_ouverture_charger_dist() {
	// Nécessaire pour bigup
	$valeurs['_bigup_rechercher_fichiers'] = true;

	return $valeurs;
}

function formulaires_configurer_tarteaucitron_ouverture_verifier_dist() {
	$erreurs = [];
	if (!empty($_FILES['upload_icon'])) {
		$file = $_FILES['upload_icon'];
		$verifier = charger_fonction('verifier', 'inc/');
		$options_verif = [
			'mime' => 'image_web',
			'hauteur_max' => 50,
			'largeur_max' => 50,
		];

		if ($erreur = $verifier($file, 'fichiers', $options_verif)) {
			$erreurs['upload_icon'] = $erreur;
			$dest = $file['tmp_name'];
			if (file_exists($dest)) {
				@unlink($dest);
			}
		}
	}

	return $erreurs;
}

function formulaires_configurer_tarteaucitron_ouverture_traiter_dist() {
	include_spip('inc/cvt_configurer');

	$retours = [
		'message_ok' => _T('config_info_enregistree'),
		'editable' => true,
	];

	// On garde en mémoire l'existant (l'API vide sinon)
	if ($icone_actuelle = lire_config('tarteaucitron/icon')) {
		set_request('icon', $icone_actuelle);
	}

	// On enregistre la nouvelle configuration
	$trace = cvtconf_formulaires_configurer_enregistre('configurer_tarteaucitron_ouverture', []);

	// On vérifie si on supprime l'image existante
	if (_request('supprimer_upload_icon')) {
		ecrire_config('tarteaucitron/icon', '');
		if ($icone_actuelle) {
			@unlink($icone_actuelle);
		}
		// On vérifie si on envoie une nouvelle image
	} elseif (!empty($_FILES['upload_icon'])) {
		include_spip('action/ajouter_documents');

		$file = $_FILES['upload_icon'];
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		$extension = corriger_extension(strtolower($extension));
		$dest_name = 'tarteaucitron_icon.' . $extension;
		$dest = _DIR_IMG . $dest_name;

		include_spip('inc/documents');
		if ($chemin = deplacer_fichier_upload($file['tmp_name'], $dest)) {
			if ($icone_actuelle != $dest) {
				@unlink($icone_actuelle);
			}
			ecrire_config('tarteaucitron/icon', $dest);
		} else {
			$retours['message_ok'] = '';
			$retours['message_erreur'] = _T('texte_inc_meta_1', ['fichier' => $dest_name]) . ' ' .
										 _T('texte_inc_meta_2') . ' ' .
										 _T('texte_inc_meta_3', ['repertoire' => _DIR_IMG]);
		}
	}

	// Cette partie est vraiment nécessaire ?
	include_spip('inc/invalideur');
	suivre_invalideur('1'); # tout effacer

	return $retours;
}

function formulaires_configurer_tarteaucitron_ouverture_saisies_dist() {
	$saisies = [
		[
			'saisie' => 'selection',
			'options' => [
				'nom' => 'ouverture',
				'option_intro' => '<:tarteaucitron:cfg_aucun:>',
				'label' => '<:tarteaucitron:cfg_ouverture_type:>',
				'defaut' => '',
				'datas' => [
					'image' => '<:tarteaucitron:cfg_image:>',
					'alertSmall' => '<:tarteaucitron:cfg_small_alert:>',
				],
			],
		],
		[
			'saisie' => 'selection',
			'options' => [
				'nom' => 'iconPosition',
				'label' => '<:tarteaucitron:cfg_iconposition:>',
				'cacher_option_intro' => 'oui',
				'defaut' => 'BottomRight',
				'datas' => [
					'BottomRight' => '<:tarteaucitron:cfg_position_bd:>',
					'BottomLeft' => '<:tarteaucitron:cfg_position_bg:>',
					'TopRight' => '<:tarteaucitron:cfg_position_hd:>',
					'TopLeft' => '<:tarteaucitron:cfg_position_hg:>',
				],
				'afficher_si' => '@ouverture@ == "image"',
			],
		],
		[
			'saisie' => 'tac_upload',
			'options' => [
				'nom' => 'upload_icon',
				'type' => 'file',
				'attributs' => 'accept=image/*',
				'class' => 'bigup',
				'label' => '<:tarteaucitron:cfg_icon:>',
				'explication' => '<:tarteaucitron:cfg_icon_explication:>',
				'afficher_si' => '@ouverture@ == "image"',
				'src_img' => lire_config('tarteaucitron/icon'),
			],
		],
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'cookieslist',
				'label' => '<:tarteaucitron:cfg_cookieslist:>',
				'explication' => '<:tarteaucitron:cfg_cookieslist_explication:>',
				'datas' => [
					'true' => '<:item_oui:>',
				],
				'afficher_si' => '@ouverture@ == "alertSmall"',
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
