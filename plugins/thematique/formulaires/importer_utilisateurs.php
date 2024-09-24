<?php

/**
 * Gestion du formulaire d'export des utilisateurs
 *
 * @plugin    Géolocalisation des utilisateurs de ressources
 * @copyright 2016
 * @author    Teddy Payet
 * @licence   GNU/GPL
 * @package   SPIP\Georessources\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/autoriser');
include_spip('action/editer_liens');

/**
 * Declarer les champs postes et y integrer les valeurs par defaut
 */
function formulaires_importer_utilisateurs_charger_dist() {
	$valeurs = [];

	return $valeurs;
}

/**
 * Verifier les champs postes et signaler d'eventuelles erreurs
 */
function formulaires_importer_utilisateurs_verifier_dist() {
	$erreurs = [];
	$filename = '';
	if (_request('go')) {
		$filename = session_get('importer_utilisateurs::tmpfilename');
	} else {
		$files = importer_utilisateurs_file();
		if (is_string($files)) {
			$erreurs['file_import'] = $files;
		} else {
			$files = reset($files);
			$filename = _DIR_TMP . basename($files['tmp_name']);
			move_uploaded_file($files['tmp_name'], $filename);
			session_set('importer_utilisateurs::tmpfilename', $filename);
			session_set('importer_utilisateurs::filename', $files['name']);
		}
	}

	if (!$filename) {
		$erreurs['file_import'] = _T('info_obligatoire');
	} elseif (!_request('go')) {
		$test = importer_utilisateurs_data($filename);
		$head = array_keys(reset($test));

		$erreurs['test'] = "\n";
		$erreurs['test'] .= "|{{" . implode("}}|{{", $head) . "}}|\n";

		$nbmax = 200;
		$count = count($test) - 1;
		while ($row = array_shift($test) and $nbmax--) {
			$erreurs['test'] .= "|" . implode("|", $row) . "|\n";
		}
		$erreurs['test'] .= "\n\n";
		$erreurs['test'] .= "<p class='explication'>{{" . singulier_ou_pluriel(
			$count,
			'thematique:info_1_auteur_importer',
			'thematique:info_nb_auteurs_importer'
		) . "}}</p>";
		$erreurs['message_erreur'] = '';
	}

	return $erreurs;
}

/**
 * Traiter les champs postes
 */
function formulaires_importer_utilisateurs_traiter_dist() {
	refuser_traiter_formulaire_ajax(); // pour recharger toute la page

	$res = array('editable' => true);

	$filename = session_get('importer_utilisateurs::tmpfilename');

	$r = importer_utilisateurs_importe($filename);

	$message =
		sinon(
			singulier_ou_pluriel(
				$r['count'],
				'thematique:info_1_auteur_importer',
				'thematique:info_nb_auteurs_importer'
			),
			_T('thematique:info_aucun_auteurs_importer')
		);
	if (count($r['erreurs'])) {
		$message .= "<p>Erreurs : <br />" . implode("<br />", $r['erreurs']) . "</p>";
		$res['message_erreur'] = $message;
	} else {
		$res['message_ok'] = $message;
	}

	return $res;
}


function importer_utilisateurs_file() {
	static $files = [];
	// on est appele deux fois dans un hit, resservir ce qu'on a trouve a la verif
	// lorsqu'on est appelle au traitement

	if (count($files)) {
		return $files;
	}

	$post = isset($_FILES) ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
	$files = [];
	if (is_array($post)) {
		include_spip('action/ajouter_documents');
		include_spip('inc/joindre_document');

		foreach ($post as $file) {
			if (is_array($file['name'])) {
				while (count($file['name'])) {
					$test = array(
						'error' => array_shift($file['error']),
						'name' => array_shift($file['name']),
						'tmp_name' => array_shift($file['tmp_name']),
						'type' => array_shift($file['type']),
					);
					if (!($test['error'] == 4)) {
						if (is_string($err = joindre_upload_error($test['error']))) {
							return $err;
						} // un erreur upload
						if (!is_array(verifier_upload_autorise($test['name']))) {
							return _T('medias:erreur_upload_type_interdit', ['nom' => $test['name']]);
						}
						$files[] = $test;
					}
				}
			} else {
				//UPLOAD_ERR_NO_FILE
				if (!($file['error'] == 4)) {
					if (is_string($err = joindre_upload_error($file['error']))) {
						return $err;
					} // un erreur upload
					if (!is_array(verifier_upload_autorise($file['name']))) {
						return _T('medias:erreur_upload_type_interdit', ['nom' => $file['name']]);
					}
					$files[] = $file;
				}
			}
		}
		if (!count($files)) {
			return _T('medias:erreur_indiquez_un_fichier');
		}
	}

	return $files;
}

function importer_utilisateurs_data($filename) {

	$header = true;
	$importer_csv = charger_fonction("importer_csv", "inc");

	// lire la premiere ligne et voir si elle contient 'email' pour decider si entete ou non
	if ($handle = @fopen($filename, "r")) {
		$line = fgets($handle, 4096);
		if (!$line or stripos($line, 'nom') === false) {
			$header = false;
		}
		@fclose($handle);
	}

	$data_raw = $importer_csv($filename, $header, ";", '"', null);

	// verifier qu'on a pas affaire a un fichier avec des fins de lignes Windows mal interpretes
	// corrige le cas du fichier texte 1 colonne, c'est mieux que rien
	if (
		count($data_raw) == 1
		and count(reset($data_raw)) == 1
	) {
		$d = reset($data_raw);
		$d = reset($d);
		$d = explode("\r", $d);
		$d = array_map('trim', $d);
		$d = array_filter($d);
		if (count($d) > 1) {
			$data_raw = [];
			foreach ($d as $v) {
				$data_raw[] = array($v);
			}
		}
	}

	// colonner : si colonne email on prend toutes les colonnes
	// sinon on ne prend que la premiere colonne, comme un email
	$data = [];
	while ($data_raw and count($data_raw)) {

		$row = array_shift($data_raw);

		$data[] = $row;
	}

	return $data;
}

/**
 *
 * @param  string $filename
 * @param  array  $options
 *   statut
 *   listes
 * @return array
 */
function importer_utilisateurs_importe($filename) {
	$res = ['count' => 0, 'erreurs' => []];
	$count = 0;
	$data = importer_utilisateurs_data($filename);
	set_request('id_auteur', ''); // pas d'auteur associe a nos inscrits

	foreach ($data as $d) {
		$nom = trim($d['nom']);
		$prenom = trim($d['prenom']);
		$login = trim($d['login']);
		$email = trim($d['emails']);
		[$email] = explode(':', $email);
		$admin_rubriques = trim($d['admin des rubriques']);
		$auteur_articles = trim($d['auteur des articles']);
		$champs = [
			"nom" => $nom,
			"prenom" => $prenom,
			"login" => $login,
			"email" => $email,
			"statut" => '6forum',
			"webmestre" => 'nom'
		];
		if ($id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'login=' . sql_quote($login))) {
		} else {
			$id_auteur = sql_insertq('spip_auteurs', $champs);
		}
		if (is_array(explode(',', $admin_rubriques))) {
			$admin_rubriques = explode(',', $admin_rubriques);
			foreach ($admin_rubriques as $a_r) {
				objet_associer(['id_auteur' => $id_auteur], ['rubrique' => $a_r]);
			}
		} else {
			objet_associer(['id_auteur' => $id_auteur], ['rubrique' => $admin_rubriques]);
		}
		if (is_array(explode(',', $auteur_articles))) {
			$auteur_articles = explode(',', $auteur_articles);
			foreach ($auteur_articles as $a_r) {
				objet_associer(['id_auteur' => $id_auteur], ['article' => $a_r]);
			}
		} else {
			objet_associer(['id_auteur' => $id_auteur], ['article' => $auteur_articles]);
		}
		$count++;
	}
	$res['count'] = $count;
	return $res;
}
