<?php

/**
 * Pipelines du plugin Incarner
 *
 * @plugin     Incarner
 * @copyright  2016
 * @author     Michel Bystranowski
 * @licence    GNU/GPL
 */

/**
 * Afficher un lien pour incarner un auteur sur sa page
 *
 * @pipeline affiche_gauche
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function incarner_boite_infos($flux) {
	if (
		$flux['args']['type'] === 'auteur'
		and isset($flux['args']['id'])
		and $id_auteur = $flux['args']['id']
		and autoriser('incarner', 'auteur', $id_auteur)
	) {
		include_spip('incarner_fonctions');
		include_spip('inc/session');
		include_spip('base/abstract_sql');

		if ($id_auteur != session_get('id_auteur')) {
			$auteur = sql_fetsel('login,email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
			$login_aff = incarner_login_affiche($auteur['login'], $auteur['email']);
			if ($login_aff) {
				// on ne peut pas emboiter les incarnations : il faut retourner a sa session d'origine et incarner la nouvelle personne
				if (!incarner_racine_incarnation()
				  and $auteur_login_possible = auth_identifier_login($auteur['login'], '')
				  and is_array($auteur_login_possible)) {
					include_spip('inc/actions');
					$url_action = generer_action_auteur('incarner', 'login:' . $auteur['login'], self());
					$disabled = '';
				} else {
					$url_action = '#';
					$disabled = 'disabled';
				}

				$contexte = [
					'url' => $url_action,
					'texte' => _T('incarner:incarner_login', ['login' => $login_aff]),
					'disable' => $disabled,
				];

				$fond_previsu = recuperer_fond('prive/squelettes/inclure/inc-incarner_bouton', $contexte);
				$flux['data'] .= $fond_previsu;
			}
		}
	}

	return $flux;
}

/**
 * Ajoute une feuille de styles à l'espace public
 *
 * @pipeline insert_head
 * @param  string $flux Données du pipeline
 * @return string       Données du pipeline
 */
function incarner_insert_head($flux) {

	$flux .= '<link rel="stylesheet" type="text/css" href="' . timestamp(find_in_path('css/incarner.css')) . '" />';

	return $flux;
}

function incarner_header_prive($flux) {

	return incarner_insert_head($flux);
}

/**
 * Ajoute le bouton d'administration permettant d'incarner l'auteur de l'objet courant sur une page publique
 *
 * @pipeline formulaire_admin
 * @param array $flux Données du pipeline
 * @return array       Données du pipeline
 *
 * @uses _INCARNER_OBJET_ID_OBJET_COURANT
 *      Au format "objet|id_objet" = indique de quels objets on peut incarner l'auteur
 */
function incarner_formulaire_admin($flux) {

	if (!defined('_INCARNER_OBJET_ID_OBJET_COURANT')
		or empty($GLOBALS['visiteur_session']['id_auteur']) // utile lorsqu'on déconnecté mais que le cookie de correspondance est encore là
		or !isset($flux['args']['contexte']['objet'])
		or !($objet = $flux['args']['contexte']['objet'])
		or !isset($flux['args']['contexte']['id_objet'])
		or !($id_objet = $flux['args']['contexte']['id_objet'])
	) {
		return $flux;
	}
	[$incarner_objet, $incarner_objet_id] = explode('|', _INCARNER_OBJET_ID_OBJET_COURANT);
	$id_objet_courant = intval(_request($incarner_objet_id));
	if ($objet != $incarner_objet) {
		return $flux;
	}

	include_spip('base/abstract_sql');
	$auteur = sql_fetsel(
		'A.id_auteur, A.login',
		'spip_auteurs as A, spip_auteurs_liens as L',
		['A.id_auteur=L.id_auteur', 'L.objet=' . sql_quote($objet), 'L.id_objet=' . intval($id_objet_courant)]
	);
	if (!$auteur or !autoriser('incarner', 'auteur', $auteur['id_auteur'])) {
		return $flux;
	}
	$login_objet_courant = $auteur['login'];
	$login = session_get('login');
	if ($login == $login_objet_courant) {
		return $flux;
	}

	include_spip('inc/actions');
	$url_courant = generer_action_auteur('incarner', 'login:' . $login_objet_courant, self());

	$lien = '<a class="bouton-incarner" href="' . $url_courant . '">';
	$lien .= _T('incarner:incarner_login', ['login' => $login_objet_courant]);
	$lien .= '</a>';

	$x = '<!--extra-->';
	$flux['data'] = str_ireplace($x, $lien . $x, $flux['data']);
	return $flux;
}
