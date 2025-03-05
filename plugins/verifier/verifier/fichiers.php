<?php

/**
 * API de vérification : vérification de la validité d'un envoi de fichiers
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
 * Vérifier une saisie d'envoi de fichiers
 *
 * @param array|bool $valeur
 *   Le sous tableau de $_FILES à vérifier, $_FILES['logo'] par exemple
 *   Doit être un champ avec un ou plusieurs upload
 *   Si bool égal à true, cela signifie que le fichier avait déjà été vérifié, et qu'il est inutile de refaire la vérification.
 * @param array $options
 *   Options à vérifier :
 *   - mime au choix 'pas_de_verification', 'image_web','tout_mime','specifique'
 *   - mime_specifique (si l'option 'mime_specifique' est choisi ci-dessus)
 *   - taille_max (en Kio)
 *   - dimension_max, tableau contenant les dimension max:
 *		- largeur (en px)
 *		- hauteur (en px)
 *		- autoriser_rotation : booléen à mettre à true (ou bien string égale à 'on') si on autorise une image qui tiendrait dans ces dimensions si on faisait une rotation de 90°
 *	 - on peut remplacer ce tableau par des strings directement dans $options:
 *		- largeur_max
 *		- hauteur_max
 *		- autoriser_rotation
 * @param array|string &$erreurs_par_fichier
 *   Si on vérifier un upload multiple, un tableau, passé par référence, qui contient le détail des erreurs fichier de $_FILES['fichier'] par fichier
 *   Si on vérifie un upload unique, une chaîne qui contiendra l'erreur du fichier.
 * @return string
 **/
function verifier_fichiers_dist($valeur, $options, &$erreurs_par_fichier) {
	if (empty($valeur)) {
		return '';
	}
	if (!is_array($valeur['tmp_name'])) {//si on reçoit une info de type fichier unique, on bascule comme si on était fichier multiple
		if ($valeur === true) { // Si on n'a rien à vérifier
			return '';
		};

		$old_valeur = $valeur;
		$valeur = [];
		foreach ($old_valeur as $propriete => $val) {
			$valeur[$propriete][0] = $val;
		}
	}

	// normalisation de $options
	if (isset($options['largeur_max']) && !isset($options['dimension_max']['largeur'])) {
		if (!isset($options['dimension_max'])) {
			$options['dimension_max'] = [];
		}
		$options['dimension_max']['largeur'] = $options['largeur_max'];
		unset($options['largeur_max']);
	}
	if (isset($options['hauteur_max']) && !isset($options['dimension_max']['hauteur'])) {
		if (!isset($options['dimension_max'])) {
			$options['dimension_max'] = [];
		}
		$options['dimension_max']['hauteur'] = $options['hauteur_max'];
		unset($options['hauteur_max']);
	}
	if (isset($options['dimension_autoriser_rotation']) && !isset($options['dimension_max']['autoriser_rotation'])) {
		if (!isset($options['dimension_max'])) {
			$options['dimension_max'] = [];
		}
		$options['dimension_max']['autoriser_rotation'] = $options['dimension_autoriser_rotation'];
		unset($options['dimension_autoriser_rotation']);
	}
	if (isset($options['dimension_max']['autoriser_rotation']) && $options['dimension_max']['autoriser_rotation'] == 'on') {
		$options['dimension_max']['autoriser_rotation'] = true;
	}
	// Vérification proprement dite
	foreach ($valeur['tmp_name'] as $cle => $tmp_name) {//On parcourt tous les fichiers
		if ($valeur['error'][$cle] != 0 && $valeur['error'][$cle] != 4) {//On vérifie uniquement les fichiers bien expediés, si mal expedié, on indique une erreur générique. Si pas expediés, on indique rien
			$erreur = _T('verifier:erreur_php_file_' . $valeur['error'][$cle], ['name' => $valeur['name'][$cle]]);
			if (!is_array($erreurs_par_fichier)) {
				$erreurs_par_fichier = $erreur;
				return $erreur;
			} else {
				$erreurs_par_fichier[$cle] = "- $erreur";
			}
			continue;
		}
		if ($valeur['error'][$cle] == 4) {
			continue;
		}
		foreach (['mime','taille','dimension_max'] as $verification) { // On va vérifier d'hivers choses, dans un certain ordre, en confiant cela à des fonctions homonymes
			$fonction_verification = "verifier_fichier_$verification";
			if ($erreur = $fonction_verification($valeur, $cle, $options)) {
				if (!is_array($erreurs_par_fichier)) {
					$erreurs_par_fichier = $erreur;
					return $erreur;
				} else {
					$erreurs_par_fichier[$cle] = "- $erreur";
				}
			}
		}
	}
	if (!empty($erreurs_par_fichier)) {
		return implode('<br />', $erreurs_par_fichier);
	}
	return '';
}

/**
 * Vérifier le mime type d'une saisie d'envoi de fichiers
 *
 * @param array $valeur
 *   Le sous tableau de $_FILES à vérifier, $_FILES['logo'] par exemple
 *   Doit être un champ plusieurs uploads
 * @param int $cle
 *   La clé du tableau qu'on vérifie
 * @param array $options
 *   Les options tels que passés à verifier_fichiers()
 * @return string
 **/
function verifier_fichier_mime($valeur, $cle, $options) {
	if ($options['mime'] == 'pas_de_verification') {
		return '';
	}

	// Récuperer les infos mime + extension
	$mime_type = $valeur['type'][$cle];
	include_spip('action/ajouter_documents');
	$extension = corriger_extension(pathinfo($valeur['name'][$cle], PATHINFO_EXTENSION));

	// Appliquer les alias de type_mime
	include_spip('base/typedoc');
	while (isset($GLOBALS['mime_alias'][$mime_type])) {
		$mime_type = $GLOBALS['mime_alias'][$mime_type];
	}

	// Voir si les mime_type fournit par le serveur sont significatifs ou non
	$mime_insignifiant = false;
	if (in_array($valeur['type'][$cle], ['text/plain', '', 'application/octet-stream'])) { // si mime-type insignifiant, on se base uniquement sur l'extension (comme par exemple dans recuperer_infos_distantes())
		$mime_insignifiant = true;
	}

	if ($options['mime'] === 'specifique') {
		if (!$mime_insignifiant) {
			if (!in_array($mime_type, $options['mime_specifique'])) {
				return _T('verifier:erreur_type_non_autorise', ['name' => $valeur['name'][$cle]]);
			}
		}
		$res = sql_select('mime_type', 'spip_types_documents', sql_in('mime_type', $options['mime_specifique']) . ' and extension=' . sql_quote($extension));
		if (sql_count($res) === 0) {
			return _T('verifier:erreur_type_non_autorise', ['name' => $valeur['name'][$cle]]);
		}
	} elseif ($options['mime'] === 'tout_mime') {
		if (!$mime_insignifiant) {
			$res = sql_select('mime_type', 'spip_types_documents', 'mime_type=' . sql_quote($mime_type) . ' and extension=' . sql_quote($extension));
		} else {
			$res = sql_select('mime_type', 'spip_types_documents', 'extension=' . sql_quote($extension));
		}
		if (sql_count($res) === 0) {
			return _T('verifier:erreur_type_non_autorise', ['name' => $valeur['name'][$cle]]);
		}
	} elseif ($options['mime'] === 'image_web') {
		if (!in_array($mime_type, verifier_fichier_mime_type_image_web())) {
			return _T('verifier:erreur_type_image', ['name' => $valeur['name'][$cle]]);
		}
	}
	return '';
}


/**
 * Lister les mime_type d'image affichable
 * @param return array
**/
function verifier_fichier_mime_type_image_web(): array {
	static $mime = [];
	if (!$mime) {
		include_spip('base/abstract_sql');
		$mime = sql_allfetsel('mime_type', 'spip_types_documents', ['inclus=\'image\'', 'media_defaut=\'image\'']);
		$mime = array_column($mime, 'mime_type');
	}
	return $mime;
}
/**
 * Vérifier la taille d'une saisie d'envoi de fichiers
 *
 * La taille est vérifiée en fonction du paramètre passé en option, sinon en fonction d'une constante:
 *	- _IMG_MAX_SIZE si image web (sauf svg)
 *	- _DOC_MAX_SIZE si pas jpg/png/gif ou si _IMG_MAX_SIZE n'est pas définie
 *
 * @param array $valeur
 *   Le sous tableau de `$_FILES` à vérifier, `$_FILES['logo']` par exemple
 *   Doit être un champ plusieurs uploads
 * @param int $cle
 *   La clé du tableau qu'on vérifie
 * @param array $options
 *   Les options tels que passés à verifier_fichiers()
 * @return string
 **/
function verifier_fichier_taille($valeur, $cle, $options) {
	$taille = $valeur['size'][$cle];
	$mime = $valeur['type'][$cle];
	$image_web = verifier_fichier_mime_type_image_web();
	// Ne pas limiter la largeur des svg
	$image_web = array_diff($image_web, ['image/svg+xml']);

	// On commence par déterminer la taille max
	if (isset($options['taille_max'])) {
		$taille_max = $options['taille_max'];
	} elseif (in_array($mime, $image_web) && defined('_IMG_MAX_SIZE')) {
		$taille_max = _IMG_MAX_SIZE;
	} elseif (defined('_DOC_MAX_SIZE')) {
		$taille_max = _DOC_MAX_SIZE;
	} else {
		$taille_max = 0; // pas de taille max.
	}

	$taille_max = intval($taille_max); // précaution

	//Si la taille max est déterminée, on vérifie que le fichier ne dépasse pas cette taille
	if ($taille_max) {
		$taille_max = 1024 * $taille_max; // passage de l'expression en kibioctets à une expression en octets
		if ($taille > $taille_max) {
			return _T('verifier:erreur_taille_fichier', [
				'name'       => $valeur['name'][$cle],
				'taille_max' => taille_en_octets($taille_max),
				'taille'     => taille_en_octets($taille)
			]);
		}
	}
	return '';
}

/**
 * Vérifier la dimension d'une saisie d'envoi de fichiers
 *
 * Les dimensions sont vérifiées en fonction du paramètre passé en option, sinon en fonction des constantes:
 *	- _IMG_MAX_WIDTH
 *	- _IMG_MAX_HEIGHT
 *
 * On suppose que le type mime a été vérifié auparavent
 * @param array $valeur
 *   Le sous tableau de $_FILES à vérifier, $_FILES['logo'] par exemple
 *   Doit être un champ plusieurs uploads
 * @param int $cle
 *   La clé du tableau qu'on vérifie
 * @param array $options
 *   Les options tels que passés à verifier_fichiers()
 * @return string
 **/
function verifier_fichier_dimension_max($valeur, $cle, $options) {
	// On commence par récupérer les dimension de l'image
	include_spip('inc/filtres');
	$imagesize = @getimagesize($valeur['tmp_name'][$cle]);

	// Puis les infos sur ce qu'on autorise
	$largeur_max = (isset($options['dimension_max']['largeur']) ? $options['dimension_max']['largeur'] : (defined('_IMG_MAX_WIDTH') ? _IMG_MAX_WIDTH : PHP_INT_MAX));
	$hauteur_max = (isset($options['dimension_max']['hauteur']) ? $options['dimension_max']['hauteur'] : (defined('_IMG_MAX_HEIGHT') ? _IMG_MAX_HEIGHT : PHP_INT_MAX));
	$autoriser_rotation = (isset($options['dimension_max']['autoriser_rotation'])) ? $options['dimension_max']['autoriser_rotation'] : false;

	// Et on teste, si on a ce qui est nécessaire pour tester
	if ($imagesize && ($hauteur_max || $largeur_max)) {
		if ($autoriser_rotation) { // dans le cas où on autorise une rotation
			if (
				($imagesize[0] > $largeur_max || $imagesize[1] > $hauteur_max)
				&&
				($imagesize[1] > $largeur_max || $imagesize[0] > $hauteur_max)
			) {
				return _T('verifier:erreur_dimension_image', [
					'name'       => $valeur['name'][$cle],
					'taille_max' => $largeur_max . '&nbsp;px * ' . $hauteur_max . '&nbsp;px',
					'taille'     => $imagesize[0] . '&nbsp;px * ' . $imagesize[1] . '&nbsp;px'
				]);
			}
		} else { // dans le cas où on autorise pas la rotation
			if ($imagesize[0] > $largeur_max || $imagesize[1] > $hauteur_max) {
				return _T('verifier:erreur_dimension_image', [
					'name'       => $valeur['name'][$cle],
					'taille_max' => $largeur_max . '&nbsp;px * ' . $hauteur_max . '&nbsp;px',
					'taille'     => $imagesize[0] . '&nbsp;px * ' . $imagesize[1] . '&nbsp;px'
				]);
			}
		}
	}
	spip_log('Impossible de vérifier la dimension maximum du fichier', 'verifier' . _LOG_ERREUR);
	return '';
}
