<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function duplicator_autoriser(){}

function autoriser_dupliquer_dist($faire, $quoi='', $id=0, $qui=null, $options=null) {
	include_spip('inc/config');

	// S'il y a une autorisation explicite dans la configuration, on l'utilise
	if ($autorisation = lire_config("duplicator/$quoi/autorisation")) {
		if ($autorisation == 'webmestre') {
			return autoriser('webmestre', $quoi, $id, $qui, $options);
		}
		elseif ($autorisation == 'administrateur') {
			return $qui['statut'] <= '0minirezo' and !$qui['restreint'];
		}
		elseif ($autorisation == 'redacteur') {
			return $qui['statut'] <= '1comite';
		}
	}
	// Sinon on cherche une autorisation logique par défaut, de création ou de création dans un parent
	else {
		include_spip('base/objets');

		// Si on trouve un parent pour ce type d'objet
		if ($parent = objet_lister_parents($quoi, $id, true)) {//Ne prendre que le parent direct
			$parent = $parent[0];
			// On construit le nom de la fonction
			return autoriser("creer{$quoi}dans", $parent['objet'], $parent['id_objet']);
		}
		// Sinon c'est juste la création tout court
		else {
			return autoriser('creer', $quoi);
		}
	}

	return false;
}
