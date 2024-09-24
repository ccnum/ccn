<?php

/**
 * FONCTIONS
 **/
function filtre_nb2col($nb) {
	return substr($nb, spip_strlen((int) $nb) - 1, 1);
}

/* Surcharges Fonctions plugins
function formulaires_joindre_document_traiter(
	$id_document = 'new',
	$id_objet = 0,
	$objet = '',
	$mode = 'auto',
	$galerie = false,
	$proposer_media = true,
	$proposer_ftp = true
) {
	$res = array('editable' => true);
	$ancre = '';
	// on joint un document deja dans le site
	if (_request('joindre_mediatheque')) {
		$refdoc_joindre = _request('refdoc_joindre');
		$refdoc_joindre = strtr($refdoc_joindre, ";,", "  ");
		$refdoc_joindre = preg_replace(',\b(doc|document|img),', '', $refdoc_joindre);
		// expliciter les intervales xxx-yyy
		while (preg_match(",\b(\d+)-(\d+)\b,", $refdoc_joindre, $m)) {
			$refdoc_joindre = str_replace($m[0], implode(" ", range($m[1], $m[2])), $refdoc_joindre);
		}
		$refdoc_joindre = explode(" ", $refdoc_joindre);
		include_spip('action/editer_document');
		foreach ($refdoc_joindre as $j) {
			if ($j = intval(preg_replace(',^(doc|document|img),', '', $j))) {
				// lier le parent en plus
				$champs = array('ajout_parents' => array("$objet|$id_objet"));
				document_modifier($j, $champs);
				if (!$ancre) {
					$ancre = $j;
				}
				$sel[] = $j;
				$res['message_ok'] = _T('medias:document_attache_succes');
			}
		}
		if ($sel) {
			$res['message_ok'] = singulier_ou_pluriel(
				count($sel),
				'medias:document_attache_succes',
				'medias:nb_documents_attache_succes'
			);
		}
		set_request('refdoc_joindre', ''); // vider la saisie
	} // sinon c'est un upload
	else {
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');

		$mode = joindre_determiner_mode($mode, $id_document, $objet);
		include_spip('inc/joindre_document');
		$files = joindre_trouver_fichier_envoye();

		$nouveaux_doc = $ajouter_documents($id_document, $files, $objet, $id_objet, $mode);

		if (defined('_tmp_zip')) {
			unlink(_tmp_zip);
		}
		if (defined('_tmp_dir')) {
			effacer_repertoire_temporaire(_tmp_dir);
		}

		// checker les erreurs eventuelles
		$messages_erreur = array();
		$nb_docs = 0;
		$sel = array();
		foreach ($nouveaux_doc as $doc) {
			if (!is_numeric($doc)) {
				$messages_erreur[] = $doc;
			} // cas qui devrait etre traite en amont
			elseif (!$doc) {
				$messages_erreur[] = _T('medias:erreur_insertion_document_base', array('fichier' => '<em>???</em>'));
			} else {
				if (!$ancre) {
					$ancre = $doc;
				}
				$sel[] = $doc;
			}
		}
		if (count($messages_erreur)) {
			$res['message_erreur'] = implode('<br />', $messages_erreur);
		}
		if ($sel) {
			$res['message_ok'] = singulier_ou_pluriel(
				count($sel),
				'medias:document_installe_succes',
				'medias:nb_documents_installe_succes'
			);
		}
		if ($ancre) {
			if ($id_)
				$res['redirect'] = "spip.php?page=$objet&id_$objet=$id_objet&mode_complet#doc$ancre";
		}
	}
	if (count($sel) or isset($res['message_ok'])) {
		$callback = "";
		if ($ancre) {
			$callback .= "jQuery('#doc$ancre a.editbox').eq(0).focus();";
		}
		if (count($sel)) {
			// passer les ids document selectionnes aux pipelines
			$res['ids'] = $sel;

			$sel = "#doc" . implode(",#doc", $sel);
			$callback .= "jQuery('$sel').animateAppend();";
		}
		$js = "if (window.jQuery) jQuery(function(){ajaxReload('documents',{callback:function(){ $callback }});});";
		$js = "<script type='text/javascript'>$js</script>";
		if (isset($res['message_erreur'])) {
			$res['message_erreur'] .= $js;
		} else {
			$res['message_ok'] .= $js;
		}
	}

	return $res;
}
*/
