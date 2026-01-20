<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

/**
 * Pipeline d'aiguillage entre les modes d'authentification
 *
 * @param array $flux
 * @return array
 */
function cioidc_recuperer_fond($flux) {

	// Si le plugin cicas est actif, il faut suspendre le fonctionnement de cioidc
	if ($flux['args']['fond'] == 'formulaires/login' && !defined('_DIR_PLUGIN_CICAS')) {
		// ajout d'une securite anti BOT (via la constante _CIOIDC_ANTI_BOT)
		include_spip('inc/cioidc_commun');
		cioidc_charger_fichier_parametrage();
		
		if (defined('_CIOIDC_ANTI_BOT') && _CIOIDC_ANTI_BOT == 'oui' && _IS_BOT) {
			include_spip('inc/headers');
			redirige_par_entete(generer_url_public('404'));
		} else {
			$cioidc_mode_auth = cioidc_mode_auth();

			// il faut que le plugin soit configuré
			if ($config_oidc = cioidc_configuration_serveur_oidc()) {
				$self = self();
				$replace = '';

				// authentification CIOIDC
				if ($cioidc_mode_auth == 'oidc') {
					// authentification CIOIDC demandee par un clic sur le lien
					if (_request('cioidc') && (_request('cioidc') == 'oui' || intval(_request('cioidc') >= 1))) {
						include_spip('inc/cioidc_login');
					} else {
						$cioidc_test_agentconnect = cioidc_test_agentconnect($config_oidc['url_serveur']);
						$cioidc_test_proconnect = cioidc_test_proconnect($config_oidc['url_serveur']);
						$cioidc_test_franceconnect = cioidc_test_franceconnect($config_oidc['url_serveur']);

						if (cioidc_nombre_serveurs_additionnels() >= 1) {
							$titre = '<h2 class="cioidc_h2">' . cioidc_titre_se_connecter() . '</h2>';

							$lien = parametre_url($self, 'cioidc', 'oui');
							$replace = '<li>' . cioidc_html_lien_serveur($lien, $config_oidc['url_serveur'], $config_oidc['nom_serveur'], 'cioidc_lien_serveur') . '</li>';

							// serveurs additionnels
							$serveurs = cioidc_lire_serveurs_additionnels();
							foreach ($serveurs as $id_serveur => $serveur) {
								$lien = parametre_url($self, 'cioidc', intval($id_serveur));
								$replace .= '<li><p class="cioidc_ou">ou</p>' . cioidc_html_lien_serveur($lien, $serveur['url_serveur'], $serveur['nom_serveur'], 'cioidc_lien_serveur') . '</li>';
							}

							$replace = '<div class="cioidc_choix_serveur">' . $titre . '<ul class="cioidc_choix_serveur_liste">' . $replace . '</ul></div>';
						} elseif ($cioidc_test_agentconnect || $cioidc_test_proconnect || $cioidc_test_franceconnect) {
							$lien = parametre_url($self, 'cioidc', 'oui');
							$replace = cioidc_html_lien_serveur($lien, $config_oidc['url_serveur'], '', '');
							$replace = '<div class="cioidc_choix_serveur">' . $replace . '</div>';
						} else {
							include_spip('inc/cioidc_login');
						}

						if ($replace) {
							$flux['data']['texte'] = $replace;
						}
					}

					// authentification hybride CIOIDC et SPIP
				} elseif ($cioidc_mode_auth == 'hybride') {
					// authentification SPIP demandee par un clic sur le lien
					if (_request('cioidc') && _request('cioidc') == 'spip') {
						// laisser passer le formulaire de login de SPIP
						// authentification CIOIDC demandee par un clic sur le lien
					} elseif (_request('cioidc') && (_request('cioidc') == 'oui' || intval(_request('cioidc') >= 1))) {
						include_spip('inc/cioidc_login');
					} else {
						// ajout du lien vers l'authentification CIOIDC
						if (cioidc_nombre_serveurs_additionnels() >= 1) {
							$lien = parametre_url($self, 'cioidc', 'oui');
							$replace .= '<li>' . cioidc_html_lien_serveur($lien, $config_oidc['url_serveur'], $config_oidc['nom_serveur'], 'cioidc_lien_serveur') . '</li>';

							// serveurs additionnels
							$serveurs = cioidc_lire_serveurs_additionnels();
							foreach ($serveurs as $id_serveur => $serveur) {
								$lien = parametre_url($self, 'cioidc', intval($id_serveur));
								$replace .= '<li><p class="cioidc_ou">ou</p>' . cioidc_html_lien_serveur($lien, $serveur['url_serveur'], $serveur['nom_serveur'], 'cioidc_lien_serveur') . '</li>';
							}
						} else {
							$lien = parametre_url($self, 'cioidc', 'oui');
							$replace .= '<li>' . cioidc_html_lien_serveur($lien, $config_oidc['url_serveur'], $config_oidc['nom_serveur'], 'cioidc_lien_serveur') . '</li>';
						}

						$lien = parametre_url($self, 'cioidc', 'spip');
						$intitule_auth_spip = cioidc_nom_serveur_spip();
						$replace .= '<li><p class="cioidc_ou">ou</p>' . cioidc_html_lien_serveur($lien, '', $intitule_auth_spip, 'cioidc_lien_serveur') . '</li>';

						$titre = '<h2 class="cioidc_h2">' . cioidc_titre_se_connecter() . '</h2>';
						$replace = '<div class="cioidc_choix_serveur">' . $titre . '<ul class="cioidc_choix_serveur_liste">' . $replace . '</ul></div>';
						$flux['data']['texte'] = $replace;
					}
				}
			}
		}
	}

	return $flux;
}

/**
 * Anti hack
 *
 * @param array $tableau
 * @return array
 */
function cioidc_pre_edition($flux) {

	if (
			isset($flux['args']['action'])
			&& $flux['args']['action'] == 'modifier'
			&& isset($flux['args']['table']) && $flux['args']['table'] == 'spip_auteurs'
	) {
		include_spip('inc/cioidc_commun');
		cioidc_charger_fichier_parametrage();
		
		if (defined('_CIOIDC_ANTI_HACK') && _CIOIDC_ANTI_HACK == 'oui' && !defined('_DIR_PLUGIN_CICAS')) {
		// on ne doit pas pouvoir remplacer l'identifiant SSO (email ou login) d'un autre auteur avec son propre identifiant SSO (email ou login)
			$id_auteur = intval($flux['args']['id_objet']);
			if ($id_auteur > 0) {
				include_spip('inc/texte');
				$row = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $id_auteur);
				if ($row) {
					// Lire la configuration du plugin pour la session
					$tableau_uid_champ_spip = cioidc_tableau_uid_champ_spip();

					$mon_id_auteur = (isset($GLOBALS['visiteur_session']['id_auteur']) ? $GLOBALS['visiteur_session']['id_auteur'] : '');

					// modifier un autre auteur
					if ($id_auteur != $mon_id_auteur) {
						if (in_array('login', $tableau_uid_champ_spip)) {
							$old_login = $row['login'];
							$new_login = strtolower((isset($flux['data']['login']) ? $flux['data']['login'] : ''));

							// modifier l'identifiant SSO (login) d'un autre auteur
							if ($new_login != $old_login) {
								$mes_logins = [];
								if (isset($GLOBALS['visiteur_session']['login']) && trim($GLOBALS['visiteur_session']['login'])) {
									$mes_logins[] = $GLOBALS['visiteur_session']['login'];
								}

								// compatibilite avec mes anciens logins
								$tableau_memo = cioidc_sso_memo($mon_id_auteur, 'login');
								foreach ($tableau_memo as $valeur) {
									if (trim($valeur)) {
										$mes_logins[] = $valeur;
									}
								}

								if (in_array($new_login, $mes_logins)) {
									$flux['data']['login'] = $old_login;
								}

								// Pas de contrôle d'unicité du car SPIP s'en charge
							}
						}

						if (in_array('email', $tableau_uid_champ_spip)) {
							$old_email = $row['email'];
							$new_email = strtolower((isset($flux['data']['email']) ? $flux['data']['email'] : ''));

							// modifier l'identifiant SSO (email) d'un autre auteur
							if ($new_email && $new_email != $old_email) {
								$mes_emails = [];
								if (isset($GLOBALS['visiteur_session']['email']) && trim($GLOBALS['visiteur_session']['email'])) {
									$mes_emails[] = $GLOBALS['visiteur_session']['email'];
								}
								// compatibilite avec mes anciennes adresses email
								$tableau_memo = cioidc_sso_memo($mon_id_auteur, 'email');
								foreach ($tableau_memo as $valeur) {
									if (trim($valeur)) {
										$mes_emails[] = $valeur;
									}
								}

								if (in_array($new_email, $mes_emails)) {
									$flux['data']['email'] = $old_email;
								} else {
									// compatibilite avec les anciennes adresses email
									$new_tableau_email = explode('@', $new_email);
									$new_nom_mail = strtolower($new_tableau_email[0]);
									$new_domaine_mail = strtolower($new_tableau_email[1]);

									$cioidc_mail_compatible = cioidc_mail_compatible();

									foreach ($mes_emails as $mon_email) {
										$mon_tableau_email = explode('@', $mon_email);
										$mon_nom_mail = strtolower($mon_tableau_email[0]);
										$mon_domaine_mail = strtolower($mon_tableau_email[1]);

										if ($new_nom_mail == $mon_nom_mail) {
											foreach ($cioidc_mail_compatible as $cle => $valeur) {
												if (($mon_domaine_mail == $valeur && $new_domaine_mail == $cle) || ($mon_domaine_mail == $cle && $new_domaine_mail == $valeur)) {
													$flux['data']['email'] = $old_email;
													break;
												}
											}
										}
									}
								}

								// Controle d'unicite de l'email
								// Remarque : SPIP permet de le faire (via une constante)
								// mais il ne prend pas en compte les adresses email avec un ancien domaine
								if (!cioidc_unicite_email($new_email, $id_auteur)) {
									$flux['data']['email'] = $old_email;
								}
							}
						}
					} else {
						// se modifier soi meme
						$memo = '';
						if (in_array('login', $tableau_uid_champ_spip)) {
							$old_login = $row['login'];
							$new_login = strtolower((isset($flux['data']['login']) ? $flux['data']['login'] : ''));

							// modifier son propre identifiant SSO (login)
							if ($old_login && $new_login != $old_login) {
								// si administrateur du site
								if (autoriser('configurer')) {
									cioidc_ecrire_memo($mon_id_auteur, $old_login, 'login');
								} else {
									$flux['data']['login'] = $old_login;
								}
							}
						}

						if (in_array('email', $tableau_uid_champ_spip)) {
							$old_email = $row['email'];
							$new_email = strtolower((isset($flux['data']['email']) ? $flux['data']['email'] : ''));
							// modifier son propre identifiant SSO (email)
							if ($old_email && $new_email != $old_email) {
								// si administrateur du site
								if (autoriser('configurer')) {
									// controle d'unicite de l'email
									if (!cioidc_unicite_email($new_email, $id_auteur)) {
										$flux['data']['email'] = $old_email;
									} else {
										cioidc_ecrire_memo($mon_id_auteur, $old_email, 'email');
									}
									// sinon interdire de modifier son email
								} else {
									$flux['data']['email'] = $old_email;
								}
							}
						}
					}
				}
			}
		}
	}

	return $flux;
}

/**
 * Mémorise dans un fichier de log
 * une modification de son propre email ou de son propre login
 * effectuée dans le formulaire de saisie des auteurs de SPIP
 *
 * @param int $id_auteur
 * @param string $id_sso (l'ancienne valeur)
 * @param string $cioidc_uid (nom du champ de SPIP : 'email' ou 'login')
 */
function cioidc_ecrire_memo($id_auteur, $id_sso, $cioidc_uid = 'email') {

	if ($id_auteur && $id_sso) {
		$memo = $id_auteur . '/' . $cioidc_uid . ':' . strtolower($id_sso) . '|';

		$fichier = cioidc_fichier_memo();
		$option_fopen = 'ab';

		// supprimer le fichier s'il est trop gros
		$taille_max = ( (defined('_CIOIDC_TAILLE_MAX') && intval(_CIOIDC_TAILLE_MAX) > 0) ? intval(_CIOIDC_TAILLE_MAX) : 150); // en Ko
		if (@file_exists($fichier) && @filesize($fichier) > ($taille_max * 1024)) {
			$option_fopen = 'w';
		}
		$f = @fopen($fichier, $option_fopen);
		if ($f) {
			fputs($f, $memo);
			fclose($f);
		}
	}
}

/**
 * Lecture du fichier de log
 *
 * @return array
 */
function cioidc_lire_memo() {
	$return = [];
	$fichier = cioidc_fichier_memo();

	$handle = @fopen($fichier, 'rb');
	if ($handle) {
		$contenu = fread($handle, filesize($fichier));
		fclose($handle);
		if (substr($contenu, -1) == '|') {
			$contenu = substr($contenu, 0, -1);
		}
		$return = explode('|', $contenu);
	}
	return $return;
}

/**
 * Recherche dans le fichier de log
 *
 * @param int $id_auteur
 * @param string $cioidc_uid (nom du champ de SPIP : 'email' ou 'login')
 * @return array
 */
function cioidc_sso_memo($id_auteur, $cioidc_uid = 'email') {
	$return = [];
	if ($id_auteur = intval($id_auteur)) {
		$cherche = $id_auteur . '/' . $cioidc_uid . ':';
		$longeur = strlen($cherche);
		$tableau_memo = cioidc_lire_memo();
		foreach ($tableau_memo as $valeur) {
			if (substr($valeur, 0, $longeur) == $cherche) {
				$return[] = substr($valeur, $longeur);
			}
		}
	}

	return $return;
}

/**
 * Chemin du fichier de log
 *
 * @return string
 */
function cioidc_fichier_memo() {

	$memo_nom = 'cioidc_memo';
	$memo_suffix = '.txt';
	$memo_dir = ((defined('_DIR_LOG') && defined('_DIR_PLUGIN_CIMS')) ? _DIR_LOG : _DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES);
	if (defined('_DIR_PLUGIN_CIMS') && function_exists($f_racine = 'cims_racine_dossier')) {
		$memo_dir = $f_racine(_NOM_TEMPORAIRES_INACCESSIBLES) . _NOM_TEMPORAIRES_INACCESSIBLES;
	}

	if (defined('_CIOIDC_REPERTOIRE') && _CIOIDC_REPERTOIRE) {
		$repertoire = _CIOIDC_REPERTOIRE;
		// securite
		if (
				(strpos($repertoire, '../') === false)
				&& !(preg_match(',^\w+://,', $repertoire))
		) {
			if (substr($repertoire, 0, 1) == '/') {
				$repertoire = substr($repertoire, 1);
			}
			if (substr($repertoire, -1) == '/') {
				$repertoire = substr($repertoire, 0, -1);
			}
			$ci_dir_racine = substr(_DIR_IMG, 0, -strlen(_NOM_PERMANENTS_ACCESSIBLES));
			if (is_dir($ci_dir_racine . $repertoire)) {
				$memo_dir = $ci_dir_racine . $repertoire . '/';
			}
		}
	}

	$fichier_memo = $memo_dir . $memo_nom . $memo_suffix;
	
	return $fichier_memo;
}

/**
 * Vérifie que le nouvel email ne figure pas déjà
 * dans la table des auteurs de SPIP
 *
 * @param string $new_email
 * @param int $id_auteur
 * @return boolean
 */
function cioidc_unicite_email($new_email, $id_auteur) {
	$return = true;

	if ($new_email && $id_auteur = intval($id_auteur)) {
		// controle d'unicite de l'email
		if (sql_countsel('spip_auteurs', "email='" . $new_email . "' AND id_auteur<>" . $id_auteur) > 0) {
			$return = false;
		}

		if ($return) {
			// compatibilite avec les anciennes adresses email
			$cioidc_mail_compatible = cioidc_mail_compatible();

			$mon_tableau_email = explode('@', $new_email);
			$mon_nom_mail = strtolower($mon_tableau_email[0]);
			$mon_domaine_mail = strtolower($mon_tableau_email[1]);

			foreach ($cioidc_mail_compatible as $cle => $valeur) {
				if (sql_countsel('spip_auteurs', "(email='" . $mon_nom_mail . '@' . $cle . "' OR email='" . $mon_nom_mail . '@' . $valeur . "') AND id_auteur<>" . $id_auteur) > 0) {
					$return = false;
					break;
				}
			}
		}
	}
	return $return;
}

/**
 * Un rédacteur ou un admin restreint ne doit pas pouvoir modifier son propre email
 * si CIOIDC utilise l'email comme identifiant (or SPIP 4.0 le permet)
 *
 * @param array $flux
 * @return array
 */
function cioidc_formulaire_verifier($flux) {
	// Remarque : id_auteur figure dans $flux['args']['args'][0]
	if ($GLOBALS['spip_version_branche'] >= 4 && !defined('_DIR_PLUGIN_CICAS')) {
		if (
				isset($flux['args']['form'])
				&& $flux['args']['form'] == 'editer_auteur'
				&& isset($flux['args']['args'][0])
				&& $flux['args']['args'][0]
		) {
			if (
					isset($GLOBALS['visiteur_session']['id_auteur'])
					&& $GLOBALS['visiteur_session']['id_auteur'] == $flux['args']['args'][0]
					&& isset($GLOBALS['visiteur_session']['statut'])
			) {
				if (
						$GLOBALS['visiteur_session']['statut'] == '1comite'
						|| ($GLOBALS['visiteur_session']['statut'] == '0minirezo' && liste_rubriques_auteur($GLOBALS['visiteur_session']['id_auteur']))
				) {
					// email modifié ?
					$old_email = '';
					$new_email = _request('email');
					$row = sql_fetsel('email', 'spip_auteurs', 'id_auteur=' . intval($flux['args']['args'][0]));
					if ($row) {
						$old_email = $row['email'];
					}

					if ($new_email != $old_email) {
						// Si CIOIDC utilise l'email comme identifiant
						include_spip('inc/cioidc_commun');
						$tableau_config = cioidc_lire_meta(0, false, true);

						if (
								!isset($tableau_config['uid_champ_spip'])
								|| $tableau_config['uid_champ_spip'] == ''
								|| $tableau_config['uid_champ_spip'] == 'email'
						) {
							$flux['data'] = ['email' => _T('cioidc:email_non_modifiable')];
						}
					}
				}
			}
		}
	}

	return $flux;
}

/**
 * Ajoute la feuille de style pour le choix (par l'utilisateur) entre plusieurs serveurs
 * et pour le bouton agentconnect ou franceconnect
 *
 * @param string $flux
 * @return string
 */
function cioidc_insert_head_css($flux) {
	if ($GLOBALS['spip_version_branche'] >= 4) {
		$css = direction_css(find_in_path('_css/cioidc2.css'), lang_dir());
	} else {
		$css = direction_css(find_in_path('_css/cioidc_spip3.css'), lang_dir());
	}
	$flux .= '<link rel="stylesheet" href="' . $css . '" type="text/css" />';
	return $flux;
}
