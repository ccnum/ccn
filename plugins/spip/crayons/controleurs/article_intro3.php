<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// un controleur qui n'utilise que php et les inputs défauts
function controleurs_article_intro3_dist($regs) {
	[, $crayon_nom, $crayon_type, $champ, $id] = $regs;
	$valeur = valeur_colonne_table($crayon_type, ['descriptif', 'chapo', 'texte'], $id);
	if ($valeur === false) {
		return ["$crayon_type $id $champ: " . _U('crayons:pas_de_valeur'), 6];
	}

	$crayon = new Crayon('article-intro3-' . $id, $valeur, ['hauteurMini' => 234]);

	$contexte = [
			'descriptif' => [
				'type' => 'texte',
				'attrs' => [
					'style' => 'height:' . ceil($crayon->hauteur * 2 / 13) . 'px;' . 'width:' . $crayon->largeur . 'px;'
				]
			],
			'chapo' =>  [
				'type' => 'texte',
				'attrs' => [
					'style' => 'height:' . ceil($crayon->hauteur * 4 / 13) . 'px;' . 'width:' . $crayon->largeur . 'px;'
				]
			],
			'texte' =>  [
				'type' => 'texte',
				'attrs' => [
					'style' => 'height:' . ceil($crayon->hauteur * 4 / 13) . 'px;' . 'width:' . $crayon->largeur . 'px;'
				]
			]
	];
	$html = $crayon->formulaire($contexte);
	$status = null;
	return [$html, $status, $crayon];
}
