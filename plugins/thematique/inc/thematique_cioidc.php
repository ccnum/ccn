<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonctions de résolution de compte et de droits pour le pipeline
 * cioidc_userinfo (SSO ENT), appelées depuis thematique_cioidc_userinfo()
 * dans thematique_pipelines.php.
 **/

// cioidc_session() résout le compte qui recevra réellement la session par login=uid
// (uid_champ_spip='login', cf. cioidc_verifier_identifiant()) : on doit chercher avec
// le même critère en priorité, sinon on met à jour un autre compte (ex: un doublon
// historique retrouvé par email) que celui qui sera effectivement connecté.
function thematique_cioidc_resoudre_auteur($uid, $email) {
	$champs = 'id_auteur,nom,nom_complet,statut,email,webmestre,avatar';
	$auteur = $uid ? sql_fetsel($champs, 'spip_auteurs', 'login=' . sql_quote($uid)) : null;
	if (!$auteur) {
		$auteur = sql_fetsel($champs, 'spip_auteurs', 'email=' . sql_quote($email));
	}
	return $auteur;
}

// Si $valeur diffère du champ actuel de l'auteur, met à jour spip_auteurs et logue le
// changement. Factorise le motif commun aux mises à jour d'email/nom/statut ci-dessous.
function thematique_cioidc_maj_champ(array $auteur, string $champ, $valeur, string $libelle_log) {
	if ($valeur && $valeur !== $auteur[$champ]) {
		spip_log('userinfo mise à jour ' . $libelle_log . ' : ' . $auteur[$champ] . ' => ' . $valeur, 'cioidc');
		sql_updateq('spip_auteurs', [$champ => $valeur], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur[$champ] = $valeur;
	}
	return $auteur;
}

// Normalise un attribut CAS multivalué : arrive en objet unique (pas en tableau)
// quand il n'y a qu'une seule valeur.
function thematique_cioidc_normaliser_liste($valeur) {
	if (is_object($valeur)) {
		return [$valeur];
	}
	return $valeur ?: [];
}

// ENTClassesGroupes (member_type=ENS, group_type=CLS) : la/les classe(s) réellement
// enseignée(s) par ce prof, utilisée pour affiner son nom affiché et pour le lien
// "Travail des classes". Absent chez un intervenant qui n'a pas de classe en charge,
// seulement un groupe projet (ENTGroupesLibres).
function thematique_cioidc_classes_reelles(array $classes_groupes) {
	return array_values(array_filter(
		$classes_groupes,
		fn ($groupe) => ($groupe->group_type ?? '') === 'CLS' && !empty($groupe->group_name)
	));
}

// Les comptes ENS rattachés à un établissement listé dans _THEMATIQUE_RNE_WEBMESTRES
// (ex: établissement pilote de l'équipe projet) restent administrateurs complets
// plutôt que rédacteurs, sans restriction de rubrique. ENTAllUai liste tous les UAI
// de rattachement (pas seulement le principal).
function thematique_cioidc_est_webmestre($uai_liste, bool $is_enseignant) {
	$uai_liste = array_map('strval', (array) thematique_cioidc_normaliser_liste($uai_liste));
	$rne_webmestres = array_filter(array_map('trim', explode(',', _THEMATIQUE_RNE_WEBMESTRES)));
	$is_webmestre = $is_enseignant && (bool) array_intersect($uai_liste, $rne_webmestres);
	spip_log(
		'userinfo ENTAllUai=' . implode(',', $uai_liste) . ' => webmestre:' . ($is_webmestre ? 'oui' : 'non'),
		'cioidc'
	);
	return $is_webmestre;
}

// Nom d'un établissement depuis son UAI (ex: "0440001A", cf ENTAllUai/
// _THEMATIQUE_RNE_WEBMESTRES), via l'API publique "Annuaire de l'éducation"
// (data.education.gouv.fr, sans clé — cf issue #44). Passe par
// recuperer_url_cache() (cache fichier SPIP, cf ecrire/inc/distant.php)
// plutôt qu'un appel direct à chaque connexion SSO : un nom d'établissement
// change quasiment jamais, un aller-retour réseau au login serait une
// dépendance externe synchrone inutile — et recuperer_url_cache() réutilise
// le dernier résultat en cache si l'API est temporairement indisponible,
// plutôt que de faire échouer la résolution (et donc perdre l'info) le temps
// d'un incident réseau.
function thematique_cioidc_nom_etablissement($uai) {
	$uai = trim((string) $uai);
	if (!preg_match('/^[0-9]{7}[A-Za-z]$/', $uai)) {
		return '';
	}

	include_spip('inc/distant');
	$url = 'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/records'
		. '?where=' . urlencode('identifiant_de_l_etablissement="' . $uai . '"')
		. '&select=nom_etablissement&limit=1';

	// 30 jours : cf commentaire ci-dessus, inutile de retaper l'API plus souvent.
	$reponse = recuperer_url_cache($url, ['delai_cache' => 30 * 86400]);
	if (!$reponse || empty($reponse['page'])) {
		spip_log("userinfo échec résolution établissement uai=$uai", 'cioidc' . _LOG_ERREUR);
		return '';
	}

	$donnees = json_decode($reponse['page'], true);
	$nom_etablissement = $donnees['results'][0]['nom_etablissement'] ?? '';
	spip_log("userinfo résolution établissement uai=$uai => " . ($nom_etablissement ?: '(introuvable)'), 'cioidc');
	return $nom_etablissement;
}

// Rôle ENT affiché (Enseignant/Intervenant/Tuteur/Élève), remplacé par "Admin" pour un
// webmestre : affiché ci-dessous à la suite du nom plutôt que le rôle ENT d'origine.
// L'ENT laclasse.com n'a pas de profil dédié "intervenant" : un intervenant est envoyé
// avec le même profil ENS qu'un vrai prof (cf issue signalée en session — un intervenant
// ressortait "Enseignant"). Seule la présence d'une classe réelle (ENTClassesGroupes,
// cf thematique_cioidc_classes_reelles()) distingue les deux : un ENS sans classe réelle
// (seulement un groupe projet ENTGroupesLibres) est un intervenant.
function thematique_cioidc_role_affiche(string $profils, bool $is_webmestre, bool $a_une_classe_reelle) {
	if ($is_webmestre) {
		return 'Admin';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $a_une_classe_reelle ? 'Enseignant' : _T('thematique:cioidc_role_intervenant');
	}
	if (strpos($profils, 'TUT') !== false) {
		return 'Tuteur';
	}
	if (strpos($profils, 'ELV') !== false) {
		return _T('thematique:cioidc_role_eleve');
	}
	return null;
}

// Prénom + nom réels de la personne (ex: "Intervenant CCN"), tels que fournis par
// l'ENT — à distinguer de thematique_cioidc_nom_affiche() qui construit le libellé
// rôle/classe/collège (cf #44). Utilisé là où on veut identifier la personne plutôt
// que sa fonction (menu haut). 'name' est déjà le prénom+nom concaténés côté ENT ;
// repli sur LaclassePrenom/LaclasseNom si jamais absent.
function thematique_cioidc_nom_complet(array $data) {
	if (!empty($data['name'])) {
		return trim((string) $data['name']);
	}
	return trim(($data['LaclassePrenom'] ?? '') . ' ' . ($data['LaclasseNom'] ?? ''));
}

// Nom affiché : rôle ENT, suivi de la classe (première classe réelle du prof) pour
// distinguer dans la liste des auteurs un même prof intervenant sur plusieurs CCN
// (cf issue #44), et enfin du nom de son établissement (résolu depuis le premier
// UAI de $uai_liste — cf thematique_cioidc_nom_etablissement()). Plus de
// prénom/nom de la personne : décidé finalement sur l'issue #44, seuls le rôle, la
// classe et le collège identifient l'auteur. Le group_name reçu de l'ENT est
// préfixé par "CCN - " : préfixe redondant qu'on retire.
function thematique_cioidc_nom_affiche(array $classes_reelles, ?string $role_ent, array $uai_liste = []) {
	$parties = [];
	if ($role_ent) {
		$parties[] = $role_ent;
	}
	if ($groupe_classe = $classes_reelles[0]->group_name ?? null) {
		if (stripos($groupe_classe, 'CCN - ') === 0) {
			$groupe_classe = substr($groupe_classe, strlen('CCN - '));
		}
		$parties[] = $groupe_classe;
	}
	if (($uai = (string) ($uai_liste[0] ?? ''))
		&& ($nom_etablissement = thematique_cioidc_nom_etablissement($uai))) {
		$parties[] = $nom_etablissement;
	}
	return implode(' - ', $parties);
}

// Résout le secteur de l'année scolaire en cours (ex: "2025"), et sous ce secteur les
// rubriques "Travail des classes" (rôle prof) et "Consignes" (rôle intervenant).
function thematique_cioidc_rubriques_annee(string $annee_scolaire) {
	$id_secteur = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);
	spip_log('userinfo annee_scolaire=' . $annee_scolaire . ' id_secteur=' . $id_secteur, 'cioidc');

	$id_travail_classes = null;
	$id_consignes = null;
	if ($id_secteur) {
		$id_travail_classes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Travail des classes%') . ' AND id_secteur=' . intval($id_secteur)
		);
		$id_consignes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Consignes%') . ' AND id_secteur=' . intval($id_secteur)
		);
	}
	spip_log('userinfo id_travail_classes=' . $id_travail_classes . ' id_consignes=' . $id_consignes, 'cioidc');

	return [$id_secteur, $id_travail_classes, $id_consignes];
}

// ENTGroupesLibres : le groupe projet (ex: nom de l'intervenant/du binôme) → lien
// "Consignes". Un même compte ENT peut porter des groupes de plusieurs
// thématiques/années ("Textile 2023", "On tourne 2025", ...) : seuls ceux de la
// thématique de CE site (meta nom_site) et de l'année scolaire en cours sont
// pertinents ici.
function thematique_cioidc_groupes_libres_pertinents(array $groupes_libres, string $nom_site, string $annee_scolaire) {
	if (!$nom_site) {
		return [];
	}
	$pertinents = [];
	foreach ($groupes_libres as $groupe) {
		$nom_groupe = $groupe->name ?? '';
		if (
			$nom_groupe
			&& stripos($nom_groupe, $nom_site) !== false
			&& strpos($nom_groupe, $annee_scolaire) !== false
		) {
			$pertinents[] = $groupe;
		}
	}
	return $pertinents;
}

// Statut SPIP selon le rôle ENT. Un enseignant sans classe réelle ni groupe projet
// pertinent pour ce site/cette année reste simple visiteur ('6forum') : pas de droit
// de rédaction tant qu'il n'est pas effectivement affecté à quelque chose ici.
function thematique_cioidc_calculer_statut(bool $is_webmestre, string $profils, bool $a_un_groupe_pertinent) {
	if ($is_webmestre) {
		return '0minirezo';
	}
	if (strpos($profils, 'ELV') !== false) {
		return '6forum';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $a_un_groupe_pertinent ? '1comite' : '6forum';
	}
	return null;
}

// Sur un CCN archivé (_CCN_PROJET_ACTIVE=false), seuls les comptes webmestre peuvent
// se connecter via l'ENT/OIDC. Vérifié seulement après la promotion webmestre (dans
// l'appelant) : un webmestre qui se connecte pour la première fois sur ce site précis
// n'a pas encore 'oui' en base avant ce point (chaque CCN a sa propre table
// spip_auteurs), il serait bloqué à tort si on testait plus tôt. cioidc_session()
// (plugins/cioidc/inc/cioidc_session.php) ouvre la session juste après ce pipeline :
// rediriger ici empêche la session de s'ouvrir, sans avoir à toucher au plugin tiers
// cioidc.
function thematique_cioidc_bloquer_si_archive(array $auteur) {
	if (
		defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE
		&& ($auteur['webmestre'] ?? 'non') !== 'oui'
	) {
		spip_log('userinfo connexion refusée (CCN archivé, non-webmestre) id=' . $auteur['id_auteur'], 'cioidc');
		include_spip('inc/headers');
		redirige_par_entete(generer_url_public('cioidc_erreur_archive'));
	}
}

// Rubriques de classe/projet à lier à l'auteur : rôle prof (classes réelles + groupes
// projet pertinents) ou rôle élève (même classe que son prof, même recherche/création,
// pour rattacher l'élève à sa classe à son tour, ex: affichage de l'emoji de classe
// hors du contexte d'une rubrique).
function thematique_cioidc_resoudre_liens_rubriques(
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_reelles,
	array $groupes_libres_pertinents,
	$id_travail_classes,
	$id_consignes
) {
	$classes_a_lier = [];
	$projets_a_lier = [];

	if ($is_enseignant && !$is_webmestre) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
		foreach ($groupes_libres_pertinents as $groupe) {
			if ($id_projet = thematique_trouver_ou_creer_rubrique($groupe->name ?? '', $id_consignes)) {
				$projets_a_lier[] = $id_projet;
			}
		}
	} elseif ($is_eleve) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
	}

	return [$classes_a_lier, $projets_a_lier];
}

// Associe effectivement l'auteur aux rubriques résolues ci-dessus (+ le blog
// pédagogique commun à tous les profs).
function thematique_cioidc_associer_rubriques(
	array $auteur,
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_a_lier,
	array $projets_a_lier
) {
	if ($is_enseignant && !$is_webmestre) {
		$blog = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre = ' . sql_quote(_T('thematique:cioidc_blog_pedagogique'))
		);
		if ($blog) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
		}
		foreach ($projets_a_lier as $id_projet) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_projet]);
		}
	}
	if (($is_enseignant && !$is_webmestre) || $is_eleve) {
		foreach ($classes_a_lier as $id_classe) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_classe]);
		}
	}
}
