<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Création automatique des histoires à la connexion SSO (CIOIDC / ENT laclasse.com),
 * appelée depuis fictionsv2_cioidc_userinfo() dans fictionsv2_pipelines.php.
 *
 * Chaque prof inscrit au projet de l'année (groupe libre ENT "Fictions <année>") crée à
 * sa première connexion la rubrique d'histoire suivante sous la rubrique de l'année :
 * "01. Histoire 01" pour le premier, "02. Histoire 02" pour le suivant, etc.
 *
 * Le prof n'est PAS rattaché à cette rubrique (cadavre exquis : les classes tournent
 * d'une histoire à l'autre, rattachement géré à la main). On mémorise donc qui a créé
 * quelle histoire dans la meta fictionsv2_histoires_createurs ([annee => [uid =>
 * id_rubrique]]), pour ne pas en recréer une à chaque connexion. Clé = uid ENT et non
 * id_auteur : ce pipeline passe avant cioidc_session(), qui ne crée le compte SPIP
 * qu'ensuite lors de la toute première connexion.
 **/

include_spip('fictionsv2_fonctions');

// Normalise un attribut multivalué : arrive en objet unique (pas en tableau) quand il
// n'y a qu'une seule valeur.
function fictionsv2_cioidc_normaliser_liste($valeur) {
	if (is_object($valeur)) {
		return [$valeur];
	}
	return is_array($valeur) ? $valeur : [];
}

// Minuscules sans accents, espaces ni ponctuation : "Fictions 2026" => "fictions2026".
function fictionsv2_cioidc_normaliser_nom(string $nom) {
	include_spip('inc/charsets');
	return preg_replace('/[^a-z0-9]/', '', strtolower(translitteration($nom)));
}

// Inscrit au projet de CE site pour l'année : un groupe libre dont le nom COMMENCE par
// "<site> <année>" (ex: "Fictions 2026" pour nom_site "fictions.laclasse.com"), même
// règle que thematique_cioidc_groupes_libres_pertinents().
function fictionsv2_cioidc_est_inscrit(array $groupes_libres, string $nom_site, int $annee) {
	$site = fictionsv2_cioidc_normaliser_nom(explode('.', $nom_site)[0]);
	if (!$site) {
		return false;
	}
	$prefixe = $site . $annee;
	foreach ($groupes_libres as $groupe) {
		if (str_starts_with(fictionsv2_cioidc_normaliser_nom($groupe->name ?? ''), $prefixe)) {
			return true;
		}
	}
	return false;
}

// Numéro de la prochaine histoire : plus grand numéro en tête de titre ("07. Histoire
// 07") parmi les rubriques de l'année, + 1. Les autres rubriques (blog pédagogique...)
// n'ont pas de numéro et sont ignorées.
function fictionsv2_cioidc_prochain_numero(int $id_annee) {
	$max = 0;
	foreach (sql_allfetsel('titre', 'spip_rubriques', 'id_parent=' . $id_annee) as $row) {
		if (preg_match('/^(\d+)\./', $row['titre'], $m)) {
			$max = max($max, intval($m[1]));
		}
	}
	return $max + 1;
}

// Crée "NN. Histoire NN" et ses chapitres 2 à 5 vides, sur le modèle de la rubrique
// "modele" : le chapitre 2 en prop (à écrire, cf cascade prop->publie de
// fictionsv2_post_edition()), les suivants en prepa. Le chapitre 1 n'est pas créé :
// c'est l'article "chapitre1" commun de l'année (cf fictionsv2_id_chapitre1()).
function fictionsv2_cioidc_creer_histoire(int $id_annee, int $numero) {
	include_spip('action/editer_objet');
	$num = sprintf('%02d', $numero);
	$id_rubrique = objet_inserer('rubrique', $id_annee, ['titre' => _T('fictionsv2:titre_histoire_numero', ['num' => $num])]);
	if (!$id_rubrique) {
		return 0;
	}
	for ($chapitre = 2; $chapitre <= 5; $chapitre++) {
		objet_inserer('article', $id_rubrique, [
			'titre' => $chapitre . '/ ' . _T('fictionsv2:titre_chapitre_defaut'),
			'statut' => $chapitre === 2 ? 'prop' : 'prepa',
		]);
	}
	// Publiée d'emblée (aucun article publié avant l'écriture du chapitre 2), statut
	// maintenu ensuite par fictionsv2_calculer_rubriques().
	sql_updateq('spip_rubriques', ['statut' => 'publie', 'date' => date('Y-m-d H:i:s')], 'id_rubrique=' . intval($id_rubrique));
	return (int) $id_rubrique;
}

// Crée l'histoire du prof $uid pour l'année $annee s'il n'en a pas déjà une.
// Verrou MySQL : deux profs se connectant en même temps prendraient sinon le même numéro.
function fictionsv2_cioidc_histoire_prof(string $uid, int $annee) {
	$id_annee = fictionsv2_id_rubrique_annee($annee);
	if (!$id_annee) {
		spip_log("fictionsv2 pas de rubrique d'année $annee, aucune histoire créée pour uid=$uid", 'cioidc');
		return 0;
	}

	$verrou = sql_quote('fictionsv2_histoire_' . $id_annee);
	sql_query("SELECT GET_LOCK($verrou, 10)");

	include_spip('inc/meta');
	lire_metas();
	$createurs = @unserialize($GLOBALS['meta']['fictionsv2_histoires_createurs'] ?? '') ?: [];
	$id_rubrique = intval($createurs[$annee][$uid] ?? 0);

	if (!$id_rubrique || !sql_countsel('spip_rubriques', 'id_rubrique=' . $id_rubrique . ' AND id_parent=' . $id_annee)) {
		$numero = fictionsv2_cioidc_prochain_numero($id_annee);
		$id_rubrique = fictionsv2_cioidc_creer_histoire($id_annee, $numero);
		if ($id_rubrique) {
			$createurs[$annee][$uid] = $id_rubrique;
			ecrire_meta('fictionsv2_histoires_createurs', serialize($createurs), 'non');
			spip_log("fictionsv2 histoire $numero créée (id_rubrique=$id_rubrique) pour uid=$uid année=$annee", 'cioidc');
		}
	}

	sql_query("SELECT RELEASE_LOCK($verrou)");
	return $id_rubrique;
}
