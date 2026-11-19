<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie que le texte du commentaire n'est pas vide.
 */
function forumv2_texte_est_valide() {
	return trim(_request('texte')) !== '';
}

/*
 * Documents joints à un commentaire (plusieurs possibles, comme pour une
 * mission).
 *
 * Les fichiers sont envoyés à l'étape « Prévisualiser », alors que le
 * commentaire n'est enregistré qu'à l'étape « Poster » : entre les deux,
 * chacun est gardé dans tmp/documents_forum/<jeton>.bin, avec ses
 * métadonnées (nom, auteur) dans <jeton>.txt, et les jetons circulent dans
 * les champs cachés document_forum[]. Même principe que le formulaire de
 * forum natif (plugins-dist/forum), mais avec un jeton par fichier plutôt
 * qu'un emplacement unique par session : un fichier abandonné (Annuler) ne
 * se rattache pas au commentaire suivant de l'auteur.
 */

/**
 * Nombre maximal de documents joints à un même commentaire.
 */
if (!defined('_FORUMV2_DOCUMENTS_MAX')) {
	define('_FORUMV2_DOCUMENTS_MAX', 10);
}

/**
 * Répertoire des documents en attente de publication.
 */
function forumv2_document_repertoire() {
	return sous_repertoire(_DIR_TMP, 'documents_forum');
}

/**
 * Document en attente désigné par un jeton, s'il existe et appartient à
 * l'auteur connecté.
 *
 * @param string $jeton Un des champs cachés document_forum[]
 * @return array|null ['jeton', 'tmp_name', 'name'], ou null
 */
function forumv2_document_en_attente($jeton) {
	$jeton = is_string($jeton) ? $jeton : '';
	$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
	if (!$id_auteur || !preg_match(',^[a-f0-9]{32}$,', $jeton)) {
		return null;
	}

	$base = forumv2_document_repertoire() . $jeton;
	if (!file_exists($base . '.bin') || !lire_fichier($base . '.txt', $meta)) {
		return null;
	}
	$meta = @unserialize($meta);
	if (!is_array($meta) || intval($meta['id_auteur'] ?? 0) !== $id_auteur) {
		return null;
	}

	return [
		'jeton' => $jeton,
		'tmp_name' => $base . '.bin',
		'name' => (string) ($meta['name'] ?? ''),
	];
}

/**
 * Documents en attente désignés par les champs cachés document_forum[], dans
 * l'ordre d'envoi.
 *
 * @return array Documents (cf forumv2_document_en_attente()), indexés par jeton
 */
function forumv2_documents_en_attente() {
	$documents = [];
	foreach ((array) _request('document_forum') as $jeton) {
		if ($document = forumv2_document_en_attente($jeton)) {
			$documents[$document['jeton']] = $document;
		}
	}

	return array_slice($documents, 0, _FORUMV2_DOCUMENTS_MAX, true);
}

/**
 * Supprime un document en attente (fichier et métadonnées).
 */
function forumv2_document_supprimer($document) {
	if ($document) {
		spip_unlink($document['tmp_name']);
		spip_unlink(preg_replace(',\.bin$,', '.txt', $document['tmp_name']));
	}
}

/**
 * Gère le champ fichier à la vérification : retraits demandés, nouveaux
 * envois (contrôlés puis mis en attente), conservation des documents déjà en
 * attente. Les jetons retenus sont réinjectés dans document_forum pour
 * charger().
 *
 * @return string|null Message d'erreur, ou null
 */
function forumv2_document_verifier() {
	$documents = forumv2_documents_en_attente();

	$a_retirer = array_filter((array) _request('supprimer_document_forum'), 'is_string');
	foreach (array_intersect_key($documents, array_flip($a_retirer)) as $jeton => $document) {
		forumv2_document_supprimer($document);
		unset($documents[$jeton]);
	}

	$erreur = null;
	if (!empty($_FILES['fichier_upload']['name'])) {
		$erreur = forumv2_document_mettre_en_attente($_FILES['fichier_upload'], $documents);
		// Mis en attente ou refusés, les fichiers ne doivent plus figurer dans
		// $_FILES : bigup efface alors sa propre copie (cf
		// bigup_formulaire_verifier) au lieu de la réafficher dans sa zone
		// de dépôt et de la réinjecter au prochain envoi.
		unset($_FILES['fichier_upload']);
	}

	set_request('document_forum', array_keys($documents));

	return $erreur;
}

/**
 * Fichiers d'une entrée de $_FILES, champ simple ou multiple (name[]).
 *
 * @param array $envoi Entrée de $_FILES
 * @return array Liste de ['name', 'type', 'tmp_name'] envoyés sans erreur
 */
function forumv2_document_fichiers_envoyes($envoi) {
	$fichiers = [];
	foreach ((array) ($envoi['name'] ?? []) as $cle => $nom) {
		$valeur = function ($champ) use ($envoi, $cle) {
			return is_array($envoi[$champ] ?? null) ? ($envoi[$champ][$cle] ?? null) : ($envoi[$champ] ?? null);
		};
		if (
			is_string($nom) && $nom !== ''
			&& is_string($valeur('tmp_name')) && $valeur('tmp_name') !== ''
			&& intval($valeur('error')) === UPLOAD_ERR_OK
		) {
			$fichiers[] = ['name' => $nom, 'type' => (string) $valeur('type'), 'tmp_name' => $valeur('tmp_name')];
		}
	}

	return $fichiers;
}

/**
 * Contrôle les fichiers envoyés et les ajoute aux documents en attente.
 * Tout ou rien : un fichier refusé (format, taille, nombre) fait refuser
 * l'envoi entier.
 *
 * @param array $envoi Entrée de $_FILES
 * @param array $documents Documents en attente, complétés en cas de succès
 * @return string|null Message d'erreur, ou null
 */
function forumv2_document_mettre_en_attente($envoi, &$documents) {
	include_spip('inc/uploads');
	include_spip('inc/joindre_document');
	include_spip('action/ajouter_documents');
	include_spip('inc/getdocument');

	$fichiers = forumv2_document_fichiers_envoyes($envoi);
	if (!$fichiers) {
		return null;
	}

	// Extension et taille, comme pour tout champ fichier_upload du site.
	$erreurs = ccn_verifier_uploads();
	if (!empty($erreurs['message_erreur'])) {
		return $erreurs['message_erreur'];
	}

	if (count($documents) + count($fichiers) > _FORUMV2_DOCUMENTS_MAX) {
		return _T('thematique:erreur_documents_commentaire_max', ['max' => _FORUMV2_DOCUMENTS_MAX]);
	}

	$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
	foreach ($fichiers as $fichier) {
		[, $nom] = fixer_extension_document($fichier);
		$jeton = md5(uniqid(random_int(0, mt_getrandmax()), true));
		$base = forumv2_document_repertoire() . $jeton;
		if (!deplacer_fichier_upload($fichier['tmp_name'], $base . '.bin')) {
			return _T('copie_document_impossible');
		}
		ecrire_fichier($base . '.txt', serialize([
			'name' => $nom,
			'id_auteur' => $id_auteur,
		]));
		$documents[$jeton] = forumv2_document_en_attente($jeton);
	}

	forumv2_document_purger();

	return null;
}

/**
 * Supprime les documents en attente abandonnés depuis plus d'un jour.
 */
function forumv2_document_purger() {
	foreach (glob(forumv2_document_repertoire() . '*.{bin,txt}', GLOB_BRACE) ?: [] as $fichier) {
		if (filemtime($fichier) < time() - 86400) {
			spip_unlink($fichier);
		}
	}
}

/**
 * Rattache les documents en attente au commentaire publié.
 *
 * @param int $id_forum
 */
function forumv2_document_publier($id_forum) {
	$documents = forumv2_documents_en_attente();
	if (!$documents || !$id_forum) {
		return;
	}

	$ajouter_documents = charger_fonction('ajouter_documents', 'action');
	$fichiers = [];
	foreach ($documents as $document) {
		$fichiers[] = ['tmp_name' => $document['tmp_name'], 'name' => $document['name']];
	}
	$resultat = $ajouter_documents('new', $fichiers, 'forum', $id_forum, 'document');
	foreach ($resultat as $id_document) {
		if (!is_numeric($id_document) || !$id_document) {
			spip_log("document non rattaché au forum $id_forum : " . $id_document, 'forumv2.' . _LOG_ERREUR);
		}
	}

	array_map('forumv2_document_supprimer', $documents);
}

/**
 * Charge le formulaire.
 *
 * Modes :
 * - publication : création d'un nouveau commentaire
 * - edition : modification d'un commentaire existant
 *
 * @param int|string $id_article Article concerné
 * @return array Valeurs du formulaire
 */
function formulaires_forumv2_charger_dist($id_article) {
	$publication_forum_action = _request('publication_forum_action');
	$id_forum = intval(_request('id_forum'));
	$id_parent = intval(_request('id_parent'));

	$affichage = 'redaction';
	$texte = _request('texte');

	/**
	 * En mode édition, si aucun texte n'a encore été envoyé,
	 * on charge le texte actuel du commentaire.
	 */
	if ($id_forum && !$texte) {
		$forum = sql_fetsel('id_forum, objet, id_objet, id_parent, texte', 'spip_forum', 'id_forum=' . intval($id_forum));

		if (!$forum) {
			return [
				'id_article' => $id_article,
				'id_forum' => $id_forum,
				'id_parent' => $id_parent,
				'texte' => '',
				'affichage' => 'redaction',
				'edition' => true,
				'commentaire_introuvable' => true,
			];
		}

		/**
		 * Vérification que le commentaire appartient bien
		 * à l'article affiché.
		 */
		if ($forum['objet'] !== 'article' || intval($forum['id_objet']) !== intval($id_article)) {
			return [
				'id_article' => $id_article,
				'id_forum' => $id_forum,
				'id_parent' => $id_parent,
				'texte' => '',
				'affichage' => 'redaction',
				'edition' => true,
				'commentaire_introuvable' => true,
			];
		}

		$texte = $forum['texte'];
		$id_parent = intval($forum['id_parent']);
	}

	/**
	 * Prévisualisation.
	 */
	if (
		$publication_forum_action === 'previsualiser'
		&& forumv2_texte_est_valide()
	) {
		$affichage = 'previsualisation';
	}

	include_spip('inc/uploads');
	$documents = array_values(forumv2_documents_en_attente());

	// Encadré « droit à la déconnexion » : un nouveau commentaire notifie par
	// e-mail, différé en période de repos (cf ccn/inc/ccn_deconnexion.php). Pas
	// en modification, qui n'envoie rien.
	$avertissement_deconnexion = '';
	include_spip('inc/ccn_deconnexion');
	if (!$id_forum && ccn_fin_deconnexion()) {
		$avertissement_deconnexion = _T('thematique:avertissement_deconnexion_commentaire', [
			'debut' => _CCN_DECONNEXION_HEURE_DEBUT,
			'fin' => _CCN_DECONNEXION_HEURE_FIN,
		]);
	}

	return [
		'id_article' => $id_article,
		'id_forum' => $id_forum,
		'id_parent' => $id_parent,
		'texte' => $texte,
		'affichage' => $affichage,
		'edition' => ($id_forum > 0),
		// Préfixe _ : valeurs calculées, que SPIP ne remplace pas par la saisie.
		// Documents en attente : [['jeton' => …, 'name' => …], …]
		'_documents_forum' => array_map(function ($document) {
			return ['jeton' => $document['jeton'], 'name' => $document['name']];
		}, $documents),
		'_accept' => ccn_attribut_accept_documents(),
		'_avertissement_deconnexion' => $avertissement_deconnexion,
		// Active la réinjection dans $_FILES des fichiers envoyés par bigup.
		'_bigup_rechercher_fichiers' => true,
	];
}

/**
 * Vérification du formulaire.
 *
 * @param int|string $id_article Article concerné
 * @return array Erreurs, vide si aucune
 */
function formulaires_forumv2_verifier_dist($id_article) {
	$erreurs = [];

	$id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? 0;

	if (!$id_auteur) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
		return $erreurs;
	}

	if (!forumv2_texte_est_valide()) {
		$erreurs['texte'] = _T('thematique:erreur_commentaire_texte_obligatoire');
		// Défini (vide) : sinon SPIP ajoute « Il y a 1 erreur dans votre saisie… »
		// au-dessus, redondant avec le message sous le titre.
		$erreurs['message_erreur'] = '';
	}

	// Plus aucune publication/édition de commentaire (admin
	// compris) sur un article d'une année scolaire passée, une fois la
	// nouvelle année créée.
	include_spip('thematique_fonctions');
	if (thematique_annee_est_passee(thematique_annee_article($id_article))) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
		return $erreurs;
	}

	// Même quand le texte manque : le fichier envoyé reste en attente et
	// n'est pas à renvoyer une fois l'erreur corrigée.
	if ($erreur_document = forumv2_document_verifier()) {
		$erreurs['fichier_upload'] = $erreur_document;
		$erreurs['message_erreur'] = '';
	}

	/**
	 * En mode édition, vérifier que le commentaire existe
	 * et que l'utilisateur connecté en est bien l'auteur.
	 */
	$id_forum = intval(_request('id_forum'));

	if ($id_forum) {
		$forum = sql_fetsel(
			'id_forum, id_objet, id_auteur',
			'spip_forum',
			'id_forum=' . intval($id_forum) . " AND objet='article'"
		);

		if (!$forum) {
			$erreurs['message_erreur'] = _T('thematique:erreur_commentaire_404');
			return $erreurs;
		}

		if (intval($forum['id_objet']) !== intval($id_article)) {
			$erreurs['message_erreur'] = _T('thematique:erreur_commentaire_422');
			return $erreurs;
		}

		if (intval($forum['id_auteur']) !== intval($id_auteur)) {
			$erreurs['message_erreur'] = _T('info_acces_interdit');
			return $erreurs;
		}
	}

	// Réponse : le message parent doit appartenir au même article, sinon le
	// commentaire se rattachait au fil d'un autre article (et en notifiait
	// l'auteur).
	$id_parent = intval(_request('id_parent'));
	if ($id_parent && !sql_countsel('spip_forum', [
		'id_forum=' . $id_parent,
		"objet='article'",
		'id_objet=' . intval($id_article),
	])) {
		$erreurs['message_erreur'] = _T('thematique:erreur_commentaire_422');
		return $erreurs;
	}

	return $erreurs;
}

/**
 * Traitement du formulaire.
 *
 * @param int|string $id_article Article concerné
 * @return array Retour du traitement (message_ok, message_erreur, redirect…)
 */
function formulaires_forumv2_traiter_dist($id_article) {
	include_spip('action/editer_forum');
	include_spip('inc/session');

	// previsualiser / rediger : rien à traiter, l'affichage est calculé par charger()
	// (SPIP ignore les autres clés que message_ok/message_erreur/editable/redirect).
	if (_request('publication_forum_action') === 'publier') {
		$id_forum = intval(_request('id_forum'));
		if ($id_forum) {
			sql_updateq('spip_forum', ['texte' => _request('texte')], 'id_forum=' . $id_forum);
			forumv2_document_publier($id_forum);
		} else {
			$id_parent = intval(_request('id_parent'));
			$id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? 0;
			include_spip('thematique_fonctions');
			$id_forum = forum_inserer(
				$id_parent,
				[
					'objet' => 'article',
					'id_objet' => $id_article,
					'texte' => _request('texte'),
					// Nom calculé côté serveur (même calcul que le formulaire) : le
					// champ caché nom_auteur peut être forgé.
					'auteur' => thematique_nom_auteur_commentaire($id_auteur),
					'statut' => 'publie',
					// Classe active de l'auteur à l'écriture : le commentaire la
					// garde même si le prof change ensuite de classe active.
					'id_classe' => thematique_id_rubrique_classe($id_auteur),
					// Valeurs par défaut du plugin notifications, explicites : sur
					// une base où ces colonnes sont NULL sans défaut, elles
					// restaient NULL et la notification forumposte plantait sur
					// extraire_balises(null) (TypeError, SPIP 4.4.27) — "Erreur"
					// côté ajax alors que le commentaire était bien enregistré.
					'notification' => 1,
					'notification_email' => '',
				]
			);
			session_set('forum_commentaire_succes', $id_forum);
			// Avant la notification, pour que le document soit déjà lié au message.
			forumv2_document_publier($id_forum);

			// forum_inserer() n'est que l'insertion SQL brute : contrairement à
			// forum_insert_base() (chemin de l'ancien formulaire public forum.php),
			// elle ne déclenche pas la notification. Sans cet appel explicite, les
			// destinataires calculés par thematique_notifications_destinataires
			// ne sont jamais notifiés pour un commentaire forumv2.
			if ($id_forum && ($notifications = charger_fonction('notifications', 'inc'))) {
				$notifications('forumposte', $id_forum);
			}
		}
		// Un commentaire publié via ce formulaire "ajax" est en fait suivi
		// d'une vraie navigation (SPIP coeur remplace la page via
		// document.location.replace() dès qu'il détecte une redirection,
		// cf ajax_redirect dans prive/javascript/ajaxCallback.js) : sans
		// onglet=commentaires ici, la mission se recharge bien mais
		// retombe sur son premier onglet au lieu de rester sur les
		// commentaires (cf urlParam 'onglet' de #mission-tabs, initMissionTabs
		// dans controleurs.js).
		return [
			'redirect' => generer_url_public('article', [
				'id_article' => $id_article,
				'mode' => 'complet',
				'onglet' => 'commentaires',
			]),
		];
	}

	return [];
}
