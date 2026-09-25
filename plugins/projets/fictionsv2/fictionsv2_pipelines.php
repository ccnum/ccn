<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function fictionsv2_post_edition($flux) {
	if ($flux['args']['action'] !== 'modifier' || isset($flux['args']['data'])) {
		return $flux;
	}

	if (($flux['args']['objet'] ?? '') !== 'article') {
		return $flux;
	}

	$id_objet   = intval($flux['args']['id_objet'] ?? 0);
	$statut     = $flux['args']['champs_anciens']['statut'] ?? '';
	// #219 : $flux['data'] porte les valeurs tout juste enregistrées (post_edition),
	// contrairement à champs_anciens qui est l'état AVANT cette modification. Sur le
	// tout premier enregistrement d'un chapitre, champs_anciens['descriptif'] est
	// encore vide : lire uniquement l'ancienne valeur faisait sortir cette fonction
	// avant la cascade prop->publie, laissant le chapitre précédent visible en entier
	// jusqu'à un enregistrement ultérieur (masquage manuel en attendant).
	$descriptif = $flux['data']['descriptif'] ?? ($flux['args']['champs_anciens']['descriptif'] ?? '');
	$id_rubrique = intval($flux['args']['champs_anciens']['id_rubrique'] ?? 0);

	if (!$id_objet || !$statut || $descriptif === '' || !$id_rubrique) {
		return $flux;
	}

	include_spip('action/editer_objet');
	include_spip('fictionsv2/fictionsv2_fonctions');

	$blog = fictionsv2_id_rubrique_a_mot('blog_pedagogique');
	// #229 : la rubrique blog auteur suit la même mécanique de publication automatique
	// que le blog pédagogique, une fois la rubrique créée et la constante surchargée.
	$blogs_ids = array_filter([(int) $blog, _FICTIONSV2_ID_BLOG_AUTEUR]);

	if ($statut === 'prop') {
		// Publier l'article que l'on vient de modifier
		objet_modifier('article', $id_objet, ['statut' => 'publie']);
		// Masquer le chapitre précédent en tronquant son descriptif (#219)
		$prev_id = sql_getfetsel('id_article', 'spip_articles', [
			'id_rubrique=' . intval($id_rubrique),
			'statut=' . sql_quote('publie'),
			'id_article<>' . intval($id_objet),
		], '', 'id_article', '1', 'id_article DESC');
		if ($prev_id) {
			$prev_desc = sql_getfetsel('descriptif', 'spip_articles', 'id_article=' . intval($prev_id));
			objet_modifier('article', intval($prev_id), ['descriptif' => masquerTexteChapitre($prev_desc)]);
		}
		// Passer de prépa à prop le suivant
		$id_article = sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=' . $id_rubrique, 'statut=' . sql_quote('prepa')], '', 'id_article', '0,1');
		if ($id_article) {
			objet_modifier('article', intval($id_article), ['statut' => 'prop']);
		}
	}

	if (in_array($id_rubrique, $blogs_ids, true) && $statut === 'prepa') {
		objet_modifier('article', $id_objet, ['statut' => 'publie']);
		// Masquer le chapitre précédent en tronquant son descriptif (#219)
		$prev_id = sql_getfetsel('id_article', 'spip_articles', [
			'id_rubrique=' . intval($id_rubrique),
			'statut=' . sql_quote('publie'),
			'id_article<>' . intval($id_objet),
		], '', 'id_article', '1', 'id_article DESC');
		if ($prev_id) {
			$prev_desc = sql_getfetsel('descriptif', 'spip_articles', 'id_article=' . intval($prev_id));
			objet_modifier('article', intval($prev_id), ['descriptif' => masquerTexteChapitre($prev_desc)]);
		}
	}

	return $flux;
}