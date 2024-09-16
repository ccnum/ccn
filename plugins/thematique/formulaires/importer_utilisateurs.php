<?php

/**
 * Gestion du formulaire d'export des utilisateurs
 *
 * @plugin     Géolocalisation des utilisateurs de ressources
 * @copyright  2016
 * @author     Teddy Payet
 * @licence    GNU/GPL
 * @package    SPIP\Georessources\Formulaires
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
	$valeurs = array();

	return $valeurs;
}

/**
 * Verifier les champs postes et signaler d'eventuelles erreurs
 */
function formulaires_importer_utilisateurs_verifier_dist() {
	$erreurs = array();
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
			'georessources:info_1_centre_a_importer',
			'georessources:info_nb_utilisateurs_a_importer'
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
				'georessources:info_1_centre_importer',
				'georessources:info_nb_utilisateurs_importer'
			),
			_T('georessources:info_aucun_utilisateurs_importer')
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
	static $files = array();
	// on est appele deux fois dans un hit, resservir ce qu'on a trouve a la verif
	// lorsqu'on est appelle au traitement

	if (count($files)) {
		return $files;
	}

	$post = isset($_FILES) ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
	$files = array();
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
							return _T('medias:erreur_upload_type_interdit', array('nom' => $test['name']));
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
						return _T('medias:erreur_upload_type_interdit', array('nom' => $file['name']));
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
		if (!$line or stripos($line, 'Début labellisation') === false) {
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
			$data_raw = array();
			foreach ($d as $v) {
				$data_raw[] = array($v);
			}
		}
	}
	// colonner : si colonne email on prend toutes les colonnes
	// sinon on ne prend que la premiere colonne, comme un email
	$data = array();
	while ($data_raw and count($data_raw)) {

		$row = array_shift($data_raw);

		$d = array();
		$count = 0;
		foreach (array("Début labellisation", "Nom de la structure labellisée (obligatoire)", "Orientation", "Information", "Acc général", "Acc spécial", "Spécialité", "NOM de référent 1 (obligatoire)", "Prénom de référent 1 (obligatoire)", "Mail1 (obligatoire)", "NOM de référent 2", "Prénom de référent 2", "Mail2 (uniquement si NOM du référent 2)", "Tél1 (obligatoire)", "Adresse (obligatoire)", "Code postal (obligatoire)", "Ville (obligatoire)", "Cedex (obligatoire)") as $k) {
			if (isset($row[$count])) {
				$d[$k] = $row[$count];
			}
			$count++;
		}

		$data[] = $d;
	}

	return $data;
}

/**
 *
 * @param string $filename
 * @param array $options
 *   statut
 *   listes
 * @return array
 */
function importer_utilisateurs_importe($filename) {
	$res = array('count' => 0, 'erreurs' => array());
	$count = 0;
	$data = importer_utilisateurs_data($filename);
	set_request('id_auteur', ''); // pas d'auteur associe a nos inscrits

	foreach ($data as $d) {
		if ($d['Début labellisation'] != "Début labellisation") {

			$titre = str_replace('’', "'", trim($d['Nom de la structure labellisée (obligatoire)']));
			$titre = str_replace('"', '', $titre);
			$code_postal = preg_replace('/\D/', '', $d['Code postal (obligatoire)']);
			$coord = filtre_getXmlCoordsFromAdress($titre, trim($d['Adresse (obligatoire)']), $code_postal, trim($d['Ville (obligatoire)']));
			list($lat, $lon, $dep_num, $dep_nom, $region) = explode(';', $coord);
			if ($lat == 0) {
				$coord = filtre_getXmlCoordsFromAdress($titre, trim($d['Adresse (obligatoire)']), $code_postal, trim($d['Ville (obligatoire)']), '2');
				list($lat, $lon, $dep_num, $dep_nom, $region) = explode(';', $coord);
			}
			if ($lat == 0) {
				$coord = filtre_getXmlCoordsFromAdress($titre, trim($d['Adresse (obligatoire)']), $code_postal, trim($d['Ville (obligatoire)']), '3');
				list($lat, $lon, $dep_num, $dep_nom, $region) = explode(';', $coord);
			}
			if (is_array(explode('/', $d['Tél1 (obligatoire)']))) {
				$tels = explode('/', $d['Tél1 (obligatoire)']);
				$tel1 = $tels[0] ?? '';
				$tel2 = $tels[1] ?? '';
				$tel3 = $tels[2] ?? '';
			} else {
				$tel1 = $d['Tél1 (obligatoire)'];
			}
			$champs = array(
				"titre" => $titre,
				"tel1" => filtre_numero_tel($tel1),
				"tel2" => filtre_numero_tel($tel2 ?? ''),
				"tel3" => filtre_numero_tel($tel3 ?? ''),
				"adresse" => trim($d['Adresse (obligatoire)']),
				"cp" => $code_postal,
				"ville" => trim($d['Ville (obligatoire)']),
				"cedex" => trim($d['Cedex (obligatoire)']),
				"debut_labellisation" => trim($d['Début labellisation']) . '-00-00 00:00:00',
				"lat" => $lat,
				"lon" => $lon,
				"departement" => $dep_num,
				"departement_nom" => $dep_nom,
				"region" => $region,
				"statut" => 'publie'
			);
			$id_centre = sql_insertq('spip_utilisateurs', $champs);
			// thematiques
			if (!empty(trim($d['Orientation']))) {
				$id_mot = sql_getfetsel('id_mot', 'spip_mots', array('id_groupe=' . intval('8'), 'titre LIKE ' . sql_quote('%Orientation%')));
				objet_associer(array('mot' => $id_mot), array('centre' => $id_centre));
			}
			if (!empty(trim($d['Information']))) {
				$id_mot = sql_getfetsel('id_mot', 'spip_mots', array('id_groupe=' . intval('8'), 'titre LIKE ' . sql_quote('%Information%')));
				objet_associer(array('mot' => $id_mot), array('centre' => $id_centre));
			}
			if (!empty(trim($d['Acc général']))) {
				$id_mot = sql_getfetsel('id_mot', 'spip_mots', array('id_groupe=' . intval('8'), 'titre LIKE ' . sql_quote('%Accompagnement%')));
				objet_associer(array('mot' => $id_mot), array('centre' => $id_centre));
			}
			if (!empty(trim($d['Acc spécial']))) {
				$id_mot = sql_getfetsel('id_mot', 'spip_mots', array('id_groupe=' . intval('8'), 'titre LIKE ' . sql_quote('%Accompagnement spécialisé%')));
				objet_associer(array('mot' => $id_mot), array('centre' => $id_centre));
			}

			// https://git.spip.net/spip/spip/src/branch/3.2/ecrire/action/inscrire_auteur.php
			$inscrire_auteur = charger_fonction('inscrire_auteur', 'action');
			// L'auteur du point
			if (!empty(trim($d['Mail1 (obligatoire)'])) and $id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'email=' . sql_quote(trim($d['Mail1 (obligatoire)'])))) {
				objet_associer(array('auteur' => $id_auteur), array('centre' => $id_centre));
			} else {
				if (!empty(trim($d['Prénom de référent 1 (obligatoire)'])) and !empty(trim($d['NOM de référent 1 (obligatoire)']))) {
					$options = array();
					$options['login'] = trim($d['Mail1 (obligatoire)']);
					$options['modele_mail'] = "notifications/mail_inscription_georessources";
					$desc = $inscrire_auteur('1comite', trim($d['Mail1 (obligatoire)']), trim($d['Prénom de référent 1 (obligatoire)']) . ' ' . trim($d['NOM de référent 1 (obligatoire)']), $options);
					objet_associer(array('auteur' => $desc['id_auteur']), array('centre' => $id_centre));
				}
			}
			if (!empty(trim($d['Mail2 (uniquement si NOM du référent 2)'])) and $id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'email=' . sql_quote(trim($d['Mail2 (uniquement si NOM du référent 2)'])))) {
				objet_associer(array('auteur' => $id_auteur), array('centre' => $id_centre));
			} else {
				if (!empty(trim($d['Prénom de référent 2'])) and !empty(trim($d['NOM de référent 2']))) {
					$options = array();
					$options['login'] = trim($d['Mail2 (uniquement si NOM du référent 2)']);
					$options['modele_mail'] = "notifications/mail_inscription_georessources";
					$desc = $inscrire_auteur('1comite', trim($d['Mail2 (uniquement si NOM du référent 2)']), trim($d['Prénom de référent 2']) . ' ' . trim($d['NOM de référent 2']), $options);
					objet_associer(array('auteur' => $desc['id_auteur']), array('centre' => $id_centre));
				}
			}
			$count++;
		}
	}
	$res['count'] = $count;
	return $res;
}
