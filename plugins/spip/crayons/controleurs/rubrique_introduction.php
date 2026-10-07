<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// un controleur php + html
// html == avec un modele, controleurs/rubrique_introduction.html)
function controleurs_rubrique_introduction_dist($regs) {
	[, $crayon_nom, $crayon_type, $champ, $id] = $regs;
	$valeur = valeur_colonne_table($crayon_type, ['descriptif', 'texte'], $id);
	if ($valeur === false) {
		return ["$crayon_type $id $champ: " . _U('crayons:pas_de_valeur'), 6];
	}

	$crayon = new Crayon(
		'rubrique-introduction-' . $id,
		$valeur,
		['hauteurMini' => 234, 'controleur' => 'controleurs/rubrique_introduction']
	);

	$contexte = [
		'h_descriptif' => (int)ceil($crayon->hauteur * 5 / 13),
		'h_texte' => (int)ceil($crayon->hauteur * 7 / 13)
	];
	$html = $crayon->formulaire($contexte);
	$status = null;

	return [$html, $status, $crayon];
}
