<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Construit une déclaration de champ extra "simple" (saisie input, valeur
 * par défaut vide, visible de tous), commune à la plupart des champs de
 * thematique_declarer_champs_extras() : seuls le nom du champ, sa clé de
 * langue, son type SQL et les statuts auteur autorisés à le modifier
 * varient.
 *
 * @param string $nom Nom du champ (identique à la clé $champs[$table][$nom])
 * @param string $cle_lang Clé de langue du label (module thematique)
 * @param string $sql Type SQL de la colonne
 * @param string|array $modifier Statut(s) auteur autorisés à modifier (cf 'restrictions')
 * @return array
 */
function thematique_champ_extra_simple($nom, $cle_lang, $sql, $modifier = ['webmestre', '0minirezo']) {
	return [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => $nom,
			'label' => _T($cle_lang),
			'sql' => $sql,
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => $modifier],
			], //Seuls les statuts listés dans $modifier peuvent modifier
		],
	];
}

function thematique_declarer_champs_extras($champs = []) {
	// 'disable' => 'disable', volontairement désactivé (cf historique)
	$champs['spip_auteurs']['ent'] = thematique_champ_extra_simple(
		'ent',
		'thematique:champ_extra_ent',
		"varchar(255) NOT NULL DEFAULT ''",
		'webmestre'
	);
	$champs['spip_auteurs']['avatar'] = thematique_champ_extra_simple(
		'avatar',
		'thematique:champ_extra_avatar',
		"varchar(255) NOT NULL DEFAULT ''",
		'webmestre'
	);
	$champs['spip_auteurs']['ent_statut'] = thematique_champ_extra_simple(
		'ent_statut',
		'thematique:champ_extra_ent_statut',
		"varchar(255) NOT NULL DEFAULT ''"
	);

	$champs['spip_rubriques']['url_id_doc'] = thematique_champ_extra_simple(
		'url_id_doc',
		'thematique:champ_extra_url_id_doc',
		'text'
	);
	$champs['spip_rubriques']['id_rubrique_lien'] = thematique_champ_extra_simple(
		'id_rubrique_lien',
		'thematique:champ_extra_id_rubrique_lien',
		'text'
	);

	$champs['spip_articles']['x'] = thematique_champ_extra_simple('x', 'thematique:champ_extra_position_x', 'float');
	$champs['spip_articles']['y'] = thematique_champ_extra_simple('y', 'thematique:champ_extra_position_y', 'float');
	$champs['spip_articles']['id_consigne'] = thematique_champ_extra_simple(
		'id_consigne',
		'thematique:champ_extra_id_consigne',
		"int(5) NOT NULL DEFAULT '0'"
	);

	return $champs;
}
