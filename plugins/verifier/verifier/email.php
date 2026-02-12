<?php

/**
 * API de vérification : vérification de la validité d'una adresse de courriel
 *
 * @plugin     verifier
 * @copyright  2018
 * @author     Les Développements Durables
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie la validité d'une adresse de courriel.
 *
 * Les contraintes du mail sont déterminées par le mode de validation
 * En option, on contrôle aussi la disponibilité du mail dans la table des auteurs
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 *   Un éventuel tableau d'options.
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_email_dist($valeur, $options = []) {
	include_spip('inc/filtres');
	if (!is_string($valeur)) {
		return '';
	}

	// Disponibilite des courriels en base AUTEURS
	// Si l'adresse n'est pas disponible, on stoppe tout sinon on continue
	if (!empty($options['disponible']) && !verifier_disponibilite_email($valeur, isset($options['id_auteur']) ? $options['id_auteur'] : null)) {
		return _T('verifier:erreur_email_nondispo', ['email' => echapper_tags($valeur)]);
	}

	//Si on n'autorise qu'un seul email
	if ($options['unique'] ?? false) {
		$emails = verifier_email_explode_emails($valeur);
		if (count($emails) > 1) {
			return _T('verifier:erreur_email_unique');
		}
	}

	// Choix du mode de verification de la syntaxe des courriels
	if (!in_array($options['mode'] ?? '', ['normal','rfc5322','strict'])) {
		$mode = 'normal';
	} else {
		$mode = $options['mode'];
	}

	$fonctions_disponibles = [
		'normal'  => 'email_valide',
		'rfc5322' => 'verifier_email_rfc5322',
		'strict'  => 'verifier_email_de_maniere_stricte'
	];
	$fonction_verif = $fonctions_disponibles[$mode];

	if (!$fonction_verif($valeur)) {
		return _T('verifier:erreur_email', ['email' => echapper_tags($valeur)]);
	}


	return '';
}

/**
 * Une fonction pour explode une liste d'email séparés par une virgule
 * sans casser des emails qui peuvent eux-même contenir des virgules
 *
 * @param $valeur
 * @return array
 */
function verifier_email_explode_emails($valeur) {
	$p = strpos($valeur, '@');
	if ( $p !== false && strpos($valeur, ',', $p) !== false ) {
		if ($parts = preg_split('/(@[^@,]+),/' , $valeur, -1, PREG_SPLIT_DELIM_CAPTURE)) {
			$adresses = [];
			while (!empty($parts)) {
				$left = array_shift($parts);
				if (!empty($parts)) {
					$adresses[] = $left . array_shift($parts);
				} else {
					$adresses[] = $left;
				}
			}
			$adresses = array_map('trim', $adresses);
			$adresses = array_filter($adresses);
			return $adresses;
		}
	}
	return [trim($valeur)];
}

/**
 * Changement de la RegExp d'origine
 *
 * Respect de la RFC5322
 *
 * @link (phraseur détaillé ici : http://www.dominicsayers.com/isemail/)
 * @param string $valeur La valeur à vérifier
 * @return boolean Retourne true uniquement lorsque le mail est valide
 */
function verifier_email_rfc5322($valeur) {
	// Si c'est un spammeur autant arreter tout de suite
	if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $valeur)) {
		spip_log("Tentative d'injection de mail : $valeur");
		return false;
	}
	$adresses = verifier_email_explode_emails($valeur);
	include_spip('inc/is_email');
	foreach ($adresses as $adresse) {
		if (ISEMAIL_VALID !== is_email($adresse, false, ISEMAIL_THRESHOLD)) {
			return false;
		}
	}
	return true;
}

/**
 * Version basique du contrôle des mails
 *
 * Cette version impose des restrictions supplémentaires
 * qui sont souvent utilisées pour des raison de simplification des adresses
 * (ex. comptes utilisateurs lisibles, etc..)
 *
 * @param string $valeur La valeur à vérifier
 * @return boolean Retourne true uniquement lorsque le mail est valide
 */
function verifier_email_de_maniere_stricte($valeur) {
	// Si c'est un spammeur autant arreter tout de suite
	if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $valeur)) {
		spip_log("Tentative d'injection de mail : $valeur");
		return false;
	}
	$adresses = verifier_email_explode_emails($valeur);
	foreach ($adresses as $adresse) {
		// nettoyer certains formats
		// "Marie Toto <Marie@toto.com>"
		$adresse = trim(preg_replace(',^[^<>\"]*<([^<>\"]+)>$,i', '\\1', $adresse));
		if (!preg_match('/^([A-Za-z0-9]){1}([A-Za-z0-9]|-|_|\.)*@[A-Za-z0-9]([A-Za-z0-9]|-|\.){1,}\.[A-Za-z]{2,18}$/', $adresse)) {
			return false;
		}
		if (strpos($adresse, '.@') !== false) {
			return false;
		}
		if (strpos($adresse, '..') !== false) {
			return false;
		}
		// Vérifier qu'avant le @ on 64 charactère ou moins
		$explode = explode('@', $adresse);
		if (strlen($explode[0]) > 64) {
			return false;
		}
	}
	return true;
}

/**
 * Vérifier que le courriel à tester n'est pas
 * déjà utilisé dans la table spip_auteurs
 *
 * @param string $valeur La valeur à vérifier
 * @return boolean Retourne false lorsque le mail est déjà utilisé
 */
function verifier_disponibilite_email($valeur, $exclure_id_auteur = null) {
	include_spip('base/abstract_sql');

	if (sql_getfetsel('id_auteur', 'spip_auteurs', 'email=' . sql_quote(trim($valeur)) . (!is_null($exclure_id_auteur) ? "AND statut<>'5poubelle' AND id_auteur<>" . intval($exclure_id_auteur) : ''))) {
		return false;
	} else {
		return true;
	}
}
