<?php

/**
 * Options du plugin Incarner au chargement
 *
 * @plugin     Incarner
 * @copyright  2013
 * @author     Michel Bystranowski
 * @licence    GNU/GPL
 * @package    SPIP\Incarner\Options
 */

$GLOBALS['liste_des_authentifications']['incarner'] = 'incarner';

/**
 * teste si l'internaute actuellement connecté est un admin incarné
 *
 * @return int      id_auteur de l'admin actuellement incarné, ou 0 sinon
 */
function incarner_racine_incarnation() {
	if (
		(! isset($_COOKIE['spip_cle_incarner']))
		or (! $cle_actuelle = $_COOKIE['spip_cle_incarner'])
	) {
		return 0;
	}

	include_spip('incarner_fonctions');
	if (!$id_auteur = incarner_cle_valide($cle_actuelle)) {
		return 0;
	}

	if (
		empty($GLOBALS['visiteur_session']['id_auteur'])
		or intval($GLOBALS['visiteur_session']['id_auteur']) === $id_auteur
	) {
		return 0;
	}

	return intval($id_auteur);
}

/**
 * Ajouter un lien dans côté public pour redevenir webmestre
 *
 * @pipeline formulaire_admin
 * @param  array $html Données du pipeline
 * @return array       Données du pipeline
 */
function incarner_affichage_final($html) {

	$id_auteur = incarner_racine_incarnation();
	if (!$id_auteur) {
		return $html;
	}

	include_spip('base/abstract_sql');
	$auteur = sql_fetsel('login,email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));

	$self = self();
	include_spip('inc/actions');
	include_spip('inc/filtres');

	$url_rollback = generer_action_auteur('incarner', 'rollback', $self);
	$url_logout = generer_action_auteur('incarner', 'logout', $self);

	$login_aff = incarner_login_affiche($auteur['login'], $auteur['email']);
	$quisuisje = incarner_login_affiche(session_get('login'), session_get('email'));

	$lien = '<div class="menu-incarner' . (test_espace_prive() ? ' prive' : '') . '">';
	$lien .= '<a class="bouton-incarner" href="' . $url_logout . '">';
	$lien .= _T('incarner:logout_definitif');
	$lien .= '</a>';
	$invite = _T('incarner:reset_incarner', ['login' => $login_aff]);
	$lien .= '<a class="bouton-incarner" href="' . $url_rollback . '" title="' . attribut_html(
		$quisuisje . ' : ' . $invite
	) . '">';
	$lien .= $invite;
	$lien .= '</a>';
	$lien .= '</div>';

	$html = preg_replace('#(</body>)#', $lien . '$1', $html);
	return $html;
}

/**
 * @return array
 */
function incarner_affichage_final_prive($html) {

	return incarner_affichage_final($html);
}
