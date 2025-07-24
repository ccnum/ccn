<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mise à jour du fichier services/services.html
 * appelé avec ?action=tarteaucitron_referencer_services
 * autorisé pour les seuls webmestres
 */
function action_tarteaucitron_referencer_services_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('webmestre')) {
		die('Pas autorise');
	}

	$list_services = [];
	$services_specifiques = [];

	if ($lines = file(find_in_path('lib/tarteaucitron/tarteaucitron.services.js'))) {
		$i = 0;
		foreach ($lines as $line) {
			if (preg_match('/^tarteaucitron\.services\.(\w+)/', $line, $matches)) {
				$service = $matches[1];

				$list_services[$service] = [];
				$list_services[$service]['hasModele'] = false;
				$list_services[$service]['params'] = [];

				if (preg_match('/["\']?type["\']?: ["\'](\w+)["\'],/', $lines[$i + 2], $matches)) {
					$type = $matches[1];
					$list_services[$service]['type'] = $type;
				}
			}
			$i++;
		}
	}

	$tarteaucitron_url = 'https://tarteaucitron.io/fr/install/';
	$html = file_get_contents($tarteaucitron_url);
	$doc = new DOMDocument('1.0', 'UTF-8');
	$internalErrors = libxml_use_internal_errors(true);
	$doc->loadHTML($html);
	libxml_use_internal_errors($internalErrors);

	foreach ($list_services as $service => $prop) {
		$div_service = $doc->getElementById('s_' . $service);

		if (!empty($div_service)) {
			$id = $div_service->lastChild->attributes['id']->value;
			$div_code = $doc->getElementById('m' . $id);
			$span_list = $div_code->getElementsByTagName('span');
			$nb_items = $span_list->length;
			$k = 0;
			$is_modele_found = false;
			$is_service_hidden = in_array($list_services[$service]['type'], ['support', 'analytic']);
			if (!$is_service_hidden) {
				// Gestion des services spécifiques
				switch ($service) {
					case 'facebook':
						$list_services[$service]['hasModele'] = true;
						$list_services[$service]['params_modele']['facebook_video'][] = 'video_id';
						$services_specifiques[] = $service;
						break;
					default:
						$list_services[$service]['params_modele'] = [];
						break;
				}
			}

			// Pour chaque span.code
			for ($i = 0; $i < $nb_items; $i++) {
				$span = $span_list->item($i);
				$class_attr = $span->attributes->getNamedItem('class');

				if (($class_attr !== null) && ($class_attr->nodeValue == 'code')) {
					$k++;

					// Récupère le code JS pour activer le service
					if ($k == 1) {
						$param_list = $span->getElementsByTagName('b');
						$nb_params = $param_list->length;
						$j = $nb_params - 1;

						while ($j > -1) {
							$param = $param_list[$j];
							$nom_param = 'ptac_' . str_replace(' ', '_', $param->textContent);
							$newelement = $doc->createTextNode("'" . $nom_param . "'");
							$span->replaceChild($newelement, $param);
							array_unshift($list_services[$service]['params'], $nom_param);
							$j--;
						}

						$s = $span->getElementsByTagName('s');
						$span->removeChild($s[0]);
						$script = trim(strip_tags($span->nodeValue));
						$script = preg_replace('/(\n\s+)/', "\n", $script);
						$script = str_replace("''", "'", $script);
						$list_services[$service]['JS'] = $script;
						// Récupère le modèle HTML
					} elseif (($i != $nb_items - 1) && (!$is_modele_found)) {
						$is_modele_found = true;
						$pattern = [];
						$param_list = $span->getElementsByTagName('b');
						$nb_params = $param_list->length;
						$j = $nb_params - 1;

						while ($j > -1) {
							$param = $param_list[$j];
							$nom_param = str_replace(' ', '_', $param->textContent);
							// Si le paramètre contient des valeurs entre parenthèses comme (true | false), on les enlève
							if (preg_match('/(_\(.+\))/', $nom_param, $matches)) {
								$nom_param = str_replace($matches[1], '', $nom_param);
							}

							if ($is_service_hidden) {
								$nom_param = 'ptac_' . $nom_param;

								if (!in_array($nom_param, $list_services[$service]['params'])) {
									array_unshift($list_services[$service]['params'], $nom_param);
								}
							} else {
								array_unshift($list_services[$service]['params_modele'], $nom_param);
								$nom_param = '(#ENV{' . $nom_param . '})';
								$pattern[] = '/(\s?[\w\-.]*\s?[=:]?\s?[\'\"]?' . quotemeta($nom_param) . '[\'\"]?\w*[,;]?)/';
							}

							$newelement = $doc->createTextNode($nom_param);
							$span->replaceChild($newelement, $param);
							$j--;
						}

						$script = trim($span->nodeValue);

						if (!$is_service_hidden) {
							// On ajoute les crochets pour la syntaxe SPIP
							$script = preg_replace($pattern, '[${1}]', $script);
						}

						$script = preg_replace('/(\n\s+)/', "\n", $script);
						$script = str_replace("''", "'", $script);

						if ($is_service_hidden) {
							$script = strip_tags($script);
							$list_services[$service]['JS'] = $script . "\n" . $list_services[$service]['JS'];
						} elseif (!in_array($service, $services_specifiques)) {
							$list_services[$service]['hasModele'] = true;
							$fichier_modele = _DIR_PLUGIN_TARTEAUCITRON . 'modeles/tac_' . $service . '.html';
							file_put_contents($fichier_modele, $script);
						}
					}
				}
			}

			// Supprimer les clés qui sont des tableaux vides
			foreach ($list_services[$service] as $prop => $value) {
				if ((is_array($list_services[$service][$prop])) && (empty($list_services[$service][$prop]))) {
					unset($list_services[$service][$prop]);
				}
			}
		} else {
			unset($list_services[$service]);
		}
	}

	// Création du JSON avec les services, leur type, leur(s) paramètre(s) et le code JS associé
	$file_json = find_in_path('json/services.json');

	if (file_put_contents($file_json, json_encode($list_services))) {
		echo 'Opération réussie !<br>';
		echo 'Le fichier <b>' . $file_json . '</b> a été mis à jour.';
	} else {
		echo "Erreur lors de l'écriture du fichier " . $file_json . '.';
	}
}
