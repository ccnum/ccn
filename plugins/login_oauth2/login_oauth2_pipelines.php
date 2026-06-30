<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclare les providers OAuth2 disponibles pour le plugin login_oauth2.
 *
 * Ce pipeline permet :
 * - d'ajouter de nouveaux providers OAuth2
 * - de modifier les providers existants
 * - d'étendre le plugin avec des fournisseurs externes
 *
 * Les providers déclarés ici servent de base et peuvent être complétés
 * par la configuration du plugin (client_id, client_secret, label…).
 *
 * Structure d'un provider :
 *
 * [
 *   'label' => 'Nom affiché',
 *
 *   'mode' => 'generic',
 *
 *   'oauth2_defaults' => [
 *      'provider' => 'generic',
 *      'authorize_endpoint' => 'URL authorization',
 *      'token_endpoint' => 'URL token',
 *      'scope' => 'scopes OAuth2',
 *
 *      'oidc' => [
 *          'enabled' => bool
 *      ],
 *
 *      'issuer' => 'issuer OIDC',
 *
 *      'pkce' => [
 *          'enabled' => bool,
 *          'method' => 'S256'
 *      ]
 *   ],
 *
 *   'userinfo_endpoint' => 'endpoint userinfo',
 *
 *   // paramètres optionnels
 *   'icon' => 'chemin icône',
 *   'description' => 'description provider'
 * ]
 *
 * Ce pipeline peut être utilisé par d'autres plugins pour ajouter
 * leurs propres providers (Keycloak, Azure AD, GitHub, etc.).
 *
 * @pipeline login_oauth2_providers
 *
 * @param array $providers
 *     Liste des providers déjà déclarés.
 *
 * @return array
 *     Liste enrichie des providers OAuth2 disponibles.
 */
function login_oauth2_login_oauth2_providers($providers) {

	// -------------------------------------------------
	// Google
	// -------------------------------------------------

	$providers['google_login'] = [
		'label' => 'Google',

		'mode' => 'generic',
		
		'discovery' =>
			'https://accounts.google.com/.well-known/openid-configuration',

		'oauth2_defaults' => [

			'provider' => 'generic',

			'authorize_endpoint' =>
				'https://accounts.google.com/o/oauth2/v2/auth',

			'token_endpoint' =>
				'https://oauth2.googleapis.com/token',

			'scope' => 'openid email profile',

			'oidc' => [
				'enabled' => true
			],

			'issuer' => 'https://accounts.google.com',

			'pkce' => [
				'enabled' => true,
				'method'  => 'S256'
			]
		],

		'userinfo_endpoint' =>
			'https://openidconnect.googleapis.com/v1/userinfo'
	];


	// -------------------------------------------------
	// Facebook
	// -------------------------------------------------

	$providers['facebook_login'] = [
		'label' => 'Facebook',

		'mode' => 'generic',

		'oauth2_defaults' => [

			'provider' => 'generic',

			'authorize_endpoint' =>
				'https://www.facebook.com/v19.0/dialog/oauth',

			'token_endpoint' =>
				'https://graph.facebook.com/v19.0/oauth/access_token',

			'scope' => 'email public_profile',

			'pkce' => [
				'enabled' => false
			],

			'oidc' => [
				'enabled' => false
			]
		],

		'userinfo_endpoint' =>
			'https://graph.facebook.com/me?fields=id,name,email'
	];


	// -------------------------------------------------
	// LinkedIn
	// -------------------------------------------------

	$providers['linkedin_login'] = [
		'label' => 'LinkedIn',

		'mode' => 'generic',

		'oauth2_defaults' => [

			'provider' => 'generic',

			'authorize_endpoint' =>
				'https://www.linkedin.com/oauth/v2/authorization',

			'token_endpoint' =>
				'https://www.linkedin.com/oauth/v2/accessToken',

			'scope' => 'openid profile email',

			'pkce' => [
				'enabled' => false
			],

			'oidc' => [
				'enabled' => false
			]
		],

		'userinfo_endpoint' =>
			'https://api.linkedin.com/v2/userinfo'
	];


	// -------------------------------------------------
	// Keycloak (exemple local)
	// -------------------------------------------------

	$providers['keycloak_login'] = [
		'label' => 'Keycloak',

		'mode' => 'generic',

		'discovery' => null,

		'oauth2_defaults' => [

			'provider' => 'generic',

			'authorize_endpoint' =>
				'http://localhost:8080/realms/demo/protocol/openid-connect/auth',

			'token_endpoint' =>
				'http://localhost:8080/realms/demo/protocol/openid-connect/token',

			'scope' => 'openid profile email',

			'oidc' => [
				'enabled' => true
			],

			'issuer' =>
				'http://localhost:8080/realms/demo',

			'pkce' => [
				'enabled' => true,
				'method'  => 'S256'
			]
		],

		'userinfo_endpoint' =>
			'http://localhost:8080/realms/demo/protocol/openid-connect/userinfo'
	];

	return $providers;
}

/**
 * Ajoute les boutons OAuth2 au formulaire de connexion SPIP.
 *
 * Ce pipeline injecte les boutons d'authentification OAuth2
 * dans le formulaire login de SPIP.
 *
 * @pipeline formulaire_fond
 *
 * @param array $flux
 *     Contexte du formulaire :
 *     - args : arguments du formulaire
 *     - data : HTML généré
 *
 * @return array
 *     Flux modifié avec les boutons OAuth2 ajoutés au formulaire login.
 */
 function login_oauth2_formulaire_fond($flux) {

	// Vérifier qu'on est bien sur le formulaire login
	 // On cible uniquement le formulaire login
	if (
		empty($flux['args']['form'])
		|| $flux['args']['form'] !== 'login'
	) {
		return $flux;
	}

	include_spip('inc/session');
	$erreur = session_get('login_oauth2_error');

	// Charger les erreurs
	$message_erreur = '';
	$libelle_erreur = '';
	if ($erreur) {
		$libelle_erreur=_T("login_oauth2:$erreur");
		session_set('login_oauth2_error', null);
		$message_erreur = recuperer_fond('inclure/login_oauth2_erreur',['erreur' => $libelle_erreur]);
	}

	// Charger les providers actifs
	include_spip('inc/login_oauth2_providers');
	$providers = login_oauth2_lister_providers();

	if (!$providers) {
		return $flux;
	}

	// Générer le bloc HTML
	$boutons = recuperer_fond('inclure/login_oauth2_boutons',['providers' => $providers,'erreur' => $libelle_erreur]);

	// si Oauth2 forcé, remplacer le formulaire de login SPIP <form>...</form>
	if (lire_config('login_oauth2/forcer_oauth2')) {
		$flux['data'] = preg_replace(
			',<form\b[^>]*>.*?</form>,is',
			$message_erreur . $boutons,
			$flux['data'],
		);
		return $flux;
	}

	// Sinon injecter après le formulaire de login SPIP </form>...
	$flux['data'] = str_replace('</form>','</form>'.$boutons, $flux['data']);

	return $flux;
}

/**
 * Affiche les connexions OAuth2 associées à un auteur.
 *
 * Ce pipeline ajoute dans la fiche auteur de l'espace privé
 * une boîte d'information listant les dernières autorisations
 * OAuth2 accordées par cet utilisateur.
 * Les données sont récupérées dans la table `spip_auteurs_oauth2`.
 *
 * @pipeline afficher_config_objet
 *
 * @param array $flux
 *     Contexte d'affichage de l'objet :
 *     - args : informations sur l'objet affiché
 *     - data : HTML généré
 *
 * @return array
 *     Flux enrichi avec les informations OAuth2 de l'auteur.
 */
function login_oauth2_afficher_config_objet($flux){

		// Vérifier qu'on est bien sur une fiche auteur
	if ($flux['args']['type']=='auteur'
	AND $id_auteur=intval($flux['args']['id']))
	{
	$liaisons = sql_allfetsel('*','spip_auteurs_oauth2','id_auteur='.$id_auteur);
	
	// Pas de connexion oauth2
	if (!$liaisons) {
		return $flux;
	}
	// Affichage boite connexions
	$nb = count($liaisons);
	$flux['data'].= debut_boite_info(true).'<div class="login_infos_titre">'.singulier_ou_pluriel($nb, 'login_oauth2:autorisation', 'login_oauth2:autorisations').'</div>';
	$flux['data'].='<ul class="login_oauth2_list">';
	foreach ($liaisons as $liaison) {
		include_spip('inc/filtres');
		$date = affdate($liaison['date_liaison'], 'd-m-y');
		$flux['data'].='<li class="login_infos_item">'.$liaison['provider'].': '.$date.' ['.$liaison['grant_type'].']</li>';
   }
	$flux['data'].='</ul>'. fin_boite_info(true);
	   
	}
	return $flux;
}

/**
 * Charge la feuille de style du plugin login_oauth2.
 *
 * Ce pipeline ajoute le fichier CSS du plugin dans le head
 * des pages SPIP afin de styliser les boutons OAuth2
 * et les messages d'erreur.
 *
 * @pipeline insert_head_css
 *
 * @param string $flux
 *     Contenu HTML déjà présent dans le head.
 *
 * @return string
 *     Flux enrichi avec la feuille de style du plugin.
 */
function login_oauth2_insert_head_css($flux) {
	$css = find_in_path('css/login_oauth2.css');
	$flux .= '<link rel="stylesheet" href="'.$css.'" type="text/css" />';
	return $flux;
}