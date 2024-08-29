<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration de la balise LISTER_VALEURS pour la saisie `choix_grille`
 * @param string $objet
 *     Type d'objet
 * @param string $colonne
 *     Nom de la colonne SQL
 * @param string $cles
 *     Valeurs enregistrées pour ce champ dans la bdd pour l'objet en cours
 * @return array|string vide
 *		Tableau de type 'Clé de ligne|Valeur de ligne' => 'Ligne en valeur humaine|Valeur de ligne en humain'.
 * Par ex 'Ligne1|Colonne1' => 'Ma première ligne|Ma première colonne'
 **/
function champs_extras_calculer_balise_LISTER_VALEURS_choix_grille($objet, $colonne, $cles) {
	$options = calculer_balise_CHAMP_EXTRA($objet, $colonne);
	if (isset($options['data_rows']) && isset($options['data_cols'])) {
		$data_rows = saisies_chaine2tableau($options['data_rows']);
		$data_cols = saisies_chaine2tableau($options['data_cols']);
		$retour = [];
		$valeurs = saisies_chaine2tableau($cles);
		foreach ($valeurs as $cle => $valeur) {
			if (is_array($valeur)) {
				$colonne_humaine = join(
					'|',
					array_map(
						function ($i) use ($data_cols) {
							return $data_cols[$i];
						},
						$valeur
					)
				);
				$retour["$cle|" . join('|', $valeur)] = $data_rows[$cle] . '|' . $colonne_humaine;
			} else {
				$retour["$cle|$valeur"] = $data_rows[$cle] . '|' . $data_cols[$valeur];
			}
		}
		return $retour;
	} else {
		return '';
	}
}
