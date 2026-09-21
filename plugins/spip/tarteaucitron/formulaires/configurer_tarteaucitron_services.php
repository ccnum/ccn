<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/tarteaucitron');

function formulaires_configurer_tarteaucitron_services_saisies_dist() {
	$saisies = [];
	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);
	$types_actifs = tarteaucitron_liste_types_actifs();

	foreach ($types_actifs as $type => $liste_services) {
		$fieldset = [
			'saisie' => 'fieldset',
			'options' => [
				'nom' => $type,
				'label' => $type,
				'pliable' => 'oui',
				'plie' => 'oui',
			],
		];

		foreach ($liste_services as $service => $params) {
			$has_modele = $parsed_json->{$service}->{'hasModele'};

			$fieldset['saisies'][] = [
				'saisie' => 'checkbox',
				'options' => [
					'nom' => $service . '_actif',
					'disable' => 'oui',
					'datas' => [
						$service => $service,
					],
					'defaut' => [$service],
				],
			];

			if ($has_modele) {
				$liste_modeles = scandir(_DIR_PLUGIN_TARTEAUCITRON . 'modeles');
				$modeles = '';

				foreach ($liste_modeles as $nom_fichier) {
					if (preg_match('/^tac_' . $service . '(_\w+)?.html$/', $nom_fichier)) {
						$modeles .= $nom_fichier . ', ';
					}
				}

				$modeles = '(' . substr($modeles, 0, -2) . ')';

				$fieldset['saisies'][] = [
					'saisie' => 'explication',
					'options' => [
						'nom' => $service . '_hasmodele',
						'titre' => _T('tarteaucitron:cfg_display_service', ['service' => $service]),
						'texte' => _T('tarteaucitron:cfg_ajouter_modele', ['modeles' => $modeles]),
					],
				];
			}

			$i = 0;
			foreach ($params as $param) {
				$param = substr($param, 5);
				$defaut = is_array($service) ? $service : $service . '/' . $param;
				$fieldset['saisies'][] = [
					'saisie' => 'input',
					'options' => [
						'nom' => $param,
						'label' => $param,
						'explication' => _T('tarteaucitron:cfg_parametre_service', ['service' => $service]),
						'placeholder' => $param,
						'defaut' => lire_config('tarteaucitron/services/' . $defaut),
						'obligatoire' => 'oui',
					],
				];
				$i++;
			}
		}

		$saisies[] = $fieldset;
	}

	return $saisies;
}

function formulaires_configurer_tarteaucitron_services_charger_dist() {
	$valeurs = [];
	$services_actifs = lire_config('tarteaucitron/services', []);

	foreach ($services_actifs as $service => $params) {
		if (!empty($params)) {
			foreach ($params as $name => $value) {
				$valeurs[$service] = $value;
			}
		}
	}

	return $valeurs;
}

function formulaires_configurer_tarteaucitron_services_traiter_dist() {
	$ret = [];
	$services_actifs = lire_config('tarteaucitron/services', []);
	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);

	foreach ($services_actifs as $service => $params) {
		if (isset($parsed_json->{$service}->{'params'}) and $params = $parsed_json->{$service}->{'params'}) {
			$i = 0;
			foreach ($params as $name => $value) {
				$valeur_saisie = _request(substr($value, 5));
				$valeur_saisie ??= '';
				$services_actifs[$service][substr($value, 5)] = $valeur_saisie;
				$i++;
			}
		}
	}

	if (ecrire_config('tarteaucitron/services', $services_actifs)) {
		$ret['message_ok'] = _T('config_info_enregistree');
	} else {
		$ret['message_erreur'] = _T('erreur_technique_enregistrement_impossible');
	}
	$ret['editable'] = true;

	return $ret;
}
