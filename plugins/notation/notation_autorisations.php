<?php

/**
 * Plugin Notation v3
 * par JEM (jean-marc.viglino@ign.fr) / b_b / Matthieu Marcillaud
 *
 * Copyright (c) depuis 2008
 * Logiciel libre distribue sous licence GNU/GPL.
 *
 */

/**
 * Fonction d'autorisation pour l'edition (insertion ou modif) d'une notation
 *
 * $opt recoit $objet et $id_objet au cas ou.
 *
 * @return bool true/false
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

// fonction pour le pipeline, n'a rien a effectuer
function notation_autoriser() {
}

function autoriser_notation_modifier_dist($faire, $type, $id, $qui, $opt) {
	include_spip('inc/config'); // lire_config
	// la config interdit de modifier la note ?
	if ($id and !lire_config('notation/change_note')) {
		return false;
	}

	// sinon est-on autorise a voter ?
	$acces = lire_config('notation/acces', 'all');

	if ($acces != 'all') {
		// tous visiteur
		if ($acces == 'ide' && $qui['statut'] == '') {
			return false;
		}
		// auteur
		if ($acces == 'aut' && !in_array($qui['statut'], ['0minirezo', '1comite'])) {
			return false;
		}
		// admin
		if ($acces == 'adm' && !$qui['statut'] == '0minirezo') {
			return false;
		}
	}
	return true;
}

/**
 * Autorisation pouvant être utilisée pour limiter la divulgation des  noms des personnes qui notent
 *
 * @param string $faire
 * @param string $type
 * @param int $id
 * @param array $qui
 * @param array $opt
 */
function autoriser_notation_administrer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

/**
 * Moderer les notes ?
 * -* modifier l'objet correspondant (si note attache a un objet)
 * -* droits par defaut sinon (admin complet pour moderation complete)
 * Enter description here ...
 * @param string $faire
 * @param string $type
 * @param int $id
 * @param array $qui
 * @param array $opt
 */
function autoriser_moderernote_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}
