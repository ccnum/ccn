<?php

/**
 * Plugin Notation v3
 * par JEM (jean-marc.viglino@ign.fr) / b_b / Matthieu Marcillaud
 *
 * Copyright (c) depuis 2008
 * Logiciel libre distribue sous licence GNU/GPL.
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}

include_spip('inc/notation_balises');


/**
 * Un filtre pour afficher les notations dans les forums
 * @param string $texte
 * @return string
 * @see modeles/aut_critique.html
 *
 * Gestion des notations dans les forums
 * Supprime [notation]
 * Transforme les [+] et [-] en images
 *
 */
function notation_critique($texte) {

	static $balise_svg, $image_plus, $image_moins;
	if (is_null($balise_svg)) {
		$balise_svg = charger_filtre('balise_svg');
		$image_plus = $balise_svg(find_in_path('img/notation-plus.svg'), '+', '16x16');
		$image_plus = "<span class='notation-picto-plus'>$image_plus</span>";
		$image_moins = $balise_svg(find_in_path('img/notation-moins.svg'), '-', '16x16');
		$image_moins = "<span class='notation-picto-moins'>$image_moins</span>";
	}

	$texte = str_replace('[notation]', '', $texte);
	$texte = str_replace('[+]', $image_plus, $texte);
	$texte = str_replace('[-]', $image_moins, $texte);
	return $texte;
}
