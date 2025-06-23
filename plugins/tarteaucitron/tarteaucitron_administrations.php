<?php

/**
 * Fichier gérant l'installation et désinstallation du plugin Tarteaucitron
 *
 * @plugin     Tarteaucitron
 * @copyright  2019
 * @author     Peetdu
 * @licence    GNU/GPL
 * @package    SPIP\Tarteaucitron\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction d'installation et de mise à jour du plugin Tarteaucitron.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 **/
function tarteaucitron_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	$maj['create'] = [
		['ecrire_config', 'tarteaucitron', [
			'boutons' => 'both',
			'highprivacy' => ['true'],
			'acceptallcta' => ['true'],
			'showIcon' => ['true'],
			'mandatory' => ['true'],
			'moreInfoLink' => ['true'],
		]],
		['maj_tarteaucitron_cfg'],
	];

	$maj['1.1.0'][] = ['maj_tarteaucitron_cfg'];
	$maj['1.1.1'][] = ['maj_tarteaucitron_cfg', 'maj_tarteaucitron_services'];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Fonction de désinstallation du plugin Tarteaucitron.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 **/
function tarteaucitron_vider_tables($nom_meta_base_version) {
	$icon = _DIR_IMG . 'tarteaucitron_icon.png';
	if (file_exists($icon)) {
		@unlink($icon);
	}
	effacer_meta('tarteaucitron');
	effacer_meta($nom_meta_base_version);
}

function maj_tarteaucitron_cfg() {
	$cfg = lire_config('tarteaucitron');

	if (isset($cfg['boutons'])) {
		$cfg['boutons'] = ($cfg['boutons'] == 'twice') ? 'both' : $cfg['boutons'];
	} else {
		$cfg['boutons'] = 'both';
	}

	$readmoreLink = $cfg['readmoreLink'] ?? '';
	$moreInfoLink = !isset($readmoreLink) ? ['true'] : ['false'];
	$cfg['moreInfoLink'] = $moreInfoLink;

	if (isset($cfg['services'])) {
		foreach ($cfg['services'] as $service => $value) {
			if (!empty($value)) {
				switch ($service) {
					case 'atinternet':
						if (!is_array($value) and !is_array($cfg['services']['atinternet'])) {
							$cfg['services']['atinternet'] = ['SMARTTAG_JS_LINK' => 'https://tag.aticdn.net/' . $value . '/smarttag.js'];
						}
						break;
					case 'fb_pixel':
						$cfg['services']['facebookpixel'] = ['YOUR-ID' => $value];
						unset($cfg['services']['fb_pixel']);
						break;
					case 'fb':
						$cfg['services']['facebook'] = [];
						unset($cfg['services']['fb']);
						break;
					case 'gmap':
						$cfg['services']['googlemaps'] = ['API_KEY' => $value];
						unset($cfg['services']['gmap']);
						break;
					case 'gtag':
						if (!is_array($value) and !is_array($cfg['services']['gtag'])) {
							$cfg['services']['gtag'] = ['G-XXXXXXXXX' => $value];
						}
						break;
					case 'twitter':
					case 'twitterembed':
						$cfg['services'][$service] = [];
						break;
				}
			} else {
				unset($cfg['services'][$service]);
			}
		}
	}

	ecrire_config('tarteaucitron', $cfg);
}

function maj_tarteaucitron_services() {
	$cfg = lire_config('tarteaucitron');

	foreach ($cfg['services'] as $service => $value) {
		foreach ($value as $k => $v) {
			$param = preg_replace('/ptac_(p[0-9]{1,}_)?/', '', $k);
			$param = preg_replace('/UA-XXXXXXXX-X/', 'G-XXXXXXXXX', $param); // pour gtag qui à changer de nom
			$cfg['services'][$service][$param] = $v;
		}
	}
	ecrire_config('tarteaucitron', $cfg);
}
