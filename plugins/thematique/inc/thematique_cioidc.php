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
	$champs = 'id_auteur,nom,statut,email,webmestre,avatar';
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

// Rôle ENT affiché (Enseignant/Tuteur/Élève), remplacé par "Admin" pour un webmestre :
// affiché ci-dessous à la suite du nom plutôt que le rôle ENT d'origine.
function thematique_cioidc_role_affiche(string $profils, bool $is_webmestre) {
	if ($is_webmestre) {
		return 'Admin';
	}
	$roles_ent = ['ENS' => 'Enseignant', 'TUT' => 'Tuteur', 'ELV' => _T('thematique:cioidc_role_eleve')];
	foreach ($roles_ent as $code => $libelle) {
		if (strpos($profils, $code) !== false) {
			return $libelle;
		}
	}
	return null;
}

// Nom affiché : prénom/nom ENT, suivi du rôle ENT et de la classe (première classe
// réelle du prof) pour distinguer dans la liste des auteurs un même prof intervenant
// sur plusieurs CCN (cf issue #44). Le group_name reçu de l'ENT est préfixé par
// "CCN - " : préfixe redondant qu'on retire.
function thematique_cioidc_nom_affiche(array $flux_data, array $classes_reelles, ?string $role_ent) {
	$prenom = $flux_data['LaclassePrenom'] ?? '';
	$nom_famille = $flux_data['LaclasseNom'] ?? '';
	$nom = trim($prenom . ' ' . $nom_famille);
	if ($nom && $role_ent) {
		$nom .= ' - ' . $role_ent;
	}
	if ($nom && ($groupe_classe = $classes_reelles[0]->group_name ?? null)) {
		if (stripos($groupe_classe, 'CCN - ') === 0) {
			$groupe_classe = substr($groupe_classe, strlen('CCN - '));
		}
		$nom .= ' - ' . $groupe_classe;
	}
	return $nom;
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
