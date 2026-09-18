<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// un controleur qui n'utilise que php et les inputs défauts
function controleurs_article_intro3_dist($regs) {
	[, $crayon, $type, $champ, $id] = $regs;
	$valeur = valeur_colonne_table($type, ['descriptif', 'chapo', 'texte'], $id);
	if ($valeur === false) {
		return ["$type $id $champ: " . _U('crayons:pas_de_valeur'), 6];
	}

	$n = new Crayon('article-intro3-' . $id, $valeur, ['hauteurMini' => 234]);

	return [
		// html
		$n->formulaire(
			// champs et attributs propres
			[
			'descriptif' => ['type' => 'texte', 'attrs' => [
				'style' => 'height:' . ceil($n->hauteur * 2 / 13) . 'px;' .
							'width:' . $n->largeur . 'px;']],
			'chapo' =>  ['type' => 'texte', 'attrs' => [
				'style' => 'height:' . ceil($n->hauteur * 4 / 13) . 'px;' .
							'width:' . $n->largeur . 'px;']],
			'texte' =>  ['type' => 'texte', 'attrs' => [
				'style' => 'height:' . ceil($n->hauteur * 4 / 13) . 'px;' .
							'width:' . $n->largeur . 'px;']]] //,
			// attributs communs :( marche pas pour style , pas 2 fois ?
			// array('style' => 'width:' . $n->largeur . 'px;')
		),
		// status
		null];
}
