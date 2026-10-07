<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Modification de son propre profil (nom, email, mot de passe) par un compte
 * créé dans SPIP, depuis le menu sous le nom (cf noisettes/auteur_editer.html).
 *
 * Plutôt que #FORMULAIRE_EDITER_AUTEUR : celui-ci affiche aussi bio, clé PGP,
 * site web et les champs extras, inutiles ici. Toujours l'auteur connecté (pas
 * d'id_auteur en argument). Un compte SSO (source oidc) n'a ni mot de passe
 * SPIP ni nom à modifier (resynchronisé depuis l'ENT à chaque connexion) : le
 * formulaire ne s'affiche pas pour lui.
 *
 * Le mot de passe actuel est demandé pour changer d'email ou de mot de passe :
 * une session volée ne doit pas suffire à prendre la main sur le compte.
 *
 * @package SPIP\Thematique\Formulaires
 */

function formulaires_editer_profil_auteur() {
	include_spip('inc/session');
	$id_auteur = intval(session_get('id_auteur'));
	if (!$id_auteur) {
		return null;
	}
	$auteur = sql_fetsel('id_auteur, nom, email, login, source, pass', 'spip_auteurs', 'id_auteur=' . $id_auteur);
	return ($auteur && $auteur['source'] === 'spip') ? $auteur : null;
}

function formulaires_editer_profil_charger_dist() {
	$auteur = formulaires_editer_profil_auteur();
	if (!$auteur) {
		return false;
	}

	return [
		'nom' => $auteur['nom'],
		'email' => $auteur['email'],
		'pass_actuel' => '',
		'new_pass' => '',
		'new_pass2' => '',
	];
}

function formulaires_editer_profil_verifier_dist() {
	$auteur = formulaires_editer_profil_auteur();
	if (!$auteur) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}

	$erreurs = [];
	$nom = trim((string) _request('nom'));
	$email = trim((string) _request('email'));
	$new_pass = (string) _request('new_pass');

	if ($nom === '') {
		$erreurs['nom'] = _T('info_obligatoire');
	} elseif (mb_strlen($nom) > 255) {
		$erreurs['nom'] = _T('thematique:titre_trop_long', ['max' => 255]);
	}

	include_spip('inc/filtres');
	if ($email !== '' && !email_valide($email)) {
		$erreurs['email'] = _T('form_email_non_valide');
	}

	if ($new_pass !== '') {
		if ($new_pass !== (string) _request('new_pass2')) {
			$erreurs['new_pass'] = _T('ecrire:info_passes_identiques');
		} else {
			include_spip('inc/auth');
			if ($erreur = auth_verifier_pass('spip', $auteur['login'], $new_pass, $auteur['id_auteur'])) {
				$erreurs['new_pass'] = $erreur;
			}
		}
	}

	// Mot de passe actuel, sauf pour un compte qui n'en a pas encore (connecté
	// par le lien "mot de passe oublié", par exemple).
	$change_sensible = $new_pass !== '' || $email !== $auteur['email'];
	if ($change_sensible && $auteur['pass'] !== '') {
		$auth_spip = charger_fonction('spip', 'auth');
		$verifie = $auth_spip($auteur['login'], (string) _request('pass_actuel'));
		if (!is_array($verifie) || intval($verifie['id_auteur'] ?? 0) !== intval($auteur['id_auteur'])) {
			$erreurs['pass_actuel'] = _T('thematique:profil_pass_actuel_incorrect');
		}
	}

	if ($erreurs && empty($erreurs['message_erreur'])) {
		$erreurs['message_erreur'] = _T('thematique:profil_erreurs');
	}
	return $erreurs;
}

function formulaires_editer_profil_traiter_dist() {
	$auteur = formulaires_editer_profil_auteur();
	if (!$auteur) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}

	include_spip('action/editer_auteur');
	// auteur_modifier() recharge aussi les sessions de l'auteur (nom affiché dans le menu)
	$erreur = auteur_modifier($auteur['id_auteur'], [
		'nom' => trim((string) _request('nom')),
		'email' => trim((string) _request('email')),
	], true);
	if ($erreur) {
		return ['message_erreur' => $erreur];
	}

	$new_pass = (string) _request('new_pass');
	if ($new_pass !== '') {
		include_spip('inc/auth');
		if (!auth_modifier_pass('spip', $auteur['login'], $new_pass, $auteur['id_auteur'])) {
			return ['message_erreur' => _T('thematique:profil_erreurs')];
		}
	}

	// Champs mot de passe vidés au réaffichage
	set_request('pass_actuel', '');
	set_request('new_pass', '');
	set_request('new_pass2', '');

	return ['message_ok' => _T('thematique:profil_enregistre'), 'editable' => true];
}
