<?php

/* Un mécanisme d'authentification qui ne vérifie rien, à part
   l'autorisation 'incarner' */
function auth_incarner_dist($login, $password, $serveur = '', $phpauth = false) {

	$login = trim((string) $login);
	// login absent, n'allons pas plus loin
	if (!$login) {
		return [];
	}

	// login inconnu, restons en là
	$row = sql_fetsel(
		'*',
		'spip_auteurs',
		'login=' . sql_quote($login, $serveur, 'text') .
										" AND statut<>'5poubelle'",
		'',
		'',
		'',
		'',
		$serveur
	);

	if (!$row) {
		spip_log("Manque auteur '$login'", 'incarner' . _LOG_INFO_IMPORTANTE);
		return [];
	}

	include_spip('inc/autoriser');
	include_spip('incarner_pipelines');

	// Est-ce un rollback ?
	// Si on veut retrouver son login d'origine, pas besoin de verifier l'autorisation
	// car si A s'est incarne en B, il n'est pas certain que B soit pour autant autoriser a se loger en A
	// il n'y a pas forcement symetrie
	// de plus, on ne genere pas de nouvelle cle dans ce cas
	if ($id_auteur = incarner_racine_incarnation() and intval($row['id_auteur']) === $id_auteur) {
		return $row;
	}

	// sinon on verifie les droits et on attribue une nouvelle cle si c'est OK
	if (autoriser('incarner', 'auteur', $row['id_auteur'])) {
		return $row;
	}

	// NIET
	return [];
}
