<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Filtre de squelette : enveloppe de `yaml_decode_file()`.
 *
 * @api
 * @param string|false $fichier
 * @param array $options
 * @return mixed
 */
function decoder_yaml($fichier, $options = []) {
	include_spip('inc/yaml');
	return yaml_decode_file($fichier, $options);
}

/**
 * Point d'entrée retenu par `charger_fonction('yaml_to_array', 'inc')` dans une page servie, où le cache
 * des fonctions de plugins est chargé. Simple adaptateur : toute la logique est dans `yaml_to_array()`.
 *
 * ⚠️ **Cette fonction est un contournement, à retirer le jour où le noyau abandonnera sa `_dist`.**
 * `charger_fonction()` retient d'abord le nom **sans** `_dist`, qui est par convention la place de la
 * *surcharge*, puis celui **avec**, qui est celle du *défaut*. En occupant le nom sans `_dist`, le plugin
 * prend la place réservée aux autres : plus personne ne peut surcharger son décodage. Il ne le fait que
 * parce que le noyau déclare `inc_yaml_to_array_dist()` dans `ecrire/iterateur/data.php` — que l'itérateur
 * `DATA` charge lui-même —, et que cette fonction du noyau **lève une exception**, son `inc/yaml-mini`
 * ayant disparu de Textwheel en 2021.
 *
 * Le jour où le noyau la retirera, c'est `inc/yaml_to_array.php` du plugin qui devient le défaut légitime,
 * et ce fichier-ci doit perdre cette fonction pour rendre le nom sans `_dist` à qui veut surcharger.
 *
 * @internal
 * @param string $u
 * @return array
 */
function inc_yaml_to_array($u) {
	include_spip('inc/yaml');

	return yaml_to_array($u);
}
