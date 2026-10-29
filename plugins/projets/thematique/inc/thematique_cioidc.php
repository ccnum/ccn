<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonctions de résolution de compte et de droits pour le pipeline
 * cioidc_userinfo (SSO ENT), appelées depuis thematique_cioidc_userinfo()
 * dans thematique_pipelines.php.
 **/

/**
 * cioidc_session() résout le compte qui recevra réellement la session par login=uid
 * (uid_champ_spip='login', cf. cioidc_verifier_identifiant()) : on doit chercher avec
 * le même critère en priorité, sinon on met à jour un autre compte (ex: un doublon
 * historique retrouvé par email) que celui qui sera effectivement connecté.
 *
 * @param string $uid Identifiant ENT (login SPIP)
 * @param string $email Email de l'utilisateur ('' = pas de repli par email)
 * @return array|false|null Auteur (id_auteur, nom, statut…), false ou null si introuvable
 */
function thematique_cioidc_resoudre_auteur($uid, $email) {
	$champs = 'id_auteur,nom,nom_complet,statut,email,webmestre,avatar,ent_statut';
	$auteur = $uid ? sql_fetsel($champs, 'spip_auteurs', 'login=' . sql_quote($uid)) : null;
	// Repli par email jamais sur un email vide (il désignait le premier compte sans
	// email, ex. un compte que cioidc vient de créer, dont les droits étaient alors
	// écrasés — audit 2026-10) ni sur un compte à la poubelle.
	// Réservé aux comptes historiques (login absent ou pas au format ENT ABC12345) :
	// un compte déjà rattaché à un identifiant ENT appartient à quelqu'un d'autre,
	// puisque la recherche par login n'a rien donné. Et seulement si l'email désigne
	// un seul compte : sinon rien ne dit lequel est le bon. Dans les deux cas,
	// l'appelant crée un nouveau compte plutôt que d'en reprendre un.
	if (!$auteur && $email !== '') {
		$candidats = sql_allfetsel($champs . ',login', 'spip_auteurs', [
			'email=' . sql_quote($email),
			'statut<>' . sql_quote('5poubelle'),
		], '', '', '0,2');
		$auteur = (count($candidats) === 1 && !preg_match('/^[A-Z]{3}\d{5}$/i', (string) $candidats[0]['login']))
			? $candidats[0]
			: null;
	}
	return $auteur;
}

// Première connexion : cioidc appelle le pipeline cioidc_userinfo AVANT de créer le
// compte (cioidc_auth.php puis cioidc_session()), le pipeline ne trouvait donc rien et
// le compte n'avait ses nom/statut/rubriques qu'à la connexion suivante. On le crée ici
// avec les mêmes champs que cioidc (qui le retrouve ensuite par son login), comme le
// fait déjà fictions_cioidc_userinfo().
function thematique_cioidc_creer_auteur(string $uid, string $email, array $data) {
	$id_auteur = (int) sql_insertq('spip_auteurs', [
		'login' => $uid,
		'nom' => (string) (($data['name'] ?? '') ?: $uid),
		'email' => $email,
		'statut' => defined('_CIOIDC_CREER_AUTEUR') && _CIOIDC_CREER_AUTEUR ? _CIOIDC_CREER_AUTEUR : '6forum',
		'webmestre' => 'non',
		'pass' => '',
		'source' => 'oidc',
	]);
	spip_log('userinfo compte créé pour uid=' . $uid . ' (#' . $id_auteur . ')', 'cioidc');
	return $id_auteur ? thematique_cioidc_resoudre_auteur($uid, '') : null;
}

/**
 * Si $valeur diffère du champ actuel de l'auteur, met à jour spip_auteurs et logue le
 * changement. Factorise le motif commun aux mises à jour d'email/nom/statut ci-dessous.
 *
 * @param array $auteur Auteur tel que lu en base
 * @param string $champ Colonne de spip_auteurs
 * @param mixed $valeur Nouvelle valeur (ignorée si vide)
 * @param string $libelle_log Libellé pour le log
 * @return array Auteur à jour
 */
function thematique_cioidc_maj_champ(array $auteur, string $champ, $valeur, string $libelle_log) {
	if ($valeur && $valeur !== $auteur[$champ]) {
		spip_log('userinfo mise à jour ' . $libelle_log . ' : ' . $auteur[$champ] . ' => ' . $valeur, 'cioidc');
		sql_updateq('spip_auteurs', [$champ => $valeur], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur[$champ] = $valeur;
	}
	return $auteur;
}

/**
 * Normalise un attribut CAS multivalué : arrive en objet unique (pas en tableau)
 * quand il n'y a qu'une seule valeur.
 *
 * @param mixed $valeur Attribut ENT (tableau, objet unique ou vide)
 * @return array
 */
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

/**
 * Les comptes ENS rattachés à un établissement listé dans _THEMATIQUE_RNE_WEBMESTRES
 * (ex: établissement pilote de l'équipe projet) restent administrateurs complets
 * plutôt que rédacteurs, sans restriction de rubrique. ENTAllUai liste tous les UAI
 * de rattachement (pas seulement le principal).
 * Les intervenants sont rattachés à cette même structure avec le même profil ENS :
 * un membre du groupe libre _THEMATIQUE_GROUPE_INTERVENANTS n'est jamais webmestre
 * (sinon "Admin", sans lien vers son projet sous "Consignes").
 *
 * @param mixed $uai_liste UAI de rattachement (ENTAllUai, liste ou valeur unique)
 * @param bool $is_enseignant Profil enseignant
 * @param bool $is_intervenant_ccn Membre du groupe des intervenants
 * @return bool
 */
function thematique_cioidc_est_webmestre($uai_liste, bool $is_enseignant, bool $is_intervenant_ccn = false) {
	$uai_liste = array_map('strval', (array) thematique_cioidc_normaliser_liste($uai_liste));
	$rne_webmestres = array_filter(array_map('trim', explode(',', _THEMATIQUE_RNE_WEBMESTRES)));
	$is_webmestre = $is_enseignant && !$is_intervenant_ccn && (bool) array_intersect($uai_liste, $rne_webmestres);
	spip_log(
		'userinfo ENTAllUai=' . implode(',', $uai_liste)
			. ' intervenant_ccn:' . ($is_intervenant_ccn ? 'oui' : 'non')
			. ' => webmestre:' . ($is_webmestre ? 'oui' : 'non'),
		'cioidc'
	);
	return $is_webmestre;
}

// Membre du groupe libre ENT des intervenants CCN (cf _THEMATIQUE_GROUPE_INTERVENANTS),
// sauf uid listé dans _THEMATIQUE_UID_WEBMESTRES (admin aussi inscrit dans ce groupe).
function thematique_cioidc_est_intervenant_ccn(array $groupes_libres, string $uid = '') {
	$uid_webmestres = array_filter(array_map('trim', explode(',', _THEMATIQUE_UID_WEBMESTRES)));
	if ($uid !== '' && in_array($uid, $uid_webmestres, true)) {
		return false;
	}
	$nom_groupe = thematique_cioidc_normaliser_nom(_THEMATIQUE_GROUPE_INTERVENANTS);
	foreach ($groupes_libres as $groupe) {
		if ($nom_groupe !== '' && thematique_cioidc_normaliser_nom($groupe->name ?? '') === $nom_groupe) {
			return true;
		}
	}
	return false;
}

/**
 * Nom d'un établissement depuis son UAI (ex: "0440001A", cf ENTAllUai/
 * _THEMATIQUE_RNE_WEBMESTRES), via l'API publique "Annuaire de l'éducation"
 * (data.education.gouv.fr, sans clé). Passe par
 * recuperer_url_cache() (cache fichier SPIP, cf ecrire/inc/distant.php)
 * plutôt qu'un appel direct à chaque connexion SSO : un nom d'établissement
 * change quasiment jamais, un aller-retour réseau au login serait une
 * dépendance externe synchrone inutile — et recuperer_url_cache() réutilise
 * le dernier résultat en cache si l'API est temporairement indisponible,
 * plutôt que de faire échouer la résolution (et donc perdre l'info) le temps
 * d'un incident réseau.
 *
 * @param string $uai UAI de l'établissement
 * @return string Nom de l'établissement, '' si inconnu
 */
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
// avec le même profil ENS qu'un vrai prof. Il se distingue par son appartenance au
// groupe libre _THEMATIQUE_GROUPE_INTERVENANTS, sans classe réelle (cf
// thematique_cioidc_est_intervenant()) : un enseignant sans classe dans l'ENT mais
// hors de ce groupe (ex: classe absente de l'ENT) reste "Enseignant".
function thematique_cioidc_role_affiche(string $profils, bool $is_webmestre, bool $is_intervenant) {
	if ($is_webmestre) {
		return 'Admin';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $is_intervenant ? _T('thematique:cioidc_role_intervenant') : 'Enseignant';
	}
	if (strpos($profils, 'TUT') !== false) {
		return 'Tuteur';
	}
	if (strpos($profils, 'ELV') !== false) {
		return _T('thematique:cioidc_role_eleve');
	}
	return null;
}

// Intervenant : profil ENS, membre du groupe des intervenants CCN (cf
// thematique_cioidc_est_intervenant_ccn()), sans classe réelle ; un membre du groupe
// qui a une classe CLS dans l'ENT reste enseignant.
function thematique_cioidc_est_intervenant(
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_intervenant_ccn,
	array $classes_reelles
) {
	return $is_enseignant && !$is_webmestre && $is_intervenant_ccn && !$classes_reelles;
}

// Profil ENT résolu à la connexion, mémorisé dans spip_auteurs.ent_statut : seul
// moyen pour thematique_donner_role() de distinguer, hors connexion, un enseignant
// d'un intervenant tous deux liés à un projet sous "Consignes" (cf
// thematique_donner_role()). Vide pour un tuteur ou un profil inconnu.
function thematique_cioidc_profil_ent(string $profils, bool $is_webmestre, bool $is_intervenant) {
	if ($is_webmestre) {
		return 'admin';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $is_intervenant ? 'intervenant' : 'enseignant';
	}
	if (strpos($profils, 'ELV') !== false) {
		return 'eleve';
	}
	return '';
}

// Prénom + nom réels de la personne (ex: "Intervenant CCN"), tels que fournis par
// l'ENT — à distinguer de thematique_cioidc_nom_affiche() qui construit le libellé
// rôle/classe/collège. Utilisé là où on veut identifier la personne plutôt
// que sa fonction (menu haut). 'name' est déjà le prénom+nom concaténés côté ENT ;
// repli sur LaclassePrenom/LaclasseNom si jamais absent.
function thematique_cioidc_nom_complet(array $data) {
	if (!empty($data['name'])) {
		return trim((string) $data['name']);
	}
	return trim(($data['LaclassePrenom'] ?? '') . ' ' . ($data['LaclasseNom'] ?? ''));
}

// Nom affiché : rôle ENT, suivi de la classe (première classe réelle du prof) pour
// distinguer dans la liste des auteurs un même prof intervenant sur plusieurs CCN,
// et enfin du nom de son établissement (résolu depuis le premier
// UAI de $uai_liste — cf thematique_cioidc_nom_etablissement()). Plus de
// prénom/nom de la personne : seuls le rôle, la
// classe et le collège identifient l'auteur. Le group_name reçu de l'ENT est
// préfixé par "CCN - " : préfixe redondant qu'on retire.
// Exception : un intervenant n'a ni classe ni établissement (UAI 00000CCN non résolu)
// et ressortait "Intervenant" tout court, indistinguable d'un autre : $nom_personne
// (prénom+nom, cf thematique_cioidc_nom_complet()) est alors placé en tête.
function thematique_cioidc_nom_affiche(
	array $classes_reelles,
	?string $role_ent,
	array $uai_liste = [],
	string $nom_personne = ''
) {
	$parties = [];
	if ($nom_personne !== '') {
		$parties[] = $nom_personne;
	}
	if ($role_ent) {
		$parties[] = $role_ent;
	}
	if ($groupe_classe = $classes_reelles[0]->group_name ?? null) {
		if (stripos($groupe_classe, 'CCN - ') === 0) {
			$groupe_classe = substr($groupe_classe, strlen('CCN - '));
		}
		$parties[] = thematique_cioidc_nom_classe($groupe_classe);
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
	// nom_site peut être un nom de domaine (ex: "petitfablab.laclasse.com") : seul le
	// premier segment identifie la thématique.
	$site = thematique_cioidc_normaliser_nom(explode('.', $nom_site)[0]);
	if (!$site) {
		return [];
	}

	// Un "groupe libre" ENT est nommé librement par un
	// enseignant (pas administré par l'établissement). Ce rattachement donne
	// un vrai droit de création d'article dans la rubrique-projet
	// correspondante (cf autoriser_rubrique_creerarticledans,
	// thematique_autoriser.php) et de création de ses classes : on exige donc
	// que le nom du groupe COMMENCE par "<thématique> <année>" (ex: "On tourne
	// 2026"), pas qu'il contienne ces mots n'importe où ("Fictions 2026" ou
	// "CCN - Test 2026" ne comptent pas sur ontourne). Comparaison sur les noms
	// normalisés : nom_site vaut "ontourne" en prod quand le groupe ENT s'appelle
	// "On tourne 2026", un motif \bontourne\b ne reconnaissait aucun groupe.
	$prefixe = $site . thematique_cioidc_normaliser_nom($annee_scolaire);

	$pertinents = [];
	foreach ($groupes_libres as $groupe) {
		if (str_starts_with(thematique_cioidc_normaliser_nom($groupe->name ?? ''), $prefixe)) {
			$pertinents[] = $groupe;
		}
	}
	return $pertinents;
}

// Minuscules sans accents, espaces ni ponctuation : "On tourne 2026" => "ontourne2026".
function thematique_cioidc_normaliser_nom(string $nom) {
	include_spip('inc/charsets');
	return preg_replace('/[^a-z0-9]/', '', strtolower(translitteration($nom)));
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
// (plugins/spip/cioidc/inc/cioidc_session.php) ouvre la session juste après ce pipeline :
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

/**
 * Nom d'une classe ENT tel qu'affiché sur le site : niveau de collège suivi d'un
 * espace écrit en toutes lettres ("4 2" => "4ème 2", format dans l'item de langue
 * nom_classe_niveau), ou collé à une lettre ou un numéro court ("3A" => "3ème A").
 * Les autres formats ("5EME1", "3E1HI-GE"…) sont gardés tels quels.
 *
 * @param string $group_name Nom de la classe dans l'ENT
 * @return string
 */
function thematique_cioidc_nom_classe($group_name) {
	$group_name = trim((string) $group_name);
	if (preg_match('/^([3-6])(?:\s+(\S.*)|([A-Za-z]|\d{1,2}))$/u', $group_name, $m)) {
		$m[2] = ($m[2] ?? '') !== '' ? $m[2] : $m[3];
		return _T('thematique:nom_classe_niveau', ['niveau' => $m[1], 'classe' => $m[2]]);
	}
	return $group_name;
}

/**
 * Titre de la rubrique d'une classe ENT sous "Travail des classes" : nom de la classe
 * (cf thematique_cioidc_nom_classe()) suivi de son établissement (ex: "4ème 2 - Collège
 * Jean Jaurès"), pour distinguer deux classes homonymes de collèges différents
 * inscrits au même CCN — sans quoi elles partageaient la même rubrique.
 * L'établissement vient du group_structure_id de la classe (et non du premier UAI du
 * compte : un prof peut enseigner dans plusieurs établissements). Le "/" est remplacé
 * car creer_rubrique_nommee() y voit un séparateur d'arborescence.
 *
 * @param object $groupe Groupe ENT de la classe (group_name, group_structure_id)
 * @param bool $nom_brut Nom ENT brut ("4 2") au lieu du nom formaté (titres anciens)
 * @return string
 */
function thematique_cioidc_titre_rubrique_classe($groupe, $nom_brut = false) {
	$titre = $nom_brut ? (string) $groupe->group_name : thematique_cioidc_nom_classe($groupe->group_name);
	if ($nom_etablissement = thematique_cioidc_nom_etablissement($groupe->group_structure_id ?? '')) {
		$titre .= ' - ' . $nom_etablissement;
	}
	return str_replace('/', '-', $titre);
}

/**
 * Anciens titres possibles de la rubrique d'une classe ENT, différents du titre
 * actuel : nom ENT brut avec établissement ("4 2 - Collège…", avant le formatage du
 * nom), puis nom ENT brut seul (avant l'ajout de l'établissement).
 *
 * @param object $groupe Groupe ENT de la classe
 * @return string[]
 */
function thematique_cioidc_anciens_titres_classe($groupe) {
	return array_values(array_diff(
		array_unique([thematique_cioidc_titre_rubrique_classe($groupe, true), (string) $groupe->group_name]),
		[thematique_cioidc_titre_rubrique_classe($groupe)]
	));
}

/**
 * Titre de la rubrique « école » sous "Travail des classes" : le nom de
 * l'établissement seul (ex: "Ecole élémentaire Combe Blanche"). Elle remplace la
 * classe d'un prof inscrit au projet que l'ENT n'associe à aucune classe (cas
 * fréquent dans le premier degré, où l'affectation des enseignants aux classes ne
 * remonte pas des annuaires académiques) ; ses élèves, eux, ont bien leur classe
 * dans l'ENT et la rejoignent faute de rubrique pour cette classe. Deux profs sans
 * classe de la même école la partagent.
 *
 * @param string $uai UAI de l'établissement
 * @return string '' si l'établissement est inconnu
 */
function thematique_cioidc_titre_rubrique_ecole($uai) {
	return str_replace('/', '-', thematique_cioidc_nom_etablissement($uai));
}

/**
 * Rubrique école existante d'un élève (cf thematique_cioidc_titre_rubrique_ecole()),
 * d'après l'établissement de ses classes puis ses UAI de rattachement.
 *
 * @param array $classes_reelles Classes ENT de l'élève
 * @param array $uai_liste UAI de rattachement (ENTAllUai)
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 * @return int 0 si aucune
 */
function thematique_cioidc_rubrique_ecole_eleve(array $classes_reelles, array $uai_liste, $id_travail_classes) {
	$uais = array_merge(
		array_map(fn ($groupe) => (string) ($groupe->group_structure_id ?? ''), $classes_reelles),
		array_map('strval', $uai_liste)
	);
	foreach (array_unique(array_filter($uais)) as $uai) {
		$titre = thematique_cioidc_titre_rubrique_ecole($uai);
		if ($titre !== '' && ($id_ecole = thematique_trouver_rubrique($titre, $id_travail_classes, true))) {
			return intval($id_ecole);
		}
	}
	return 0;
}

/**
 * Rubrique d'une classe ENT encore sous un ancien titre (cf
 * thematique_cioidc_anciens_titres_classe()), renommée au titre actuel plutôt que
 * dupliquée, pour ne pas perdre son contenu ni ses liens auteurs.
 *
 * @param object $groupe Groupe ENT de la classe
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 * @return int 0 si aucune
 */
function thematique_cioidc_renommer_ancienne_classe($groupe, $id_travail_classes) {
	$titre = thematique_cioidc_titre_rubrique_classe($groupe);
	// Une rubrique porte déjà le titre actuel : ne pas en créer une homonyme.
	if (sql_countsel('spip_rubriques', 'titre = ' . sql_quote($titre) . ' AND id_parent=' . intval($id_travail_classes))) {
		return 0;
	}
	foreach (thematique_cioidc_anciens_titres_classe($groupe) as $ancien) {
		$id_ancienne = (int) sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre = ' . sql_quote($ancien) . ' AND id_parent=' . intval($id_travail_classes)
		);
		if ($id_ancienne) {
			sql_updateq('spip_rubriques', ['titre' => $titre], 'id_rubrique=' . $id_ancienne);
			spip_log("userinfo rubrique classe #$id_ancienne renommée $ancien => $titre", 'cioidc');
			return $id_ancienne;
		}
	}
	return 0;
}

/**
 * Rubrique d'une classe ENT pour un prof : trouvée ou créée sous son titre avec
 * établissement. Une rubrique encore sous un ancien titre est renommée plutôt que
 * dupliquée (cf thematique_cioidc_renommer_ancienne_classe()).
 *
 * @param object $groupe Groupe ENT de la classe
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 * @return int|string|null Rubrique de la classe, null sans rubrique parente
 */
function thematique_cioidc_rubrique_classe_prof($groupe, $id_travail_classes) {
	if (!$id_travail_classes) {
		return null;
	}
	$titre = thematique_cioidc_titre_rubrique_classe($groupe);
	if ($id_classe = thematique_trouver_rubrique($titre, $id_travail_classes)) {
		return $id_classe;
	}
	if ($id_ancienne = thematique_cioidc_renommer_ancienne_classe($groupe, $id_travail_classes)) {
		return $id_ancienne;
	}
	return thematique_trouver_ou_creer_rubrique($titre, $id_travail_classes);
}

// Classes ENT proposées au prof dans la pop-up de choix (cf
// formulaires/choisir_classes_ent.php), avec l'année scolaire de la connexion pour ne
// pas proposer à la rentrée la liste de l'année précédente. Vidé pour un compte qui
// n'a rien à choisir (élève, webmestre, prof non inscrit au projet cette année).
function thematique_cioidc_memoriser_classes_ent(array $auteur, array $classes_reelles, string $annee_scolaire): void {
	$valeur = '';
	if ($classes_reelles) {
		$valeur = json_encode([
			'annee' => $annee_scolaire,
			'classes' => array_map(fn ($groupe) => [
				'group_name' => (string) $groupe->group_name,
				'group_structure_id' => (string) ($groupe->group_structure_id ?? ''),
				'titre' => thematique_cioidc_titre_rubrique_classe($groupe),
			], $classes_reelles),
		], JSON_UNESCAPED_UNICODE);
	}
	// Lu à part (pas dans thematique_cioidc_resoudre_auteur()) : tant que la mise à
	// jour de schéma 3.4.5 n'est pas passée, la colonne manque et seul ce choix de
	// classes échoue, pas toute la connexion.
	$id_auteur = intval($auteur['id_auteur']);
	if ($valeur !== (string) sql_getfetsel('classes_ent', 'spip_auteurs', 'id_auteur=' . $id_auteur)) {
		spip_log('userinfo classes ENT mémorisées : ' . ($valeur ?: '(aucune)'), 'cioidc');
		sql_updateq('spip_auteurs', ['classes_ent' => $valeur], 'id_auteur=' . $id_auteur);
	}
}

// Rattache le prof aux classes ENT cochées dans la pop-up et note l'année du
// choix, ce qui masque la pop-up. $classes : entrées de
// thematique_classes_ent_a_choisir(). Renvoie les rubriques de classe liées, [] si
// aucune n'a pu l'être (rubrique "Travail des classes" de l'année introuvable).
function thematique_cioidc_valider_classes_ent(int $id_auteur, array $classes, string $annee): array {
	include_spip('thematique_fonctions');
	include_spip('action/editer_liens');

	[, $id_travail_classes] = thematique_cioidc_rubriques_annee($annee);
	if (!$id_travail_classes) {
		return [];
	}

	thematique_cioidc_renommer_classe_echangee($id_auteur, $classes, $id_travail_classes);

	// Même rubrique que celle que créait la connexion SSO avant la pop-up de choix des classes (titre avec
	// établissement, renommage d'une rubrique au titre ancien) : on retrouve celle
	// d'un collègue de la même classe plutôt que d'en créer une seconde.
	$choisies = [];
	foreach ($classes as $classe) {
		$groupe = (object) [
			'group_name' => $classe['group_name'],
			'group_structure_id' => $classe['group_structure_id'] ?? '',
		];
		if ($id_classe = thematique_cioidc_rubrique_classe_prof($groupe, $id_travail_classes)) {
			$choisies[] = intval($id_classe);
		}
	}
	if (!$choisies) {
		return [];
	}

	// Classes de l'année non retenues : liées automatiquement à la connexion avant la pop-up de choix
	// (dont d'anciennes classes du prof), retirées pour ne garder que son choix.
	foreach (thematique_classes_auteur($id_auteur) as $id_classe) {
		if (!in_array($id_classe, $choisies, true)) {
			objet_dissocier(['id_auteur' => $id_auteur], ['rubrique' => $id_classe]);
		}
	}
	foreach ($choisies as $id_classe) {
		objet_associer(['id_auteur' => $id_auteur], ['rubrique' => $id_classe]);
	}
	thematique_cioidc_supprimer_classes_non_choisies($id_auteur, $id_travail_classes, $choisies);
	sql_updateq('spip_auteurs', ['classes_ent_choisies' => $annee], 'id_auteur=' . $id_auteur);
	spip_log("choix des classes auteur #$id_auteur année $annee : " . implode(',', $choisies), 'cioidc');

	return $choisies;
}

/**
 * Prof qui corrige son choix en cours d'année (classe cochée trop vite, cf action
 * thematique_modifier_classes_ent) : s'il échange une seule classe A contre une
 * seule classe B pas encore créée, la rubrique de A est renommée en B plutôt que de
 * créer B vide, pour garder les réponses et documents déjà publiés et son lien.
 * Seulement si A fait partie de ses classes ENT et qu'aucun autre adulte n'y est
 * lié (un collègue y travaille vraiment).
 * Même chose pour un prof qui travaillait dans la rubrique de son école faute de
 * classe dans l'ENT (cf thematique_cioidc_titre_rubrique_ecole()) : quand sa classe
 * y est enfin créée, l'école est renommée en cette classe. Les élèves de A en sont détachés : ce
 * n'est plus leur classe, ceux de B s'y rattachent à leur prochaine connexion (par
 * le titre, cf thematique_cioidc_resoudre_liens_rubriques()).
 * Dans tous les autres cas (plusieurs classes changées, B existe déjà…), rien
 * n'est renommé : A est gardée si elle a du contenu (cf
 * thematique_cioidc_supprimer_classes_non_choisies()).
 *
 * @param int $id_auteur Prof concerné
 * @param array $classes Classes cochées (entrées de thematique_classes_ent_a_choisir())
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 */
function thematique_cioidc_renommer_classe_echangee(int $id_auteur, array $classes, $id_travail_classes): void {
	// Titres possibles (actuel et anciens) d'une classe ENT
	$titres = function (array $classe) {
		$groupe = (object) [
			'group_name' => $classe['group_name'],
			'group_structure_id' => $classe['group_structure_id'] ?? '',
		];
		return array_values(array_unique(array_merge(
			[$classe['titre'], thematique_cioidc_titre_rubrique_classe($groupe)],
			thematique_cioidc_anciens_titres_classe($groupe)
		)));
	};
	$rubrique_existante = fn (array $titres_classe) => (int) sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		[sql_in('titre', $titres_classe), 'id_parent=' . intval($id_travail_classes)]
	);

	// Classes cochées : celles qui existent déjà, et celles à créer
	$existantes = [];
	$a_creer = [];
	foreach ($classes as $classe) {
		if ($id = $rubrique_existante($titres($classe))) {
			$existantes[] = $id;
		} else {
			$a_creer[] = $classe;
		}
	}

	// Classes actuelles du prof non recochées, parmi ses classes ENT et leurs écoles
	$titres_ent = [];
	foreach (thematique_classes_ent_auteur($id_auteur)['classes'] as $classe) {
		$titres_ent = array_merge(
			$titres_ent,
			$titres($classe),
			array_filter([thematique_cioidc_titre_rubrique_ecole($classe['group_structure_id'] ?? '')])
		);
	}
	$retirees = [];
	foreach (thematique_classes_auteur($id_auteur) as $id_classe) {
		$titre = (string) sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique=' . intval($id_classe));
		if (!in_array($id_classe, $existantes, true) && in_array($titre, $titres_ent, true)) {
			$retirees[] = $id_classe;
		}
	}

	if (count($retirees) !== 1 || count($a_creer) !== 1) {
		return;
	}
	$id_ancienne = intval($retirees[0]);
	$autres_adultes = sql_countsel(
		'spip_auteurs_liens AS L JOIN spip_auteurs AS A ON A.id_auteur=L.id_auteur',
		["L.objet='rubrique'", 'L.id_objet=' . $id_ancienne, 'L.id_auteur<>' . $id_auteur, "A.statut<>'6forum'"]
	);
	if ($autres_adultes) {
		return;
	}

	$titre = $a_creer[0]['titre'];
	sql_updateq('spip_rubriques', ['titre' => $titre], 'id_rubrique=' . $id_ancienne);
	$eleves = array_column(sql_allfetsel(
		'L.id_auteur',
		'spip_auteurs_liens AS L JOIN spip_auteurs AS A ON A.id_auteur=L.id_auteur',
		["L.objet='rubrique'", 'L.id_objet=' . $id_ancienne, "A.statut='6forum'"]
	), 'id_auteur');
	foreach ($eleves as $id_eleve) {
		objet_dissocier(['id_auteur' => intval($id_eleve)], ['rubrique' => $id_ancienne]);
	}
	include_spip('inc/invalideur');
	suivre_invalideur('1');
	spip_log(
		"choix des classes auteur #$id_auteur : classe #$id_ancienne renommée en « $titre » (échange de classe), "
			. count($eleves) . ' élève(s) détaché(s)',
		'cioidc'
	);
}

/**
 * Supprime les rubriques des classes ENT de ce prof qu'il n'a pas retenues : créées
 * sous "Travail des classes" par la connexion SSO avant la pop-up de choix, elles
 * s'affichaient comme classes participantes. Seules les classes de SA liste ENT
 * (thematique_classes_ent_a_choisir(), lue avant que le choix de l'année soit noté)
 * sont candidates, retrouvées par titre exact (avec établissement, ou ancien titre
 * sans), et une rubrique n'est supprimée que si :
 * - aucun autre auteur que des élèves n'y est lié (un collègue l'a cochée, ou y a été
 *   rattaché à la connexion et ne l'a pas encore choisie) ;
 * - elle est vide (cf thematique_classe_compter_contenu()) : on ne perd jamais de contenu.
 *
 * @param int $id_auteur Prof concerné
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 * @param array $choisies Rubriques de classe cochées
 */
function thematique_cioidc_supprimer_classes_non_choisies(int $id_auteur, $id_travail_classes, array $choisies): void {
	include_spip('inc/thematique_classes_vides');

	$nb = 0;
	foreach (thematique_classes_ent_a_choisir($id_auteur) as $classe) {
		$groupe = (object) [
			'group_name' => $classe['group_name'],
			'group_structure_id' => $classe['group_structure_id'] ?? '',
		];
		$titres = array_unique(array_merge(
			[$classe['titre'], thematique_cioidc_titre_rubrique_classe($groupe)],
			thematique_cioidc_anciens_titres_classe($groupe)
		));
		$rubriques = sql_allfetsel('id_rubrique, titre', 'spip_rubriques', [
			sql_in('titre', $titres),
			'id_parent=' . intval($id_travail_classes),
		]);
		foreach ($rubriques as $rubrique) {
			$id_rubrique = intval($rubrique['id_rubrique']);
			if (in_array($id_rubrique, $choisies, true)) {
				continue;
			}
			$autres_auteurs = sql_countsel(
				'spip_auteurs_liens AS L JOIN spip_auteurs AS A ON A.id_auteur=L.id_auteur',
				["L.objet='rubrique'", 'L.id_objet=' . $id_rubrique, 'L.id_auteur<>' . $id_auteur, "A.statut<>'6forum'"]
			);
			if ($autres_auteurs || array_filter(thematique_classe_compter_contenu($id_rubrique))) {
				spip_log(
					"choix des classes auteur #$id_auteur : classe #$id_rubrique non retenue conservée (collègue ou contenu)",
					'cioidc'
				);
				continue;
			}
			thematique_supprimer_rubrique_classe($id_rubrique, $rubrique['titre']);
			$nb++;
		}
	}

	if ($nb) {
		thematique_classes_apres_suppression();
	}
}

/**
 * Rubriques de classe/projet à lier à l'auteur.
 * Un prof inscrit au projet de CE site pour l'année (groupe libre pertinent, cf
 * thematique_cioidc_groupes_libres_pertinents()) n'est lié ici qu'à ses projets : ses
 * classes ENT (y compris d'anciennes années, ENTClassesGroupes n'a pas d'année) sont
 * seulement mémorisées (thematique_cioidc_memoriser_classes_ent()), il coche dans une
 * pop-up celles inscrites au CCN, qui sont alors créées sous "Travail des classes"
 * (cf formulaires/choisir_classes_ent.php).
 * Un prof inscrit sans aucune classe dans l'ENT (hors intervenant) est lié à la
 * rubrique de son école, créée au besoin (cf thematique_cioidc_titre_rubrique_ecole()).
 * Un élève est seulement rattaché à sa classe si elle existe déjà (cochée par son prof
 * ou créée par un admin), ex: affichage de l'emoji de classe hors du contexte d'une
 * rubrique ; à défaut, à la rubrique de son école si elle existe. Une fois sa classe
 * trouvée, il est détaché de cette école.
 *
 * @param array $classes_reelles Classes ENT (groupes CLS)
 * @param array $groupes_libres_pertinents Groupes libres du projet de ce site
 * @param int|string $id_travail_classes Rubrique « Travail des classes » de l'année
 * @param int|string $id_consignes Rubrique « Consignes » de l'année
 * @param array $uai_liste UAI de rattachement (ENTAllUai)
 * @return array [rubriques de classe à lier, rubriques de projet à lier, rubriques de classe à détacher]
 */
function thematique_cioidc_resoudre_liens_rubriques(
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	bool $is_intervenant,
	array $classes_reelles,
	array $groupes_libres_pertinents,
	$id_travail_classes,
	$id_consignes,
	array $uai_liste
) {
	$classes_a_lier = [];
	$projets_a_lier = [];
	$classes_a_detacher = [];

	if ($is_enseignant && !$is_webmestre && $groupes_libres_pertinents) {
		foreach ($groupes_libres_pertinents as $groupe) {
			if ($id_projet = thematique_trouver_ou_creer_rubrique($groupe->name ?? '', $id_consignes)) {
				$projets_a_lier[] = $id_projet;
			}
		}
		if (!$classes_reelles && !$is_intervenant) {
			$titre_ecole = thematique_cioidc_titre_rubrique_ecole((string) ($uai_liste[0] ?? ''));
			if ($titre_ecole !== '' && ($id_ecole = thematique_trouver_ou_creer_rubrique($titre_ecole, $id_travail_classes, true))) {
				$classes_a_lier[] = $id_ecole;
			}
		}
	} elseif ($is_eleve) {
		foreach ($classes_reelles as $groupe) {
			$id_classe = thematique_trouver_rubrique(thematique_cioidc_titre_rubrique_classe($groupe), $id_travail_classes)
				?: thematique_cioidc_renommer_ancienne_classe($groupe, $id_travail_classes);
			if ($id_classe) {
				$classes_a_lier[] = $id_classe;
			}
		}
		if ($id_ecole = thematique_cioidc_rubrique_ecole_eleve($classes_reelles, $uai_liste, $id_travail_classes)) {
			if ($classes_a_lier) {
				$classes_a_detacher[] = $id_ecole;
			} else {
				$classes_a_lier[] = $id_ecole;
			}
		}
	}

	return [$classes_a_lier, $projets_a_lier, $classes_a_detacher];
}

// Associe effectivement l'auteur aux rubriques résolues ci-dessus (+ le blog
// pédagogique et l'agenda communs à tous les profs). L'agenda est la rubrique
// portant le mot "blogs" (inversé avec "evenements" = blog pédagogique / salle
// des profs) : sans ce lien, thematique_peut_publier_dans_mot('blogs') masque
// "Nouvel événement agenda" du menu Publier et l'envoi est refusé.
// Détache d'abord les rubriques de $classes_a_detacher (école d'un élève qui a
// retrouvé sa classe).
function thematique_cioidc_associer_rubriques(
	array $auteur,
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_a_lier,
	array $projets_a_lier,
	array $classes_a_detacher = []
) {
	foreach ($classes_a_detacher as $id_classe) {
		objet_dissocier(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_classe]);
	}
	if ($is_enseignant && !$is_webmestre) {
		$blog = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre = ' . sql_quote(_T('thematique:cioidc_blog_pedagogique'))
		);
		if ($blog) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
		}
		if ($agenda = thematique_id_rubrique_a_mot('blogs')) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $agenda]);
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
