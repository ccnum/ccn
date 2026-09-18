<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Autoriser a voir le site en travaux : par defaut tous les webmestre,
 * mais c'est configurable
 * @return mixed
 */
function autoriser_travaux_dist($faire, $type, $id, $qui, $opt) {
	include_spip('inc/config');
	$statut = lire_config('entravaux/autoriser_travaux');
	$autoriser = false;
	if (!$statut) {
		$statut = 'webmestre';
	}
	if ($statut == 'webmestre') {
		$autoriser = autoriser('webmestre');
	} elseif ($statut == '0minirezo') {
		$autoriser = ($qui['statut'] == '0minirezo');
	} elseif ($statut == '1comite') {
		$autoriser = ($qui['statut'] == '1comite' or $qui['statut'] == '0minirezo');
	}

	$flux = ['args' => ['statut' => $statut], 'data' => $autoriser ];
	$autoriser = pipeline('autoriser_travaux', $flux);

	return $autoriser;
}

/**
 * Verifier un verrou fichier pose dans local/entravaux_xxx.lock
 * pour ne pas qu'il saute si on importe une base
 * La meta n'est qu'un cache qu'on met a jour si pas dispo.
 */
function entravaux_check_verrou(string $nom, bool $force = false): bool {
	if (!isset($GLOBALS['meta'][$m = 'entravaux_' . $nom]) || $force) {
		ecrire_meta($m, file_exists(_DIR_VAR . $m . '.lock') ? 'oui' : 'non', 'non');
	}
	return $GLOBALS['meta'][$m] === 'oui'; // si oui : verrou pose
}

/**
 * A-t-on active les travaux oui ou non ?
 */
function is_entravaux(): bool {
	// upgrade sauvage ?
	include_spip('entravaux_administrations');
	if (isset($GLOBALS['meta']['entravaux_id_auteur'])) {
		include_spip('entravaux_install');
		entravaux_poser_verrou('accesferme');
		effacer_meta('entravaux_id_auteur');
	}
	if (defined('_ENTRAVAUX_IP_EXCEPTIONS') && in_array($GLOBALS['ip'], explode(',', _ENTRAVAUX_IP_EXCEPTIONS))) {
		return false;
	}
	return entravaux_check_verrou('accesferme');
}

if (is_entravaux()) {
	include_spip('inc/autoriser');

	// dans le site public
	// si auteur pas autorise : placer sur un cache dedie
	// si auteur autorise, desactiver le cache :
	// il voit le site, mais pas de cache car il travaille dessus !
	if (!test_espace_prive()) {
		if (!autoriser('travaux')) {
			if (isset($GLOBALS['marqueur'])) {
				$GLOBALS['marqueur'] .= ':en_travaux';
			} else {
				$GLOBALS['marqueur'] = ':en_travaux';
			}
		} else {
			// desactiver le cache sauf si inhibe par define
			if (!defined('_ENTRAVAUX_GARDER_CACHE') && !defined('_NO_CACHE')) {
				define('_NO_CACHE', 1);
			}
		}
		// si espace prive : die avec page travaux
		// sauf si pas loge => redirection
	} elseif (
		_FILE_CONNECT
		&& _request('action') !== 'logout'
		&& !in_array(_request('exec'), ['install', 'mutualisation'])
		&& !autoriser('travaux')
	) {
		spip_initialisation_suite();
		// si on est loge : die() avec travaux
		if (isset($GLOBALS['visiteur_session']['id_auteur']) && $GLOBALS['visiteur_session']['id_auteur'] != '') {
			$travaux = recuperer_fond('inclure/entravaux', []);
			// fallback : le fond renvoie parfois du vide ...
			if (!strlen($travaux)) {
				@define('_SPIP_SCRIPT', 'spip.php');
				echo "Acces interdit (en travaux) <a href='"
				. generer_url_action('logout', 'logout=public', false, true)
				. "'>Deconnexion</a>";
			} else {
				echo $travaux;
			}
			die();
		} else {
			// sinon retour sur login_sos
			$redirect = parametre_url(generer_url_public('login_sos'), 'url', self(), '&');
			include_spip('inc/headers');
			redirige_par_entete($redirect);
		}
	}
}

/**
 * Pipeline styliser pour rerouter tous les fonds vers en_travaux
 * sauf si l'auteur connecte est celui qui a active le plugin
 *
 * @param array $flux
 * @return array
 */
function entravaux_styliser($flux) {
	if (is_entravaux()) {
		include_spip('inc/autoriser');
		// les pages exceptions
		$pages_ok = ['login_sos', 'robots.txt', 'spip_pass', 'favicon.ico', 'informer_auteur'];
		// des squelettes autorisés configurables via mes_options
		$skels_ok = defined('_SKEL_HORS_TRAVAUX') ? explode(',', _SKEL_HORS_TRAVAUX) : [];

		if (
			!autoriser('travaux')
			&& !in_array($flux['args']['fond'], $pages_ok)
			&& !in_array($flux['args']['fond'], $skels_ok)
			&& !(isset($flux['args']['contexte'][_SPIP_PAGE]) && in_array($flux['args']['contexte'][_SPIP_PAGE], $pages_ok))
			&& !((_request('var_mode') === 'preview') && (_request('var_previewtoken') != '') && autoriser('previsualiser'))
			// et on laisse passer modeles et formulaires,
			// qui ne peuvent etre inclus ou appeles que legitimement
			&& strpos($flux['args']['fond'], '/') === false
		) {
			$fond = trouver_fond('inclure/entravaux', '', true);
			$flux['data'] = $fond['fond'];
		}
	}
	return $flux;
}


/**
 * Afficher une icone de travaux sur tout le site public pour que le webmestre n'oublie pas
 * de retablir le site
 *
 * @param string $flux
 * @return string
 */
function entravaux_affichage_final($flux) {
	if (
		is_entravaux()
		&& !test_espace_prive()
		&& $GLOBALS['html']
		&& !_AJAX
	) {
		include_spip('inc/filtres'); // pour http_img_pack
		$x = '<div id="icone_travaux" style="
		position: fixed;
		left: 40px;
		top: 40px;
		z-index: 10000;
		">'
		. http_img_pack(chemin_image('entravaux-64.svg'), _T('entravaux:en_travaux'), '', _T('entravaux:en_travaux'))
		. '</div>';
		if (!$pos = strpos($flux, '</body>')) {
			$pos = strlen($flux);
		}
		$flux = substr_replace($flux, $x, $pos, 0);
	}

	return $flux;
}

/**
 * Afficher une notice sur l'accueil de ecrire
 * @param array $flux
 * @return array
 */
function entravaux_affiche_milieu($flux) {
	if (
		is_entravaux()
		&& $flux['args']['exec'] === 'accueil'
	) {
		$notice = recuperer_fond('inclure/entravaux_notice_ecrire', []);
		if (strlen(trim($notice))) {
			$flux['data'] =  $notice . $flux['data'];
		}
	}
	if ($flux['args']['exec'] === 'configurer_identite') {
		$flux['data'] .= recuperer_fond('prive/squelettes/contenu/configurer_entravaux', []);
	}

	return $flux;
}
