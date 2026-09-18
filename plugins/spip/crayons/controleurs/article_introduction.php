<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// un controleur php + html
// html == avec un modele, controleurs/article_introduction.html)
function controleurs_article_introduction_dist($regs) {
	[, $crayon, $type, $champ, $id] = $regs;
	$valeur = valeur_colonne_table($type, ['descriptif', 'chapo', 'texte'], $id);
	if ($valeur === false) {
		return ["$type $id $champ: " . _U('crayons:pas_de_valeur'), 6];
	}

	$n = new Crayon('article-introduction-' . $id, $valeur, ['hauteurMini' => 234, 'controleur' => 'controleurs/article_introduction']);

	$contexte = [
		'h_descriptif' => (int)ceil($n->hauteur * 2 / 13),
		'h_chapo' => (int)ceil($n->hauteur * 4 / 13),
		'h_texte' => (int)ceil($n->hauteur * 4 / 13)];
	$html = $n->formulaire($contexte);
	$status = null;

	return [$html, $status];
}
