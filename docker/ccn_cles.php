<?php

/**
 * Rétablit et complète config/cles.php au démarrage du conteneur (docker-entrypoint.sh).
 *
 * Seul IMG/ est persistant : config/cles.php repart vide à chaque pod. SPIP ne restaure
 * les clés (secret_des_auth, secret_du_site...) qu'à la connexion PAR MOT DE PASSE d'un
 * webmestre ayant une sauvegarde chiffrée (spip_auteurs.backup_cles), et ne crée cette
 * sauvegarde qu'à cette même connexion (cf ecrire/auth/spip.php). Les comptes passant par
 * le SSO (cioidc) ne déclenchent ni l'une ni l'autre : le fichier restait incomplet et le
 * mot de passe du compte admin du conteneur ne fonctionnait plus.
 *
 * On rejoue donc cette connexion pour l'admin créé par l'entrypoint (SPIP_ADMIN_LOGIN /
 * SPIP_ADMIN_PASS) :
 * 1. auth_spip_dist() restaure les clés manquantes depuis sa sauvegarde et la rafraîchit ;
 * 2. sans aucune sauvegarde (premier démarrage, ou clé déjà perdue), on régénère
 *    secret_des_auth puis on réenregistre son mot de passe, ce qui crée la sauvegarde ;
 *    si un AUTRE webmestre a une sauvegarde, on ne régénère rien (sa clé est récupérable
 *    à sa prochaine connexion par mot de passe) ;
 * 3. secret_du_site / secret_des_actions sont créés s'ils manquent encore (pas dans le cas
 *    où la sauvegarde est chez un autre webmestre).
 *
 * Lancé en www-data depuis la racine du site : php /usr/local/lib/ccn/ccn_cles.php
 */

use Spip\Chiffrer\SpipCles;

$login = getenv('SPIP_ADMIN_LOGIN') ?: 'admin';
$pass = (string) getenv('SPIP_ADMIN_PASS');
if ($pass === '') {
	fwrite(STDERR, "ccn_cles : SPIP_ADMIN_PASS absent, clés non vérifiées\n");
	exit(0);
}
require 'vendor/autoload.php';
include_once 'ecrire/inc_version.php';
if (!defined('_FILE_CONNECT') || !_FILE_CONNECT) {
	fwrite(STDERR, "ccn_cles : site non installé (pas de connect.php), rien à faire\n");
	exit(0);
}
include_spip('base/abstract_sql');
include_spip('inc/auth');
include_spip('auth/spip');

$cles = SpipCles::instance();
// État avant la connexion : en cas d'échec sans sauvegarde, auth_spip_dist() peut avoir
// généré elle-même un nouveau secret_des_auth, qui ne valide plus aucun mot de passe.
$secret_present = (bool) $cles->getSecretAuth();
$auteur = auth_spip_dist($login, $pass);

// Clé absente au démarrage et non restaurée par la connexion : la sauvegarde manque ou
// n'est pas celle de l'admin. Clé présente mais connexion en échec : mauvais mot de passe
// dans SPIP_ADMIN_PASS, on ne touche à rien.
if (!$auteur && !$secret_present) {
	$autres = sql_allfetsel(
		'id_auteur',
		'spip_auteurs',
		"statut='0minirezo' AND webmestre='oui' AND backup_cles!='' AND login<>" . sql_quote($login)
	);
	if ($autres) {
		fwrite(STDERR, 'ccn_cles : secret_des_auth absent, sauvegarde détenue par le(s) webmestre(s) #'
			. implode(', #', array_column($autres, 'id_auteur'))
			. " : restaurée à leur prochaine connexion par mot de passe, rien n'est régénéré\n");
	} elseif ($id_auteur = intval(sql_getfetsel('id_auteur', 'spip_auteurs', 'login=' . sql_quote($login)))) {
		auth_spip_initialiser_secret();
		include_spip('action/editer_auteur');
		include_spip('inc/autoriser');
		autoriser_exception('modifier', 'auteur', $id_auteur);
		auteur_modifier($id_auteur, ['pass' => $pass]);
		autoriser_exception('modifier', 'auteur', $id_auteur, false);
		$auteur = auth_spip_dist($login, $pass);
		fwrite(STDERR, "ccn_cles : aucune sauvegarde, secret_des_auth régénéré et mot de passe de '$login' réenregistré"
			. ($auteur ? '' : ' (échec de la vérification)') . "\n");
	} else {
		fwrite(STDERR, "ccn_cles : compte '$login' introuvable, clés non restaurées\n");
	}
}

// Clés partagées fichier/base : créées si toujours absentes (site neuf) — sauf si les clés
// attendent encore la restauration par un autre webmestre : restore() ne remplaçant pas une
// clé existante, en générer maintenant ferait perdre définitivement les anciennes.
if ($cles->getSecretAuth()) {
	$cles->getSecretSite();
	$cles->getSecretActions();
	// La sauvegarde a été prise à la connexion, avant ces deux clés : on la reprend sur le
	// jeu complet, sinon elles seraient régénérées (donc changées) au prochain démarrage.
	if ($auteur) {
		$auteur = auth_spip_dist($login, $pass);
	}
}

$avec_sauvegarde = $auteur ? (bool) sql_getfetsel('backup_cles', 'spip_auteurs', 'id_auteur=' . intval($auteur['id_auteur'])) : false;
fwrite(STDERR, 'ccn_cles : secret_des_auth ' . ($cles->getSecretAuth() ? 'présent' : 'ABSENT')
	. ", connexion de '$login' " . ($auteur ? 'OK' : 'en échec')
	. ($avec_sauvegarde ? ', sauvegarde des clés à jour' : '') . "\n");
