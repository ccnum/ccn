<?php

/**
 * Fonctions utiles au plugin Incarner
 *
 * @plugin     incarner
 * @copyright  2016
 * @author     Michel Bystranowski
 * @licence    GNU/GPL
 */

/* On perd le droit de changer d'auteur comme on veut après 1h sans activité.
	 C'est important parce qu'on garde ce droit même après déconnexion (pour
	 pouvoir tester le site en tant que visiteur anonyme et revenir ensuite
	 facilement au webmestre). */
if (! defined('_INCARNER_DELAI_EXPIRATION')) {
	define('_INCARNER_DELAI_EXPIRATION', 3600);
}

/**
 * Determiner si on affiche le login ou l'email
 * @return mixed
 */
function incarner_login_affiche($login, $email) {
	$login_aff = $login ?: $email;
	// si le login est un md5 et qu'on a un email, afficher l'email
	if (strlen($login_aff) == 32
		and !preg_match(',[^0-9a-f],i', $login_aff)
		and $email) {
		$login_aff = $email;
	}
	return $login_aff;
}

/**
 * Vérifie si une clé d'incarnation est valide
 *
 * @param string $cle : La clé d'incarnation
 *
 * @return false|int : id_auteur de l'auteur qui a lance l'incarnation si la clé est valide, false sinon
 */
function incarner_cle_valide($cle) {
	include_spip('inc/filtres');
	$cle_parts = decoder_contexte_ajax($cle, 'incarner');

	if (!$cle_parts or !is_array($cle_parts) or !count($cle_parts) === 3) {
		return false;
	}

	[$hash, $id_auteur_session_origine, $id_auteur_incarne] = $cle_parts;

	// si l'auteur connecte n'est pas parmi l'un des 2 id_auteur c'est une suspicion d'usurpation
	if (empty($GLOBALS['visiteur_session']['id_auteur'])
	  or !in_array($GLOBALS['visiteur_session']['id_auteur'], [$id_auteur_session_origine, $id_auteur_incarne])) {
		incarner_invalider_cle($cle);
		return false;
	}

	include_spip('inc/config');

	if (! $cles = lire_config('incarner/cles')) {
		$cles = [];
	}
	if (! $maj = lire_config('incarner/maj')) {
		$maj = [];
	}

	$now = time();
	if ($cles and $cles[$id_auteur_session_origine] === $cle) {
		if (empty($maj[$id_auteur_session_origine]) or ($now > ($maj[$id_auteur_session_origine] + _INCARNER_DELAI_EXPIRATION))) {
			// la cle est expiree
			incarner_invalider_cle($cle);
			return false;
		}
		// remettre a jour le flag maj si il date de plus d'une minute
		// on a pas besoin de plus de precision et ca evite de mettre a jour le meta_cache tous les hits
		if ($now > ($maj[$id_auteur_session_origine] + 60)) {
			ecrire_config("incarner/maj/$id_auteur_session_origine", $now);
		}

		return $id_auteur_session_origine;
	}

	return false;
}

/**
 * Renouveler la clé d'incarnation
 * La cle est valable uniquement pour un couple [id_auteur d'origine , id_auteur incarne]
 * Ainsi, si le cookie est volé, il ne peut être réutilisé avec une autre session
 *
 * Si on en a pas déjà une on en crée une nouvelle
 *
 * @param int $id_auteur_session
 * @param int $id_auteur_incarne
 */
function incarner_renouveler_cle($id_auteur_session, $id_auteur_incarne) {

	include_spip('inc/config');
	include_spip('inc/cookie');
	include_spip('inc/filtres');

	// si il y a deja une cle, l'invalider par precaution
	if (!empty($_COOKIE['spip_cle_incarner'])) {
		incarner_invalider_cle($_COOKIE['spip_cle_incarner']);
	}

	if (! $cles = lire_config('incarner/cles')) {
		$cles = [];
	}
	if (! $maj = lire_config('incarner/maj')) {
		$maj = [];
	}

	include_spip('inc/acces');
	$cle_parts = [md5(creer_uniqid()), intval($id_auteur_session), intval($id_auteur_incarne)];

	$nouvelle_cle = encoder_contexte_ajax($cle_parts, 'incarner');

	$cles[$id_auteur_session] = $nouvelle_cle;
	$maj[$id_auteur_session] = time();

	ecrire_config('incarner/cles', $cles);
	ecrire_config('incarner/maj', $maj);

	spip_setcookie(
		'spip_cle_incarner',
		$nouvelle_cle,
		defined('_INCARNER_COOKIE_PERSISTANT') ? time() + intval(_INCARNER_COOKIE_PERSISTANT) : 0
	);
}

/**
 * Invalider la clé de l'auteur et effacer son cookie
 */
function incarner_invalider_cle($cle = null) {

	if ($cle === null) {
		$cle = ($_COOKIE['spip_cle_incarner'] ?? '');
	}

	if ($cle) {
		include_spip('inc/config');
		if ($cles = lire_config('incarner/cles')
		  and $index_cle = array_search($cle, $cles)) {
			unset($cles[$index_cle]);
			ecrire_config('incarner/cles', $cles);
		}

		if ($maj = lire_config('incarner/maj')
			and $index_cle = array_search($cle, $maj)) {
			unset($maj[$index_cle]);
			ecrire_config('incarner/maj', $maj);
		}

		if (!empty($_COOKIE['spip_cle_incarner'])) {
			include_spip('inc/cookie');
			spip_setcookie('spip_cle_incarner', '');
		}
	}
}

/**
 * Envoyer un mail de notification par sécurité
 * @param array $auteur
 */
function incarner_notifier_nouvelle_incarnation($auteur) {
	include_spip('inc/config');

	if ($email_dest = lire_config('incarner/email_notification')) {

		$auteur_source =
			incarner_login_affiche($GLOBALS['visiteur_session']['login'], $GLOBALS['visiteur_session']['email'])
			. ' #' . $GLOBALS['visiteur_session']['id_auteur'];

		$auteur_dest =
			incarner_login_affiche($auteur['login'], $auteur['email'])
			. ' #' . $auteur['id_auteur'];

		$args = ['auteur_source' => $auteur_source, 'auteur_dest' => $auteur_dest];
		$sujet = _T('incarner:notification_sujet_email', $args);
		$texte = _T('incarner:notification_texte_email', $args)
			. "\n\n"
			. _T('date') . ' : ' . date('Y-m-d H:i:s')
			. "\n"
			. 'IP :' . $GLOBALS['ip'];

		$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
		$envoyer_mail($email_dest, $sujet, $texte);
	}
}
