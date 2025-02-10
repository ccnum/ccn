<?php

/**
 * Trouver et manipuler les data des saisies,
 * qu'elles soient sous forme tabulaire ou sous forme de liste
 *
 * @package SPIP\Saisies\Data
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Aplatit une description chaînée, en supprimant les sous-groupes.
 * @param string $chaine La chaîne à aplatir
 * @return string
 */
function saisies_aplatir_chaine($chaine) {
	$chaine = explode("\n", trim($chaine));
	$chaine = array_filter($chaine, function ($ligne) {
		if (substr($ligne, 0, 2) === '/*' || substr($ligne, 0, 1) === '*') {
			return false;
		} else {
			return true;
		}
	});
	return implode("\n", $chaine);
}
/**
 * Transforme une chaine en tableau avec comme principe :
 *
 * - une ligne devient une case
 * - si la ligne est de la forme truc|bidule alors truc est la clé et bidule la valeur
 * - si la ligne commence par * alors on commence un sous-tableau
 * - si la ligne est égale à /*, alors on finit le sous-tableau
 *
 * @param string $chaine Une chaine à transformer
 * @param string $separateur Séparateur utilisé
 * @return array Retourne un tableau PHP
 */
function saisies_chaine2tableau($chaine, $separateur = "\n") {
	if ($chaine && is_string($chaine)) {
		$tableau = [];
		$soustab = false;

		// On découpe d'abord en lignes
		$lignes = explode($separateur, $chaine);
		foreach ($lignes as $i => $ligne) {
			$ligne = trim(trim($ligne), '|');
			// Si ce n'est pas une ligne sans rien
			if ($ligne !== '') {
				// si ca commence par * c'est qu'on va faire un sous tableau
				if (strpos($ligne, '*') === 0) {
					$soustab = true;
					$soustab_cle = _T_ou_typo(substr($ligne, 1), 'multi');
					if (!isset($tableau[$soustab_cle])) {
						$tableau[$soustab_cle] = [];
					}
				} elseif ($ligne == '/*') {//si on finit sous tableau
					$soustab = false;
				} else {
					//sinon c'est une entrée normale
					// Si on trouve un découpage dans la ligne on fait cle|valeur
					if (strpos($ligne, '|') !== false) {
						list($cle,$valeur) = explode('|', $ligne, 2);
						// permettre les traductions de valeurs au passage
						if ($soustab == true) {
							$tableau[$soustab_cle][$cle] = _T_ou_typo($valeur, 'multi');
						} else {
							$tableau[$cle] = _T_ou_typo($valeur, 'multi');
						}
					} else {
						// Sinon on génère la clé
						if ($soustab == true) {
							$tableau[$soustab_cle][$i] = _T_ou_typo($ligne, 'multi');
						} else {
							$tableau[$i] = _T_ou_typo($ligne, 'multi');
						}
					}
				}
			}
		}
		return $tableau;
	} elseif (is_array($chaine)) {
		return $chaine;
	} else {
		return [];
	}
}

/**
 * Transforme un tableau en chaine de caractères avec comme principe :
 *
 * - une case devient une ligne de la chaine
 * - chaque ligne est générée avec la forme cle|valeur
 * - si une entrée du tableau est elle même un tableau, on met une ligne de la forme *clef
 * - pour marquer que l'on quitte un sous-tableau, on met une ligne commencant par /*, sauf si on bascule dans un autre sous-tableau.
 *
 * @param array $tableau Tableau à transformer
 * @return string Texte représentant les données du tableau
 */
function saisies_tableau2chaine($tableau) {
	if ($tableau && is_array($tableau)) {
		$chaine = '';
		$avant_est_tableau = false;

		foreach ($tableau as $cle => $valeur) {
			if (is_array($valeur)) {
				$avant_est_tableau = true;
				$ligne = trim("*$cle");
				$chaine .= "$ligne\n";
				$chaine .= saisies_tableau2chaine($valeur) . "\n";
			} else {
				if ($avant_est_tableau == true) {
					$avant_est_tableau = false;
					$chaine .= "/*\n";
				}
				$ligne = trim("$cle|$valeur");
				$chaine .= "$ligne\n";
			}
		}
		$chaine = trim($chaine);

		return $chaine;
	}
	elseif (is_string($tableau)) {
		// Si c'est déjà une chaine on la renvoie telle quelle
		return $tableau;
	}
	else {
		return '';
	}
}

/**
 * Transforme une valeur en tableau d'élements si ce n'est pas déjà le cas
 *
 * @param mixed $valeur
 * @param array $data
 *	Options `data` de la saisie
 * @return array Tableau de valeurs
 **/
function saisies_valeur2tableau($valeur, $data = []) {
	$data = saisies_aplatir_tableau($data);
	$tableau = [];

	if ($valeur === null) {
		$tableau = [];
	} elseif (is_array($valeur)) {
		$tableau = $valeur;
	} elseif (strlen($valeur)) {
		$tableau = saisies_chaine2tableau($valeur);

		// Si qu'une seule valeur, c'est qu'elle a peut-être un separateur à virgule
		// et a donc une clé 0 dans ce cas la d'ailleurs
		if (count($tableau) == 1 && isset($tableau[0])) {
			$tableau = saisies_chaine2tableau($tableau[0], ',');
		}
	}

	// On vérifie la pertinence des valeurs pour s'assurer d'avoir le choix alternatif dans sa clé à part
	if (is_array($data) && $data) {
		foreach ($tableau as $cle => $valeur) {
			if (!in_array($valeur, array_keys($data))) {
				$choix_alternatif = $valeur;
				unset($tableau[$cle]);
				$tableau['choix_alternatif'] = $valeur;
			}
		}
	}

	return $tableau;
}

/**
 * Pour les saisies multiples (type checkbox) proposant un choix alternatif,
 * retrouve à partir des data de choix proposés
 * et des valeurs des choix enregistrés
 * le texte enregistré pour le choix alternatif.
 *
 * @param array $data
 * @param array $valeur
 * @return string choix_alternatif
 **/
function saisies_trouver_choix_alternatif($data, $valeur) {
	if (!is_array($valeur)) {
		$valeur = saisies_valeur2tableau($valeur);
	}
	if (!is_array($data)) {
		$data = saisies_chaine2tableau($data) ;
	}

	$choix_theorique = array_keys($data);
	$choix_alternatif = array_values(array_diff($valeur, $choix_theorique));
	if (isset($choix_alternatif[0])) {
		return $choix_alternatif[0]; //on suppose que personne ne s'est amusé à proposer deux choix alternatifs
	} else {
		return '';
	}
}


/**
 * Aplatit une description tabulaire en supprimant les sous-groupes.
 * Ex : les data d'une saisie de type select
 *
 * @param array $tab            Le tableau à aplatir
 * @param bool  $masquer_sous_groupe mettre à true pour ne pas montrer le sous-groupe dans les label humain
 *
 * @return array
 */
function saisies_aplatir_tableau($tab, $masquer_sous_groupe = false) {
	$nouveau_tab = [];
	if (is_string($tab)) {
		$tab = saisies_chaine2tableau($tab);
	}
	if (is_array($tab)) {
		foreach ($tab as $entree => $contenu) {
			if (is_array($contenu)) {
				foreach ($contenu as $cle => $valeur) {
					if ($masquer_sous_groupe) {
						$nouveau_tab[$cle] = $valeur;
					} else {
						$nouveau_tab[$cle] = _T('saisies:saisies_aplatir_tableau_montrer_groupe', ['valeur' => $valeur, 'groupe' => $entree]);
					}
				}
			} else {
				$nouveau_tab[$entree] = $contenu;
			}
		}
	}

	return $nouveau_tab;
}


/**
 * Trouve le champ data ou datas (pour raison historique)
 * parmi les paramètres d'une saisie
 * et le retourne après avoir l'avoir transformé en tableau si besoin
 * @param array $description description de la saisie
 * @bool $disable_choix : si true, supprime les valeurs contenu dans l'option disable_choix des data
 * @return array data
 **/
function saisies_trouver_data($description, $disable_choix = false) {
	$options = $description['options'];
	if (isset($options['data'])) {
		$data = $options['data'];
	} elseif (isset($options['datas'])) {
		$data = $options['datas'];
	} else {
		$data = [];//normalement on peut pas mais bon
	}
	$data = saisies_chaine2tableau($data);

	if ($disable_choix == true && isset($options['disable_choix'])) {
		$disable_choix = array_flip(saisies_normaliser_liste_choix($options['disable_choix']));
		$data = array_diff_key($data, $disable_choix);
	}
	return $data;
}

/**
 * Prend une liste de choix (clés de tableau data) en entrée
 * Si tableau, renvoi presque tel quel
 * Si chaine, l'explose au niveau des virgules
 * Trim dans tous les cas les différents choix
 * @param array|string $liste
 * @return array
 **/
function saisies_normaliser_liste_choix($liste): array {
	if (is_array($liste)) {
		// nothing to do
	} elseif ($liste === null) {
		$liste = [];
	} elseif (strlen($liste)) {
		$liste = explode(',', trim($liste));
	} else {
		$liste = [];
	}
	$liste = array_map('trim', $liste);
	$liste = array_filter($liste);
	return $liste;
}

/**
 * @deprecated
 * Ancien nom de la fonction saisies_normaliser_liste_choix
 * @param array|string $liste
 * @return array
**/
function saisies_normaliser_disable_choix($liste): array {
	trigger_error('La fonction `saisies_normaliser_disable_choix()` est depréciée et sera supprimée en v6 du plugin. Utiliser `saisies_normaliser_liste_choix()` à la place', E_USER_DEPRECATED);
	spip_log('La fonction `saisies_normaliser_disable_choix()` est depréciée et sera supprimée en v6 du plugin. Utiliser `saisies_normaliser_liste_choix()` à la place', 'deprecated_saisies');
	return saisies_normaliser_liste_choix($liste);
}

/**
 * Prend un tableau de data
 * enlève les choix dépubliés
 * sauf si la valeur courante de la saisie
 * @param $data
 * @param array|string $depublie_choix
 * @param array|string $valeur
 * @return array
**/
function saisies_depublier_data(?array $data = [], $depublie_choix = [], $valeur = []): array {
	if (!$data) {
		return [];
	}
	$depublie_choix = saisies_normaliser_liste_choix($depublie_choix);
	if (!is_array($valeur)) {
		$valeur = [$valeur];
	}
	$depublie_choix = array_diff($depublie_choix, $valeur);
	$depublie_choix = array_flip($depublie_choix);

	$data = array_diff_key($data, $depublie_choix);

	// Le cas des data structurés en sous liste
	foreach ($data as $key => &$value) {
		if (is_array($value)) {
			$value = array_diff_key($value, $depublie_choix);
		}
	}
	$data = array_filter($data);// Ne pas laisser de sous-groupe vide

	return $data;
}

/**
 * Return la liste des saisies qui nécessite qu'on insère un hidden avec les précédentes valeurs.
 * C'est à dire : uniquement les saisies qui sont des depublies et sousmis à test de valeurs_acceptables
 * @param array $saisies description complète de saisies
 * @return array $liste_saisies
**/
function saisies_lister_necessite_retenir_ancienne_valeur(array $saisies): array {
	$liste = [];
	$depublie_choix = saisies_lister_avec_option('depublie_choix', $saisies);
	if ($saisies['options']['verifier_valeurs_acceptables'] ?? '') {
		return array_keys($depublie_choix);
	}
	foreach ($depublie_choix as $saisie => $config) {
		if (($config['verifier']['type'] ?? '') === 'valeurs_acceptables') {
			$liste[] = $saisie;
		} elseif (isset($config['verifier'])) {
			$verifier_type = array_column($config['verifier'], 'type');
			if (in_array('valeurs_acceptables', $verifier_type)) {
				$liste[] = $saisie;
			}
		}
	}
	return $liste;
}
