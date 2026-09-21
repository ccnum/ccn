<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// La garde est indispensable tant que le noyau déclare lui aussi `inc_yaml_to_array_dist()`, dans
// `ecrire/iterateur/data.php` : sans elle, inclure ce fichier alors que l'itérateur `DATA` est déjà
// chargé est une erreur fatale de redéclaration. Le jour où le noyau retirera la sienne, cette fonction
// deviendra le seul fournisseur du format et la garde sera sans objet, mais inoffensive.
if (!function_exists('inc_yaml_to_array_dist')) {
	/**
	 * Point d'entrée retenu par `charger_fonction('yaml_to_array', 'inc')` hors d'une page servie — en
	 * CLI par exemple. Simple adaptateur : toute la logique est dans `yaml_to_array()`.
	 *
	 * @internal
	 * @param string $u
	 * @return array
	 */
	function inc_yaml_to_array_dist($u) {
		include_spip('inc/yaml');

		return yaml_to_array($u);
	}
}
