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

/**
 * Connexion SSO : un prof inscrit au projet de l'année (groupe libre ENT "Fictions
 * <année>") crée sa rubrique d'histoire à sa première connexion (cf inc/fictionsv2_cioidc.php).
 * Année calendaire réelle, pas _ANNEE_SCOLAIRE qui suit le cookie du sélecteur d'année.
 */
function fictionsv2_cioidc_userinfo($flux) {
	include_spip('inc/fictionsv2_cioidc');

	$uid = (string) (reset($flux['args']) ?: '');
	$profils = (string) ($flux['data']['ENTPersonProfils [ENS|TUT|ELV]'] ?? '');
	if (!$uid || strpos($profils, 'ENS') === false) {
		return $flux;
	}

	$annee = intval(_ANNEE_ACTUELLE_CALCULEE);
	$groupes_libres = fictionsv2_cioidc_normaliser_liste($flux['data']['ENTGroupesLibres'] ?? []);
	if (!fictionsv2_cioidc_est_inscrit($groupes_libres, $GLOBALS['meta']['nom_site'] ?? '', $annee)) {
		spip_log("fictionsv2 uid=$uid non inscrit au projet $annee, aucune histoire créée", 'cioidc');
		return $flux;
	}

	fictionsv2_cioidc_histoire_prof($uid, $annee);
	return $flux;
}

/**
 * À partir de _FICTIONSV2_ANNEE_CHAPITRE1_COMMUN, le chapitre 1 publié n'est plus dans
 * l'histoire (article "chapitre1" commun de l'année) : une histoire dont le chapitre 2
 * est encore en cours d'écriture (prop) n'a aucun article publié, et SPIP la dépublierait,
 * la retirant des boucles RUBRIQUES du site. On la garde publiée tant qu'elle a un
 * chapitre publié ou à écrire ; une histoire désactivée (tout en prepa) reste masquée.
 */
function fictionsv2_calculer_rubriques($flux) {
	$annees = sql_allfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre REGEXP ' . sql_quote('^[0-9]{4}$') . ' AND titre>=' . sql_quote((string) _FICTIONSV2_ANNEE_CHAPITRE1_COMMUN)
	);
	if (!$annees) {
		return $flux;
	}

	$histoires = sql_allfetsel(
		'R.id_rubrique AS id, max(A.date) AS date_h',
		'spip_rubriques AS R JOIN spip_articles AS A ON R.id_rubrique=A.id_rubrique',
		[sql_in('R.id_parent', array_column($annees, 'id_rubrique')), sql_in('A.statut', ['publie', 'prop'])],
		'R.id_rubrique'
	);
	foreach ($histoires as $row) {
		sql_update('spip_rubriques', [
			'statut_tmp' => sql_quote('publie'),
			'date_tmp' => 'GREATEST(date_tmp, ' . sql_quote($row['date_h']) . ')',
		], 'id_rubrique=' . intval($row['id']));
	}
	return $flux;
}
