<?php

/**
 * API de vérification : vérification de la validité d'un identifiant de document
 *
 * @plugin     verifier
 * @copyright  2018
 * @author     Les Développements Durables
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie que la valeur correspond à un id_document valide
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_id_document_dist($valeur, $options = []) {
	$erreur = '';

	if ($valeur !== '') {
		// On vérifie déjà qu'il s'agit d'un nombre
		if (!is_numeric($valeur)) {
			$erreur = _T('verifier:erreur_id_document');
		} else {
			// construire la clause where
			$where = ['id_document=' . intval($valeur)];
			$erreur_details = label_ponctuer(_T('verifier:contraintes_particulieres')) . '<br/>';
			foreach (['media','extension'] as $w) {
				if (isset($options[$w])) {
					$where[] = sql_in_quote($w, is_string($options[$w]) ? explode(',', $options[$w]) : $options[$w]);
					$erreur_details .= "[ $w : ". (is_string($options[$w]) ? $options[$w] : implode(',', $options[$w])) . ' ] ';
				}
			}
			if (!sql_countsel('spip_documents',  $where)) {
				// un tel document n'existe pas
				$erreur = _T('verifier:erreur_id_document');
				// évoquer les contraintes supplémentaires si définies
				if (count($where) > 1) {
					$erreur .= '<br/>' . $erreur_details;
				}
			}
		}
	}

	return $erreur;
}
