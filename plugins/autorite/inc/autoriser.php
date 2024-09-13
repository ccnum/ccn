<?php

// Ceci est une surcharge de inc/autoriser.php
//
// Voir le fichier formulaires/configurer_autorite.html pour la definition des reglages

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}

if (!defined('_DEBUG_AUTORISER')){
	define('_DEBUG_AUTORISER', false);
}


$GLOBALS['autorite'] = [];
if (!empty($GLOBALS['meta']['autorite'])) {
	$GLOBALS['autorite'] = unserialize($GLOBALS['meta']['autorite']);
}

$GLOBALS['autorite'] = ($GLOBALS['autorite'] ?: []);
$autorite_erreurs = [];

//
// Les DEFINE
//
if (isset($GLOBALS['autorite']['statut_auteur_creation'])){
	if (defined('_STATUT_AUTEUR_CREATION')){
		$autorite_erreurs[] = 'statut_auteur_creation';
	} else {
		switch ($GLOBALS['autorite']['statut_auteur_creation']) {
			case 'visiteur':
				if (!defined('_STATUT_AUTEUR_CREATION')){
					define('_STATUT_AUTEUR_CREATION', '6forum');
				}
				break;
			case 'redacteur':
				if (!defined('_STATUT_AUTEUR_CREATION')){
					define('_STATUT_AUTEUR_CREATION', '1comite');
				}
				break;
			case 'admin':
				if (!defined('_STATUT_AUTEUR_CREATION')){
					define('_STATUT_AUTEUR_CREATION', '0minirezo');
				}
				break;
		}
	}
}

if (isset($GLOBALS['autorite']['statut_auteur_rubrique'])){
	if (defined('_STATUT_AUTEUR_RUBRIQUE')){
		$autorite_erreurs[] = 'statut_auteur_rubrique';
	} else {
		switch ($GLOBALS['autorite']['statut_auteur_rubrique']) {
			case '1':
				if (!defined('_STATUT_AUTEUR_RUBRIQUE')){
					define('_STATUT_AUTEUR_RUBRIQUE', '0minirezo,1comite');
				}
				break;
			case '2':
				if (!defined('_STATUT_AUTEUR_RUBRIQUE')){
					define('_STATUT_AUTEUR_RUBRIQUE', '0minirezo,1comite,6forum');
				}
				break;
		}
	}
}

if (isset($GLOBALS['autorite']['statut_ignorer_admins_restreints']) and $GLOBALS['autorite']['statut_ignorer_admins_restreints']=='oui'){
	if (defined('_ADMINS_RESTREINTS')){
		$autorite_erreurs[] = 'ignorer_admins_restreints';
	} else {
		define('_ADMINS_RESTREINTS', false);
	}
}


// Charger les versions *_dist des fonctions
include_once _DIR_RESTREINT . 'inc/autoriser.php';
// si ca n'a pas ete fait et que l'on est dans une version ancienne de spip
// definir _ID_WEBMESTRES
// ceci semble inutile a partir de spip 3.2


//
// Les FONCTIONS
//

##
## une fonction qui gere les droits publieurs
##

if (isset($GLOBALS['autorite']['espace_publieur'])){
	if (!function_exists('autorisation_publie_visiteur')){
		function autorisation_publie_visiteur($qui, $id_secteur){
			// espace publieur est un array(secteur1, secteur2), ou un id_secteur
			if (
				(is_array($GLOBALS['autorite']['espace_publieur'])
					and !in_array($id_secteur, $GLOBALS['autorite']['espace_publieur']))
				and
				$id_secteur!=$GLOBALS['autorite']['espace_publieur']
			){
				return false;
			}

			switch ($qui['statut']) {
				case '0minirezo':
					break;
				case '1comite':
					if (isset($GLOBALS['autorite']['espace_publieur_redacteurs'])){
						return true;
					}
					break;
				case '6forum':
					if (isset($GLOBALS['autorite']['espace_publieur_visiteurs'])){
						return true;
					}
					break;
			}
			return false;
		}
	} else {
		$autorite_erreurs[] = 'autorisation_publie_visiteur';
	}
}

##
## une fonction qui gere les droits wiki
##
if (isset($GLOBALS['autorite']['espace_wiki'])){
	if (!function_exists('autorisation_wiki_visiteur')){
		function autorisation_wiki_visiteur($qui, $id_secteur){
			// espace_wiki est un array(secteur1, secteur2), ou un id_secteur
			if (
				(is_array($GLOBALS['autorite']['espace_wiki'])
					and !in_array($id_secteur, $GLOBALS['autorite']['espace_wiki']))
				and
				$id_secteur!=$GLOBALS['autorite']['espace_wiki']
			){
				return false;
			}

			switch ($qui['statut']) {
				case '0minirezo':
				case '1comite':
					if (isset($GLOBALS['autorite']['espace_wiki_redacteurs'])){
						return true;
					}
					break;
				case '6forum':
					if (isset($GLOBALS['autorite']['espace_wiki_visiteurs'])){
						return true;
					}
					break;
				default:
					if (isset($GLOBALS['autorite']['espace_wiki_anonyme'])){
						return true;
					}
					break;
			}
			return false;
		}
	} else {
		$autorite_erreurs[] = 'autorisation_wiki_visiteur';
	}
}


##
## une fonction qui gere les droits wiki par mot clef
##
if (isset($GLOBALS['autorite']['espace_wiki_motsclef'])){
	if (!function_exists('autorisation_wiki_motsclef_visiteur')){
		function autorisation_wiki_motsclef_visiteur($qui, $id_article){

			//determine les mots clef affectés à l'article
			$array_mot = sql_allfetsel('id_mot', 'spip_mots_liens', "objet='article' AND id_objet=" . intval($id_article));
			$array_mot = array_column($array_mot, 'id_mot');

			//aucun mot clef d'affecté à l'article, rien à faire
			if (!$array_mot){
				return false;
			}

			//vérification que l'article possède un mot clef correspondant au staut du visiteur
			switch ($qui['statut']) {
				case '0minirezo':
				case '1comite':
					if (in_array($GLOBALS['autorite']['espace_wiki_motsclef_redacteurs'], $array_mot)){
						return true;
					}
					break;
				case '6forum':
					if (in_array($GLOBALS['autorite']['espace_wiki_motsclef_visiteurs'], $array_mot)){
						return true;
					}
					break;
				default:
					if (in_array($GLOBALS['autorite']['espace_wiki_motsclef_anonyme'], $array_mot)){
						return true;
					}
					break;
			}
			return false;
		}
	} else {
		$autorite_erreurs[] = 'autorisation_wiki_motsclef_visiteur';
	}
}


##
## autoriser_article_modifier
##
if (
	isset($GLOBALS['autorite']['auteur_mod_article'])
	or isset($GLOBALS['autorite']['espace_wiki'])
	or isset($GLOBALS['autorite']['espace_wiki_motsclef'])
	or isset($GLOBALS['autorite']['redacteur_mod_article'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_article_modifier')){
		function autoriser_article_modifier($faire, $type, $id, $qui, $opt){

			$r = sql_fetsel("id_rubrique,id_secteur,statut", "spip_articles", "id_article=" . intval($id));
			if (!$r) {
				return false;
			}
			include_spip('inc/auth');
			$a = false;
			if (!isset($GLOBALS['autorite']['espace_publieur'])){
				$a = autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt);
			} else {
				if (!in_array($qui['statut'], array('1comite', '6forum'))){
					$a = autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt);
				}
			}
			return
				$a
				or (
					// Cas du wiki, on appelle la fonction qui verifie les droits wiki
					isset($GLOBALS['autorite']['espace_wiki']) and $GLOBALS['autorite']['espace_wiki']
					and autorisation_wiki_visiteur($qui, $r['id_secteur'])
				)
				or (
					// Cas du wiki par mot clefs, on appelle la fonction qui verifie les droits wiki
					isset($GLOBALS['autorite']['espace_wiki_motsclef']) and $GLOBALS['autorite']['espace_wiki_motsclef']
					and autorisation_wiki_motsclef_visiteur($qui, _q($id))
				)
				or (
					// auteur autorise a modifier son article
					// (sauf si l'article est refuse ou l'auteur mis a la poubelle)
					isset($GLOBALS['autorite']['auteur_mod_article']) and $GLOBALS['autorite']['auteur_mod_article']
					and in_array($qui['statut'],
						array('0minirezo', '1comite', '6forum'))
					and in_array($r['statut'],
						array('publie', 'prop', 'prepa', 'poubelle'))
					and sql_countsel("spip_auteurs_liens", "objet='article' AND id_objet=" . intval($id) . " AND id_auteur=" . intval($qui['id_auteur']))
				)
				or (
					// un redacteur peut-il modifier un article propose ?
					isset($GLOBALS['autorite']['redacteur_mod_article']) and $GLOBALS['autorite']['redacteur_mod_article']
					and in_array($qui['statut'], array('0minirezo', '1comite'))
					and $r['statut']=='prop'
				)
				or (
					// un auteur peut modifier son propre article lorsqu'il est proposé ou en cours de rédaction
					in_array($qui['statut'], array('0minirezo', '1comite'))
					and in_array($r['statut'], array('prop', 'prepa'))
					and sql_countsel("spip_auteurs_liens", "objet='article' AND id_objet=" . intval($id) . " AND id_auteur=" . intval($qui['id_auteur']))
				);
		}

		if (!function_exists('autoriser_rubrique_creerarticledans')){
			function autoriser_rubrique_creerarticledans($faire, $type, $id, $qui, $opt){
				return autoriser_voir_dist($faire, $type, $id, $qui, $opt);
			}
		}
	} else {
		$autorite_erreurs[] = 'autoriser_article_modifier';
	}
}


##
## autoriser_rubrique_publierdans
##
if (
	isset($GLOBALS['autorite']['espace_wiki'])
	or isset($GLOBALS['autorite']['publierdans'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_rubrique_publierdans')){
		function autoriser_rubrique_publierdans($faire, $type, $id, $qui, $opt){

			// Si on est deja autorise en standard, dire 'OK'
			if (
				!isset($GLOBALS['autorite']['publierdans'])
				&& autoriser_rubrique_publierdans_dist($faire, $type, $id, $qui, $opt)
			){
				return true;
			}

			// Verifions qui a le droit
			// 1 : webmestre
			// 2 : admin complet
			// 4 : admin restreint
			// 8 : redacteur
			// cas du redacteur : attention, il faut verifier
			// aussi qu'il est l'auteur de l'objet publie...

			if (($GLOBALS['autorite']['publierdans'] & 1)
				&& autoriser('webmestre', $type, $id, $qui, $opt)){
				return true;
			}
			if (($GLOBALS['autorite']['publierdans'] & 2)
				&& ($qui['statut']=='0minirezo')
				&& (!$qui['restreint'])){
				return true;
			}
			if (($GLOBALS['autorite']['publierdans'] & 4)
				&& ($qui['statut']=='0minirezo')
				&& ($qui['restreint'] and $id and in_array($id, $qui['restreint']))){
				return true;
			}
			// Vérifier que le rédacteur·trice est bien l'auteur·e de l'objet
			if (($GLOBALS['autorite']['publierdans'] & 8)
				&& ($qui['statut']=='1comite')){

				// qui demande le changement de statut ?
				$id_auteur = $qui['id_auteur'];

				// changement pour quel type d'objet?
				$objet = _request('exec');
				$cle_primaire = id_table_objet($objet);
				$id_objet = _request($cle_primaire);

				//  qui est l'auteur de l'objet ?
				$id_auteur_objet = sql_getfetsel('id_auteur', 'spip_auteurs_liens', "objet =" . sql_quote($objet) . " AND id_objet =" . intval($id_objet));

				// on peut vérifier maintenant…
				if ($id_auteur==$id_auteur_objet){
					return true;
				}
			}
			// Sinon, verifier si la rubrique est ouverte aux publieurs
			// et si on est bien enregistre
			if (isset($GLOBALS['autorite']['espace_publieur'])){
				$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id));
				if (autorisation_publie_visiteur($qui, $id_secteur) and ($qui['statut'])){
					return true;
				}
			}
			// Sinon, verifier si la rubrique est wiki
			// et si on est bien enregistre (sauf cas de creation anonyme explicitement autorisee)
			if (isset($GLOBALS['autorite']['espace_wiki'])){
				$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id));
				if (autorisation_wiki_visiteur($qui, $id_secteur)
					and (
						isset($GLOBALS['autorite']['espace_wiki_rubrique_anonyme'])
						or $qui['statut']
					)){
					return true;
				}
			}

			// par defaut, NIET
			return false;
		}
	} else {
		$autorite_erreurs[] = 'autoriser_rubrique_publierdans';
	}
}


##
## autoriser_rubrique_creerrubriquedans
##
if (
	isset($GLOBALS['autorite']['interdire_creer_secteur'])
	or isset($GLOBALS['autorite']['interdire_creer_sousrub'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_rubrique_creerrubriquedans')){
		function autoriser_rubrique_creerrubriquedans($faire, $type, $id, $qui, $opt){
			if ($id==0
				and isset($GLOBALS['autorite']['interdire_creer_secteur'])){
				return
					$GLOBALS['autorite']['interdire_creer_rub_sauf_webmestre']
					and autoriser('webmestre');
			}

			if ($id!=0
				and isset($GLOBALS['autorite']['interdire_creer_sousrub'])){
				return
					$GLOBALS['autorite']['interdire_creer_rub_sauf_webmestre']
					and autoriser('webmestre');
			}

			return
				autoriser_rubrique_creerrubriquedans_dist($faire, $type, $id, $qui, $opt);
		}
	} else {
		$autorite_erreurs[] = 'autoriser_rubrique_creerrubriquedans';
	}
}


##
## autoriser_auteur_modifier
##
if (
	isset($GLOBALS['autorite']['auteur_mod_email']) and (empty($GLOBALS['autorite']['auteur_mod_email']))
){
	if (!function_exists('autoriser_auteur_modifier')){
		function autoriser_auteur_modifier($faire, $type, $id, $qui, $opt){
			if (isset($opt['email'])
				and !empty($GLOBALS['visiteur_session']['id_auteur'])
				and $qui['id_auteur'] === $GLOBALS['visiteur_session']['id_auteur']
				and intval($GLOBALS['visiteur_session']['statut'] !== '0minirezo')
			){
				return false;
			}
			return autoriser_auteur_modifier_dist($faire, $type, $id, $qui, $opt);
		}
	} else {
		$autorite_erreurs[] = 'autoriser_auteur_modifier';
	}
}


##
## autoriser_modererforum
##
if (!function_exists('autoriser_modererforum')){
	function autoriser_modererforum($faire, $type, $id, $qui, $opt){
		return
			($qui['statut']=='0minirezo')
			or (
				$GLOBALS['autorite']['auteur_modere_forum'] ?? null
				and $type=='article'
				and in_array($qui['statut'], array('0minirezo', '1comite'))
				and sql_countsel("spip_auteurs_liens", "objet='article' AND id_objet=" . intval($id) . " AND id_auteur=" . intval($qui['id_auteur']))
			);
	}
}

##
## autoriser_modererpetition
##
if (!function_exists('autoriser_modererpetition')){
	function autoriser_modererpetition($faire, $type, $id, $qui, $opt){
		return
			($qui['statut']=='0minirezo')
			or (
				$GLOBALS['autorite']['auteur_modere_petition']
				and $type=='article'
				and in_array($qui['statut'], array('0minirezo', '1comite'))
				and sql_countsel("spip_auteurs_liens", "objet='article' AND id_objet=" . intval($id) . " AND id_auteur=" . intval($qui['id_auteur']))
			);
	}
}

##
## autoriser_voirstats
##
if (
	isset($GLOBALS['autorite']['redacteurs_lire_stats'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_voirstats')){
		function autoriser_voirstats($faire, $type, $id, $qui, $opt){
			return
				$GLOBALS['autorite']['redacteurs_lire_stats']
					? in_array($qui['statut'], array('0minirezo', '1comite'))
					: $qui['statut']=='0minirezo';
		}
	} else {
		$autorite_erreurs[] = 'autoriser_voirstats';
	}
}

if (!function_exists('autoriser_auteurs_voir')){
	// semble pas testée par spip mais on s'en sert pour les autres
	function autoriser_auteurs_voir($faire, $type, $id, $qui, $opt){
		if (isset($GLOBALS['autorite']['redacteurs_voir_auteurs'])
			and $GLOBALS['autorite']['redacteurs_voir_auteurs']){
			return in_array($qui['statut'], array('0minirezo', '1comite'));
		} else {
			return ($qui['statut']=='0minirezo');
		}
	}
} else {
	$autorite_erreurs[] = 'autoriser_auteurs_voir';
}

if (!function_exists('autoriser_auteurs_menu')){
	// présenter ou non l'entrée "auteurs" dans le menu
	function autoriser_auteurs_menu($faire, $type, $id, $qui, $opt){
		return autoriser_auteurs_voir($faire, $type, $id, $qui, $opt);
	}
} else {
	$autorite_erreurs[] = 'autoriser_auteurs_voir';
}
if (!function_exists('autoriser_auteur_voir')){
	// Accès aux fiches d'infos persos des auteurs
	function autoriser_auteur_voir($faire, $type, $id, $qui, $opt){
		return ($qui['id_auteur']==$id)
			or autoriser_auteurs_voir($faire, $type, $id, $qui, $opt);
	}
} else {
	$autorite_erreurs[] = 'autoriser_auteurs_voir';
}

// Autoriser a modifier un groupe de mots $id
// y compris en ajoutant/modifiant les mots lui appartenant
// https://code.spip.net/@autoriser_groupemots_modifier
##
## autoriser_groupemots_modifier
##
if (
	isset($GLOBALS['autorite']['editer_mots'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_groupemots_modifier')){
		function autoriser_groupemots_modifier($faire, $type, $id, $qui, $opt){
			return (
					$qui['statut']=='0minirezo'
					and (
						!$qui['restreint']
						or
						$GLOBALS['autorite']['editer_mots']>=1
					)
				) or (
					$qui['statut']=='1comite'
					and $GLOBALS['autorite']['editer_mots']>=2
				);
		}

		# signaler un risque de bug avec un autoriser_mot_modifier personnalise
		if (function_exists('autoriser_mot_modifier')){
			$autorite_erreurs[] = 'autoriser_mot_modifier';
		}

		// la config d'autorite sur le groupe de mot doit être idem sur les mots
		function autoriser_mot_modifier($faire, $type, $id, $qui, $opt){
			return autoriser_groupemots_modifier($faire, $type, $id, $qui, $opt);
		}

		function autoriser_mot_creer($faire, $type, $id, $qui, $opt){
			return autoriser_groupemots_modifier($faire, $type, $id, $qui, $opt);
		}

	} else {
		$autorite_erreurs[] = 'autoriser_groupemots_modifier';
	}
}

##
## Modifier un forum ?
## A noter : il n'existe pas d'interface dans SPIP, il faut utiliser les crayons
## TODO : cookie specialise (voir commentaires dans cfg_autorite.html)
##
if (
	isset($GLOBALS['autorite']['editer_forums'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_forum_modifier')){
		function autoriser_forum_modifier($faire, $type, $id, $qui, $opt){

			// Le webmestre
			if (isset($GLOBALS['autorite']['editer_forums']) and $GLOBALS['autorite']['editer_forums']>=1
				and autoriser('webmestre', $type, $id, $qui, $opt)){
				return true;
			}

			// Les admins
			if (
				isset($GLOBALS['autorite']['editer_forums'])
				and $GLOBALS['autorite']['editer_forums']>=2
				and $qui['statut']=='0minirezo'
				and !$qui['restreint']
			){
				return true;
			}

			// Les admins restreint pour les articles attachés à une rubrique dont ils sont admins
			if (isset($GLOBALS['autorite']['editer_forums']) and $GLOBALS['autorite']['editer_forums']>=2 and $qui['statut']=='0minirezo'){
				$id = intval($id); // ?
				if (isset($GLOBALS['spip_version_branche']) and intval($GLOBALS['spip_version_branche'])<3){
					$id_rubrique = sql_getfetsel("id_rubrique", "spip_forum", "id_forum=$id");
					if (!$id_rubrique and ($id_article = sql_getfetsel("id_article", "spip_forum", "id_forum=$id"))){
						$id_rubrique = sql_getfetsel("id_rubrique", "spip_articles", "id_article=$id_article");
					}
					if (!$id_rubrique and ($id_breve = sql_getfetsel("id_breve", "spip_forum", "id_forum=$id"))){
						$id_rubrique = sql_getfetsel("id_rubrique", "spip_breves", "id_breve=$id_breve");
					}
				} else {
					$objet = sql_getfetsel("objet", "spip_forum", "id_forum=$id");
					$id_objet = sql_getfetsel("id_objet", "spip_forum", "id_forum=$id AND objet='$objet'");
					if ($objet=="rubrique"){
						$id_rubrique = $id_objet;
					} else {
						if ($objet=="article"){
							$id_rubrique = sql_getfetsel("id_rubrique", "spip_articles", "id_article=$id_objet");
						} else {
							if ($objet=="breve"){
								$id_rubrique = sql_getfetsel("id_rubrique", "spip_breves", "id_breve=$id_objet");
							}
						}
					}
				}
				return ($id_rubrique and in_array($id_rubrique, $qui['restreint']));
			}

			// L'auteur du message (enregistre')
			// 3 = avec une periode de grace d'une heure
			// 4 = ad vitam
			if (
				isset($GLOBALS['autorite']['editer_forums'])
				and $GLOBALS['autorite']['editer_forums']>=3
				and isset($qui['id_auteur'])
			){
				$where = [
					"id_forum=" . intval($id),
					"id_auteur=" . intval($qui['id_auteur'])
				];
				if ($GLOBALS['autorite']['editer_forums']==3){
					$where[] = "date_heure > " . sql_quote(date('Y-m-d H:i:s', strtotime('-1hour')));
				}
				if (sql_countsel('spip_forum', $where)){
					return true;
				}
			}

			// par defaut
			return autoriser_forum_modifier_dist($faire, $type, $id, $qui, $opt);

		}
	} else {
		$autorite_erreurs[] = 'autoriser_forum_modifier';
	}
}

##
## Modifier une signature ?
## A noter : il n'existe pas d'interface dans SPIP, il faut utiliser les crayons
## TODO : cookie specialise (voir commentaires dans cfg_autorite.html)
##
if (
	isset($GLOBALS['autorite']['editer_signatures'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_signature_modifier')){
		function autoriser_signature_modifier($faire, $type, $id, $qui, $opt){

			// Le webmestre
			if (
				isset($GLOBALS['autorite']['editer_signatures'])
				and $GLOBALS['autorite']['editer_signatures']>=1
				and autoriser('webmestre', $type, $id, $qui, $opt)
			){
				return true;
			}

			// Les admins
			if (
				isset($GLOBALS['autorite']['editer_signatures'])
				and $GLOBALS['autorite']['editer_signatures']>=2
				and $qui['statut']=='0minirezo'
				and !$qui['restreint']
			){
				return true;
			}

			// par defaut
			return autoriser_signature_modifier_dist($faire, $type, $id, $qui, $opt);
		}
	} else {
		$autorite_erreurs[] = 'autoriser_signature_modifier';
	}
}


##
## autoriser_configurer (pages de configuration)
##
if (
	isset($GLOBALS['autorite']['configurer'])
	or isset($GLOBALS['autorite']['configurer_plugin'])
){
	if (!function_exists('autoriser_configurer')){
		function autoriser_configurer($faire, $type, $id, $qui, $opt){
			// TODO:
			// cas particulier : configurer les plugins doit etre bloque
			// en mode 'webmestre', sinon on pourrait desactiver autorite.
			// mais comment faire pour ne pas bloquer quelqu'un qui installe
			// ce plugin alors qu'il est id_auteur > 1 ?
			if (in_array($type, array('plugins', 'admin_plugin'))){
				if (isset($GLOBALS['autorite']['configurer_plugin']) and $GLOBALS['autorite']['configurer_plugin']=='webmestre'){
					return autoriser('webmestre');
				}
			}

			if (isset($GLOBALS['autorite']['configurer']) and $GLOBALS['autorite']['configurer']=='webmestre'){
				return autoriser('webmestre');
			} else {
				return autoriser('x'); // autorisation par defaut
			}
		}
	} else {
		$autorite_erreurs[] = 'autoriser_configurer';
	}
}

##
## autoriser_sauvegarder (faire un backup partiel ou complet)
##
if (
	isset($GLOBALS['autorite']['sauvegarder'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_sauvegarder')){
		function autoriser_sauvegarder($faire, $type, $id, $qui, $opt){

			if (isset($GLOBALS['autorite']['sauvegarder']) and $GLOBALS['autorite']['sauvegarder']=='webmestre'){
				return autoriser('webmestre');
			}

			// admins y compris restreints
			if (isset($GLOBALS['autorite']['sauvegarder']) and $GLOBALS['autorite']['sauvegarder']=='minirezo'){
				return $qui['statut']=='0minirezo';
			}

			// version normale
			if (
				isset($GLOBALS['autorite']['sauvegarder'])
				and ($GLOBALS['autorite']['sauvegarder']==''
					or $GLOBALS['autorite']['sauvegarder']=='admin') # jusque v0.7 de ce plugin
			){
				return $qui['statut']=='0minirezo' and !$qui['restreint'];
			}
		}
	} else {
		$autorite_erreurs[] = 'autoriser_sauvegarder';
	}
}

##
## autoriser_detruire (vider la base de donnees)
##
if (
	isset($GLOBALS['autorite']['detruire'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_detruire')){
		function autoriser_detruire($faire, $type, $id, $qui, $opt){

			if (isset($GLOBALS['autorite']['detruire']) and $GLOBALS['autorite']['detruire']=='webmestre'){
				return autoriser('webmestre');
			}

			if (isset($GLOBALS['autorite']['detruire']) and $GLOBALS['autorite']['detruire']=='non'){
				return false;
			}

			// Par defaut, idem configuration
			return autoriser('configurer');
		}
	} else {
		$autorite_erreurs[] = 'autoriser_detruire';
	}
}

##
## autoriser_ecrire
##
if (
	isset($GLOBALS['autorite']['redacteurs_ecrire'])
	or false // autre possibilite de surcharge ?
){
	if (!function_exists('autoriser_ecrire')){
		function autoriser_ecrire($faire, $type, $id, $qui, $opt){
			return
				isset($GLOBALS['autorite']['redacteurs_ecrire'])
					? $qui['statut']=='0minirezo'
					: in_array($qui['statut'], array('0minirezo', '1comite'));
		}
	} else {
		$autorite_erreurs[] = 'autoriser_ecrire';
	}
}


if ($autorite_erreurs){
	$GLOBALS['autorite_erreurs'] = $autorite_erreurs;
}
