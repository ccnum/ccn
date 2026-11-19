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
 * Document joint à un commentaire.
 *
 * Le fichier est envoyé à l'étape « Prévisualiser », alors que le commentaire
 * n'est enregistré qu'à l'étape « Poster » : entre les deux, il est gardé dans
 * tmp/documents_forum/<jeton>.bin, avec ses métadonnées (nom, auteur) dans
 * <jeton>.txt, et le jeton circule dans le champ caché document_forum. Même
 * principe que le formulaire de forum natif (plugins-dist/forum), mais avec un
 * jeton par envoi plutôt qu'un emplacement unique par session : un fichier
 * abandonné (Annuler) ne se rattache pas au commentaire suivant de l'auteur.
 */

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
 * @param string $jeton Valeur du champ caché document_forum
 * @return array|null ['jeton', 'tmp_name', 'name'], ou null
 */
function forumv2_document_en_attente($jeton) {
	$jeton = (string) $jeton;
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
 * Supprime un document en attente (fichier et métadonnées).
 */
function forumv2_document_supprimer($document) {
	if ($document) {
		spip_unlink($document['tmp_name']);
		spip_unlink(preg_replace(',\.bin$,', '.txt', $document['tmp_name']));
	}
}

/**
 * Gère le champ fichier à la vérification : retrait demandé, nouvel envoi
 * (contrôlé puis mis en attente), ou conservation du document déjà en attente.
 * Le jeton retenu est réinjecté dans document_forum pour charger().
 *
 * @return string|null Message d'erreur, ou null
 */
function forumv2_document_verifier() {
	$document = forumv2_document_en_attente(_request('document_forum'));

	if (_request('supprimer_document_forum')) {
		forumv2_document_supprimer($document);
		$document = null;
	}

	$envoi = $_FILES['fichier_upload'] ?? null;
	$erreur = null;
	if ($envoi && is_string($envoi['name'] ?? null) && $envoi['name'] !== '' && !empty($envoi['tmp_name'])) {
		$erreur = forumv2_document_mettre_en_attente($envoi, $document);
		// Mis en attente ou refusé, le fichier ne doit plus figurer dans
		// $_FILES : bigup efface alors sa propre copie (cf
		// bigup_formulaire_verifier) au lieu de la réafficher dans sa zone
		// de dépôt et de la réinjecter au prochain envoi.
		unset($_FILES['fichier_upload']);
	}

	set_request('document_forum', $document['jeton'] ?? '');

	return $erreur;
}

/**
 * Contrôle un fichier envoyé et le met en attente, à la place du document
 * déjà en attente (un seul document par commentaire).
 *
 * @param array $envoi Entrée de $_FILES
 * @param array|null $document Document en attente, remplacé en cas de succès
 * @return string|null Message d'erreur, ou null
 */
function forumv2_document_mettre_en_attente($envoi, &$document) {
	include_spip('inc/uploads');
	include_spip('inc/joindre_document');
	include_spip('action/ajouter_documents');
	include_spip('inc/getdocument');

	// Extension et taille, comme pour tout champ fichier_upload du site.
	$erreurs = ccn_verifier_uploads();
	if (!empty($erreurs['message_erreur'])) {
		return $erreurs['message_erreur'];
	}

	[, $nom] = fixer_extension_document($envoi);
	$jeton = md5(uniqid(random_int(0, mt_getrandmax()), true));
	$base = forumv2_document_repertoire() . $jeton;
	if (!deplacer_fichier_upload($envoi['tmp_name'], $base . '.bin')) {
		return _T('copie_document_impossible');
	}
	ecrire_fichier($base . '.txt', serialize([
		'name' => $nom,
		'id_auteur' => intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0),
	]));

	forumv2_document_supprimer($document);
	forumv2_document_purger();
	$document = forumv2_document_en_attente($jeton);

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
 * Rattache le document en attente au commentaire publié.
 *
 * @param int $id_forum
 */
function forumv2_document_publier($id_forum) {
	$document = forumv2_document_en_attente(_request('document_forum'));
	if (!$document || !$id_forum) {
		return;
	}

	$ajouter_documents = charger_fonction('ajouter_documents', 'action');
	$resultat = $ajouter_documents('new', [[
		'tmp_name' => $document['tmp_name'],
		'name' => $document['name'],
	]], 'forum', $id_forum, 'document');
	foreach ($resultat as $id_document) {
		if (!is_numeric($id_document) || !$id_document) {
			spip_log("document non rattaché au forum $id_forum : " . $id_document, 'forumv2.' . _LOG_ERREUR);
		}
	}

	forumv2_document_supprimer($document);
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
	$document = forumv2_document_en_attente(_request('document_forum'));

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
		'_document_forum' => $document['jeton'] ?? '',
		'_document_forum_nom' => $document['name'] ?? '',
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
