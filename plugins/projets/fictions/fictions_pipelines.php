<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function fictions_post_edition($flux) {
	fictions_post_edition_rubrique_annee($flux);

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
	include_spip('fictions/fictions_fonctions');

	$blog = fictions_id_rubrique_a_mot('blog_pedagogique');
	// #229 : la rubrique blog auteur suit la même mécanique de publication automatique
	// que le blog pédagogique, une fois la rubrique créée et la constante surchargée.
	$blogs_ids = array_filter([(int) $blog, _FICTIONS_ID_BLOG_AUTEUR]);

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
 * Connexion SSO : trace l'inscription d'un prof au projet de l'année (groupe libre ENT
 * "Fictions <année>"). Ne crée plus d'histoire (#520) : une histoire par compte faussait
 * le compte (un enseignant peut gérer plusieurs classes, #519) ; les histoires se créent
 * depuis la liste des participants (ecrire/?exec=fictions_associations).
 * Année calendaire réelle, pas _ANNEE_SCOLAIRE qui suit le cookie du sélecteur d'année.
 */
function fictions_cioidc_userinfo($flux) {
	include_spip('inc/fictions_cioidc');

	// Mêmes traces que thematique_cioidc_userinfo() : tout ce que l'ENT envoie, puis
	// chaque décision, pour diagnostiquer une inscription non reconnue.
	spip_log('fictions userinfo args=' . json_encode($flux['args']) . ' data=' . json_encode($flux['data']), 'cioidc');

	$uid = (string) (reset($flux['args']) ?: '');
	$profils = (string) ($flux['data']['ENTPersonProfils [ENS|TUT|ELV]'] ?? '');
	$is_enseignant = strpos($profils, 'ENS') !== false;
	spip_log("fictions userinfo uid=$uid ENTPersonProfils=$profils => enseignant:" . ($is_enseignant ? 'oui' : 'non'), 'cioidc');
	if (!$uid || !$is_enseignant) {
		return $flux;
	}

	$annee = intval(_ANNEE_ACTUELLE_CALCULEE);
	$nom_site = $GLOBALS['meta']['nom_site'] ?? '';
	$groupes_libres = fictions_cioidc_normaliser_liste($flux['data']['ENTGroupesLibres'] ?? []);
	spip_log(
		"fictions userinfo uid=$uid groupes libres=" . json_encode(array_map(fn($g) => $g->name ?? '', $groupes_libres), JSON_UNESCAPED_UNICODE)
			. " préfixe attendu=" . fictions_cioidc_normaliser_nom(explode('.', $nom_site)[0]) . $annee,
		'cioidc'
	);
	if (!fictions_cioidc_est_inscrit($groupes_libres, $nom_site, $annee)) {
		spip_log("fictions uid=$uid non inscrit au projet $annee", 'cioidc');
		return $flux;
	}
	spip_log("fictions uid=$uid inscrit au projet $annee (participants et histoires : ecrire/?exec=fictions_associations)", 'cioidc');
	return $flux;
}

/**
 * À partir de _FICTIONS_ANNEE_CHAPITRE1_COMMUN, le chapitre 1 publié n'est plus dans
 * l'histoire (article "chapitre1" commun de l'année) : une histoire dont le chapitre 2
 * est encore en cours d'écriture (prop) n'a aucun article publié, et SPIP la dépublierait,
 * la retirant des boucles RUBRIQUES du site. On la garde publiée tant qu'elle a un
 * chapitre publié ou à écrire ; une histoire désactivée (tout en prepa) reste masquée.
 */
function fictions_calculer_rubriques($flux) {
	$annees = sql_allfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre REGEXP ' . sql_quote('^[0-9]{4}$') . ' AND titre>=' . sql_quote((string) _FICTIONS_ANNEE_CHAPITRE1_COMMUN)
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

/**
 * Tâches de fond : création de la structure de l'année scolaire à la rentrée.
 */
function fictions_taches_generales_cron($taches_generales) {
	$taches_generales['fictions_rentree_annee'] = 86400;
	return $taches_generales;
}

/**
 * Une rubrique d'année ("2026") créée ou renommée à la main sous la rubrique des années
 * reçoit tout de suite ses articles (Présentation, Prologue, chapitre 1 commun, footer),
 * sans attendre la tâche de rentrée (cf fictions_assurer_structure_annee()).
 * Dans l'espace privé, une rubrique est insérée ("Nouvelle rubrique") puis titrée par
 * une modification : c'est ce passage du titre à une année qui déclenche.
 */
function fictions_post_edition_rubrique_annee($flux) {
	if (($flux['args']['objet'] ?? '') !== 'rubrique' || ($flux['args']['action'] ?? '') !== 'modifier') {
		return;
	}
	$titre = trim((string) ($flux['data']['titre'] ?? ''));
	if (!preg_match('/^\d{4}$/', $titre) || $titre === trim((string) ($flux['args']['champs_anciens']['titre'] ?? ''))) {
		return;
	}
	$id_rubrique = intval($flux['args']['id_objet'] ?? 0);
	include_spip('inc/fictions_rentree');
	if (!fictions_est_rubrique_des_annees(intval(sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . $id_rubrique)))) {
		return;
	}
	[, $ok] = fictions_assurer_structure_annee(intval($titre));
	spip_log("fictions rubrique d'année $titre (#$id_rubrique) titrée à la main : structure " . ($ok ? 'OK' : 'incomplète'), 'fictions');
}
