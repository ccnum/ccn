<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/cioidc_commun');

function cioidc_liste_serveurs() {
	$return = '';

	$ciedit = true;
	cioidc_charger_fichier_parametrage();
	if (defined('_CIOIDC_URL_SERVEUR')) {
		if (cioidc_filtrer_url_serveur(_CIOIDC_URL_SERVEUR, true)) {
			$ciedit = false;
		}
	}

	$serveurs = cioidc_lire_serveurs_additionnels();

	$res = '';
	foreach ($serveurs as $id_serveur => $serveur) {
		$res .= '<li>N° ' . intval($id_serveur) . ' : <a href="' . generer_url_ecrire('cioidc_serveur', 'id_serveur=' . intval($id_serveur)) . '">' . interdire_scripts($serveur['nom_serveur']) . '</a></li>';
	}

	if ($res) {
		$return .= '<ul>' . $res . '</ul>';
	} elseif ($ciedit) {
		$return .= _T('cioidc:aucun_serveur');
	} else {
		$return .= _T('cioidc:aucun_serveur_fichier_param');
	}

	if ($ciedit) {
		$return .= "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		$return .= '<tr>';
		$return .= "<td width='10%'>";
		$return .= icone_verticale(_T('cioidc:titre_creer_serveur'), generer_url_ecrire('cioidc_serveur', 'new=oui'), 'article-24.png', 'creer.gif');
		$return .= "</td><td width='90%'>";
		$return .= '</td></tr></table>';
	}

	return $return;
}
