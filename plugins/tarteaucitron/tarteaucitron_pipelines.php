<?php

/**
 * Utilisations de pipelines par Tarteaucitron
 *
 * @plugin     Tarteaucitron
 * @copyright  2019
 * @author     Peetdu
 * @licence    GNU/GPL
 * @package    SPIP\Tarteaucitron\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/tarteaucitron');

/**
 * Inserer tarteaucitron.js + le javascript contenant les paramètres venant de la config du plugin
 *
 * @pipeline insert_head
 * @pipeline recuperer_fond
 *
 * @param string $flux
 * 	Le contenu de la balise #INSERT_HEAD
 * @return mixed
 */
function tarteaucitron_insert_head($flux) {
	if (tarteaucitron_actif()) {
		$css = produire_fond_statique('tarteaucitron_custom.css');
		$flux .= "<link rel='stylesheet' href='" . $css . "' type='text/css' />";

		$tarteaucitron = find_in_path('lib/tarteaucitron/tarteaucitron.js');
		$tarteaucitron_config = recuperer_fond('javascript/tarteaucitron_config');
		// Note importante : ici on ajoute un id à la déclaration du script pour que celui-ci ne soit pas compressé par le Compresseur de SPIP si ce dernier est activé.
		$flux .= "<script type='text/javascript' src='$tarteaucitron' id='tauc'></script>\n"
			. "$tarteaucitron_config\n";
		if ($infos = lire_config('tarteaucitron/services/eulerian')) {
			$flux .= "<!-- Chargement librairie  -->
			<script type='text/javascript'>
				(function(e,a){var i=e.length,y=5381,k='script',s=window,v=document,o=v.createElement(k);for(;i;){i-=1;y=(y*33)^e.charCodeAt(i)}y='_EA_'+(y>>>=0);(function(e,a,s,y){s[a]=s[a]||function(){(s[y]=s[y]||[]).push(arguments);s[y].eah=e;};}(e,a,s,y));i=new Date/1E7|0;o.ea=y;y=i%26;o.async=1;o.src='//'+e+'/'+String.fromCharCode(97+y,122-y,65+y)+(i%1E3)+'.js?2';s=v.getElementsByTagName(k)[0];s.parentNode.insertBefore(o,s);})
				('" . $infos['host'] . "','EA_push');
			</script>
			<!-- Fin de chargement librairie Eulerian-->\n
			<!-- Chargement librairie -->
			<script type='text/javascript'>
				// Integration Eulerian / TarteAuCitron
				tarteaucitron.services['eulerian'] = {
					'key': 'eulerian',
					'type': 'analytic',
					'name': 'Eulerian Analytics',
					'needConsent': true,
					'cookies': ['etuix'],
					'uri' : 'https://eulerian.com/vie-privee',
					'js': function () {
						'use strict';
						(function(x,w){ if (!x._ld){ x._ld = 1;
							let ff = function() { if(x._f){x._f('tac',tarteaucitron,1)} };
							w.__eaGenericCmpApi = function(f) { x._f = f; ff(); };
							w.addEventListener('tac.close_alert', ff);
							w.addEventListener('tac.close_panel', ff);
							}})(this,window);
					},
					'fallback': function () { this.js(); }
				};
				(tarteaucitron.job = tarteaucitron.job || []).push('eulerian');
			</script>
			<!-- Fin de chargement librairie Eulerian-->\n";
		}
	}
	return $flux;
}

/**
 * Inserer les JS correspondants aux services activés
 *
 * @pipeline affichage_final
 *
 * @return mixed
 */
function tarteaucitron_affichage_final($html) {
	if ($GLOBALS['html'] and !test_espace_prive() and tarteaucitron_actif()) {
		$ajouter_services = '<script type="text/javascript">';
		$json_source = find_in_path('json/services.json');
		$json = file_get_contents($json_source);
		$parsed_json = json_decode($json);
		$services_actifs = lire_config('tarteaucitron/services');

		$valeurs = [];
		// Tester la présence d'un formulaire formidable dans la page article
		if (strpos($html, 'class="formulaire_spip formulaire_formidable') !== false) {
			$formulaire = 'oui';
		}
		// Récupérer le nombre de résultats dans la recherche
		$re = '#recherche__total">(\n)?(\t)?(\t)?(?P<digit>\d+)#';
		if (preg_match($re, $html, $matches)) {
			$valeurs['nbr_resultats'] = $matches['digit'];
		}

		foreach ($GLOBALS['contexte'] as $k => &$v) {
			if (
				preg_match(',^id_(\w+)$,S', $k, $r)
				and ($id = intval($v)) > 0
			) {
				$valeurs[$k] = $id;
			} elseif ($k === 'type-page') {
				if (isset($v)) {
					$valeurs['page'] = $v;
				}
			} elseif ($k === 'max') {
				$valeurs['resultat_page'] = $v;
			} elseif ($k === 'max_articles') {
				$valeurs['pagination'] = $v;
			} else {
				$valeurs[$k] = $v;
			}
		}
		$eulerian = '';
		foreach ($services_actifs as $service => $params) {
			if (isset($parsed_json->{$service}->{'JS'})) {
				$codejs = $parsed_json->{$service}->{'JS'};

				if (is_array($params)) {
					$i = 0;
					foreach ($params as $param => $value) {
						$codejs = preg_replace('#ptac_' . $param . '#', $value, $codejs);
						$service_plus = '';
						if (find_in_path('services/' . $service . '.html') and $i == '0') {
							$service_plus = recuperer_fond('services/' . $service, $valeurs);
						}
						$codejs = preg_replace(
							"/tarteaucitron.user.(.*)More = function \(\) { \/\* (.*) \*\/ };/",
							$service_plus,
							$codejs
						);
						$i++;
					}
				}

				if (defined('_TAC_SITE_ENTITY') and $service == 'eulerian') {
					$info_plus = '';
					if (defined('_TAC_SITE_REGION')) {
						$info_plus .= "window.EA_datalayer.push('site_region', '" . _TAC_SITE_REGION . "');\n";
					}
					if (defined('_TAC_SITE_DEPARTMENT')) {
						$info_plus .= "window.EA_datalayer.push('site_department', '" . _TAC_SITE_DEPARTMENT . "');\n";
					}
					if (defined('_TAC_SITE_TARGET')) {
						$info_plus .= "window.EA_datalayer.push('site_target', '" . _TAC_SITE_TARGET . "');\n";
					}
					$fichier = 'eulerian_';
					$fichier .= ($valeurs['page'] ?? 'sommaire');
					$fichier .= (isset($formulaire) ? '_formulaire' : '');
					$fichier .= (isset($valeurs['id_formulaires_reponse']) ? '_formulaire_confirmation' : '');
					$handle = find_in_path('services/' . $fichier . '.html');
					if ($handle) {
						$eulerian .= "(function(){
							window.EA_datalayer = [];
							window.EA_datalayer.push('site_entity', '" . _TAC_SITE_ENTITY . "');
							window.EA_datalayer.push('site_type', 'standard');
							" . $info_plus . recuperer_fond('services/' . $fichier, $valeurs) . '
							window.EA_push(window.EA_datalayer);
						})();';
					}
				} else {
					$ajouter_services .= $codejs . "\n";
				}
			}
		}

		$ajouter_services .= $eulerian;
		$ajouter_services .= '</script>';

		$html = str_replace('</body>', $ajouter_services . '</body>', $html);
	}
	return $html;
}

/**
 * Ajouter des entrées dans le porte-plume
 *
 * @pipeline porte_plume_barre_pre_charger
 *
 * @param array $barres
 * @return array
 */
function tarteaucitron_porte_plume_barre_pre_charger($barres) {
	$menu_items = [];
	$services_actifs = lire_config('tarteaucitron/services', []);
	$json_source = find_in_path('json/services.json');
	$json = file_get_contents($json_source);
	$parsed_json = json_decode($json);
	$liste_modeles = find_all_in_path('modeles/', 'tac_');

	foreach ($services_actifs as $service => $params) {
		$params_modele = (!empty($parsed_json->{$service}->{'params_modele'})) ? $parsed_json->{$service}->{'params_modele'} : [];

		foreach ($liste_modeles as $path_modele) {
			$elems = explode('/', $path_modele);
			$nom_fichier = end($elems);
			$close = '';
			$nom_modele = '';

			if ($nom_fichier == 'tac_' . $service . '.html') {
				$nom_modele = 'tac_' . $service;
				$prop_modele = $service;
			} elseif (strpos($nom_fichier, 'tac_' . $service . '_') !== false) {
				$nom_modele = substr($nom_fichier, 0, strpos($nom_fichier, '.html'));
				$prop_modele = substr($nom_modele, 4, strlen($nom_modele) - 4);

				if (property_exists($params_modele, $prop_modele)) {
					$params_modele = $params_modele->{$prop_modele};
				}
			}

			if (find_in_path('icones_barre/' . $nom_modele . '.png')) {
				foreach ($params_modele as $param_modele) {
					$close .= '|' . $param_modele . '=[![' . $param_modele . ' :]!]';
				}

				$menu_items[] = [
					'id' => $prop_modele,
					'name' => $prop_modele,
					'className' => $nom_modele,
					'openWith' => '<' . $nom_modele,
					'closeWith' => $close . '>',
					'display' => true,
				];
			}
		}
	}

	if (count($menu_items) != 0) {
		$new_button = [
			'id' => 'tac_drop',
			'name' => 'TarteAuCitron',
			'className' => 'tac_drop',
			'replaceWith' => '',
			'display' => true,
		];

		$new_button['dropMenu'] = $menu_items;
		$barre = &$barres['edition'];
		$barre->ajouterApres('grpCaracteres', $new_button);
	}

	return $barres;
}

/**
 * Définir des images pour les classes CSS utilisées dans le porte-plume
 *
 * @pipeline porte_plume_lien_classe_vers_icone
 *
 * @param array $flux
 * @return array
 */
function tarteaucitron_porte_plume_lien_classe_vers_icone($flux) {
	$icons = ['tac_drop' => 'tac.png'];
	$services_actifs = lire_config('tarteaucitron/services', []);
	$liste_modeles = find_all_in_path('modeles/', 'tac_');

	foreach ($services_actifs as $service => $value) {
		foreach ($liste_modeles as $path_modele) {
			$elems = explode('/', $path_modele);
			$nom_fichier = end($elems);
			$img_name = '';

			if (($nom_fichier == 'tac_' . $service . '.html') || (strpos($nom_fichier, 'tac_' . $service . '_') !== false)) {
				$img_name = substr($nom_fichier, 0, strpos($nom_fichier, '.html'));
			}

			if (($img_name != '') && (find_in_path('icones_barre/' . $img_name . '.png'))) {
				$icons[$img_name] = $img_name . '.png';
			}
		}
	}

	return array_merge($flux, $icons);
}
