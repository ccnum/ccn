<?php

/**
 * API de vérification : vérification qu'une chaîne puisse décrire un tableau de data pour une saisies
 *
 * @plugin     saisies
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
/**
 * Vérifier qu'une saisie est sous forme d'une chaine transformable en tableau de data
 * C'est-à-dire de la forme cle|valeur
 * Et ce en autorisant les sous-groupes (sauf si demande contraire)
 * Mais en refusant les clés construite automatiquement pour des lignes sans pipe.
 * On en profite pour vérifier qu'il n'y pas de clé en double.
 * De plus les trim() pertubent les choses.
 * @param string $valeur
 *   La valeur à vérifier.
 * @param $options
 *	 interdire_sous_groupes => True|False
 *	 verifier_cles => array décrivant une vérification à effectuer
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_saisies_option_data_dist($valeur, $options = []) {

	// Le pb des clés implicites : on veut les interdire, car, bien que cela soit supporté historiquement par saisies_chaine2tableau, elles ne permettent pas d'assurer la cohésion dans le temps des données.
	// Voir discussion sur https://git.spip.net/spip-contrib-extensions/saisies/issues/54
	// Chercher les clés numériques dans $tableau_plat = saisies_aplatir_tableau(saisies_chaine2tableau($saisies)) ne marche pas car:
	//	 a. Des gens peuvent mettre des clés numériques explicites
	//	 b. Mais celles-ci peuvent être écrasées par les clés implicites (c'est vraiment la plaie les clés implicites)
	// Ex :
	// 1|Cle explicite
	// cle implicite
	// Et bien la clé implicite (1) surchargera la clé explicite.
	//	 c. Et l'on ne peut même pas vérifier la correspondance entre la clé dans $tableau_plat et une ce qu'on trouverai par une regexp dans $valeur, car dans $tableau_plat les chaines de langues ont deja été étendu. Donc rechercher <cle>|<valeur_humaine> dans $valeurs à partir de $tableau_plat peut échouer à tord
	// Par conséquent, pour déterminer les clés implicites, nous sommes obligés de parser les lignes du tableau et d'exclure :
	// 1. Les lignes vides
	// 2. Les débuts de sous-groupes
	// 3. Les fins de sous-groupes
	// 4. Les lignes avec clés explicites
	$lignes = explode("\n", $valeur);
	$lignes_avec_cle_implicite = array_filter($lignes, function ($ligne) {
		return
			trim($ligne)
			&& substr($ligne, 0, 1) !== '*'
			&& substr($ligne, 0, 2) !== '/*'
			&& !preg_match('#^(.*)\|#m', $ligne);
	});
	if ($lignes_avec_cle_implicite) {
		return _T('saisies:verifier_saisies_option_data_cle_manquante');
	}

	// Les sous groupes sont-ils interdits?
	if (
		($options['interdire_sous_groupes'] ?? '')
	) {
		foreach ($lignes as $ligne) {
			if (substr($ligne, 0, 1) === '*') {
				return _T('saisies:verifier_saisies_option_data_sous_groupes_interdits');
			}
		}
	}

	// Y-a-il des clés en double ?
	// On ne peut pas partir de $tableau_plat = saisies_aplatir_tableau(saisies_chaine2tableau($saisies)) car dans un $array php les clés sont déjà dédoublés
	preg_match_all('#^(.*)\|#m', $valeur, $les_cles);
	$les_cles = $les_cles[1];
	if ($les_cles != array_unique($les_cles)) {
		return _T('saisies:verifier_saisies_option_data_cles_doubles');
	}

	// Vérifier, si besoin, que toutes les clés répondent bien à certains critères (qu'on définit via une description de vérification).
	if (is_array($options['verifier_cles'] ?? '')) {
		$verifier = charger_fonction('verifier', 'inc');
		$cles_erronnees = [];
		foreach ($les_cles as $cle) {
			if ($erreur = $verifier($cle, $options['verifier_cles']['type'], $options['verifier_cles']['options'])) {
				$cles_erronnees[] = "$cle => $erreur";
			}
		}
		if ($cles_erronnees) {
			return _T('saisies:verifier_saisies_option_data_verifier_cles_erreurs') . '<br />' . implode('<br />', $cles_erronnees);
		}
	}

	// Et sinon, c'est que cela passe
	return '';
}
