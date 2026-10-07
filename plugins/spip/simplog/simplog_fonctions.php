<?php
/**
 * Ce fichier contient les fonctions filtres du plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Trouver le fichier log à partir du #ENV{log}
 * en vérifiant que #ENV{log} est safe
 * @param ?string $env_log
 * @return string
 */
function simplog_fichier_log_de(?string $env_log): string {
	if (!$env_log) {
		return '';
	}
	if (strpos($env_log, '..') !== false || strpos($env_log, '/') !== false) {
		return '';
	}
	if (!preg_match(',^([\w-]+\.log)(\.\d+)?$,', $env_log, $match)) {
		return '';
	}
	$famille_log = $match[1];
	$fichier_logs = simplog_lister_fichiers_logs();
	if (!isset($fichier_logs[$famille_log])) {
		return '';
	}
	if (!in_array($env_log, $fichier_logs[$famille_log])) {
		return '';
	}
	return _DIR_LOG . $env_log;
}

/**
 * Lister les fichiers de logs visibles et disponibles sur le disque
 * @return array|null
 */
function simplog_lister_fichiers_logs() {
	static $liste_logs = null;
	if ($liste_logs === null) {
		$liste_logs = [];
		$fichiers_logs = glob(_DIR_LOG . '*.log');
		foreach($fichiers_logs as $fichier_log) {
			$nom_log = basename($fichier_log);
			$liste_logs[$nom_log] = [$nom_log => $nom_log];
			foreach (['.?', '.??'] as $suffixe) {
				$variantes = glob($fichier_log . $suffixe);
				$variantes = array_map('basename', $variantes);
				sort($variantes);
				foreach($variantes as $variante) {
					$numero = substr($variante, strlen($nom_log) + 1);
					$liste_logs[$nom_log][$numero] = $variante;
				}
			}
		}

		ksort($liste_logs);
		if (!empty($liste_logs['spip.log'])) {
			$liste_logs = ['spip.log' => $liste_logs['spip.log']] + $liste_logs;
		}
	}

	return $liste_logs;
}
