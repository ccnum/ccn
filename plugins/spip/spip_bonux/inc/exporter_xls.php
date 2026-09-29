<?php

/**
 * Plugin Spip-Bonux
 * Le plugin qui lave plus SPIP que SPIP
 * (c) 2008 Mathieu Marcillaud, Cedric Morin, Tetue
 * Licence GPL
 *
 * Fonctions d'export d'une requete sql ou d'un tableau
 * au format CSV
 * Merge du plugin csv_import et spip-surcharges
 *
 */

use Export\ExportDataExcel;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');
include_spip('inc/filtres');
include_spip('inc/texte');


/**
 * Preparer une ligne avant export XLS : charset si besoin + callback
 *
 * @param int $nb
 * @param array $ligne
 * @param string|null $importer_charset
 *     Si défini exporte dans le charset indiqué
 * @param callable $callback
 * @return string
 */
function exporter_xls_preparer_ligne_numerotee($nb, $ligne, $importer_charset = null, $callback = null) {
	if ($callback) {
		$ligne = call_user_func($callback, $nb, $ligne, $importer_charset);
	}
	foreach ($ligne as $k => $v) {
		$ligne[$k] = str_replace('’', '\'', $ligne[$k]);
		$ligne[$k] = rtrim($ligne[$k]);
		if ($importer_charset and !is_numeric($v)) {
			$ligne[$k] = unicode2charset(html2unicode(charset2unicode($ligne[$k])), $importer_charset);
		}
	}
	return $ligne;
}

/**
 * Exporte une ressource sous forme de fichier CSV
 *
 * La ressource peut etre un tableau ou une resource SQL issue d'une requete
 * L'extension est choisie en fonction du delimiteur :
 * - si on utilise ',' c'est un vrai csv avec extension csv
 * - si on utilise ';' ou tabulation c'est pour E*cel, et on exporte en iso-truc, avec une extension .xls
 *
 * @uses exporter_xls_ligne()
 *
 * @param string $titre
 *   titre utilise pour nommer le fichier
 * @param array|resource $resource
 * @param array $options
 *   array $entetes : tableau d'en-tetes pour nommer les colonnes (genere la premiere ligne)
 *   bool $envoyer : pour envoyer le fichier exporte (permet le telechargement)
 *   string $charset : charset de l'export si different de celui du site
 *   callable callback : fonction callback a appeler sur chaque ligne pour mettre en forme/completer les donnees
 * @return string
 */
function inc_exporter_xls_dist($titre, $resource, $options = []) {

	include_spip('lib/php-export-data/src/ExportData');
	include_spip('lib/php-export-data/src/ExportDataExcel');

	$default_options = [
		'entetes' => null,
		'envoyer' => true,
		'charset' => null,
		'callback' => null,
	];
	$options = array_merge($default_options, $options);

	$filename = preg_replace(',[^-_\w]+,', '_', translitteration(textebrut(typo($titre))));


	$extension = 'xls';

	$charset = $GLOBALS['meta']['charset'];
	// mais si une option charset est explicite, elle a la priorite
	if (!empty($options['charset'])) {
		$charset = $options['charset'];
	}

	$importer_charset = (($charset === $GLOBALS['meta']['charset']) ? null : $charset);
	$filename = "$filename.$extension";
	$fichier = sous_repertoire(_DIR_CACHE, 'export') . $filename;

	if ($options['envoyer']) {
		// Vider tous les tampons
		$level = @ob_get_level();
		while ($level--) {
			@ob_end_flush();
		}
	}

	$exporter = new ExportDataExcel($options['envoyer'] ? 'browser' : 'file', $fichier);
	$exporter->encoding = $charset;

	$exporter->initialize(); // start export

	$nb = 0;
	if (!empty($options['entetes']) and is_array($options['entetes'])) {
		$ligne = exporter_xls_preparer_ligne_numerotee($nb, $options['entetes'], $importer_charset, $options['callback']);
		$exporter->addRow($ligne);
	}

	// les donnees commencent toujours a la ligne 1, qu'il y ait ou non des entetes
	$nb++;

	while ($row = is_array($resource) ? array_shift($resource) : sql_fetch($resource)) {
		$ligne = exporter_xls_preparer_ligne_numerotee($nb, $row, $importer_charset, $options['callback']);
		$exporter->addRow($ligne);
		$nb++;
	}

	$exporter->finalize(); // writes the footer, flushes remaining data to browser.

	if ($options['envoyer']) {
		// si on a envoye inline, c'est deja tout bon
		exit;
	}

	return $fichier;
}
