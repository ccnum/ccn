<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_SIMPLOG_LIGNE')) {
	/**
	 * Regexp permettant d'isoler les divers éléments d'un log spip.
	 */
	define('_SIMPLOG_LIGNE', '#^(.*:\d\d)\s(.*)\s\(pid\s(.*)\)\s(?:(.+):L(\d+):(\w+\(\)):)?:([bipru]*):([^:]*):\s(.*)$#i');
}

if (!defined('_SIMPLOG_COLONNES_EXCLUES')) {
	/**
	 * Liste des colonnes exclues de l'affichage : on peut choisir uniquement : 'ip', 'pid' ou 'hit'.
	 * Les autres colonnes sont toujours affichées.
	 * Par défaut, Simplog affiche toutes les colonnes.
	 */
	define('_SIMPLOG_COLONNES_EXCLUES', []);
}

/**
 * Lit un fichier de log et effectue son phrasage pour renvoyer une liste tabulaire des enregistrements.
 *
 * @param string $fichier Chemin complet du fichier de log
 *
 * @return array Liste des lignes du log entièrement phrasées.
 */
function simplog_phraser_log(string $fichier) : array {
	// Un tableau pour les enregistrements et un pour les niveaux de gravité
	$logs = [];
	$niveaux = [];
	$contenu = spip_file_get_contents($fichier);
	if ($contenu) {
		$gravites = array_flip(simplog_lister_gravites());
		foreach (preg_split('/\r?\n/', $contenu) as $_ligne) {
			if (
				($l = trim($_ligne))
				and ($l !== '[-- rotate --]')
			) {
				preg_match(_SIMPLOG_LIGNE, $_ligne, $matches);
				if (
					!isset($matches[1])
					or !$matches[1]
				) {
					// Ce n'est pas un nouvel enregistrement mais la suite du texte de l'enregistrement en cours
					$logs[count($logs) - 1]['texte'] .= "\n" . $_ligne;
				} else {
					// Clore la balise <code> du message d'explication du log précédent et supprimer la colonne code devenuje inutile
					if (!empty($logs[count($logs) - 1]['code'])) {
						$logs[count($logs) - 1]['texte'] .= '</code>';
					}
					if (isset($logs[count($logs) - 1]['code'])) {
						unset($logs[count($logs) - 1]['code']);
					}

					// Nouvel enregistrement de log
					$gravite = trim($matches[8]);
					$niveau = (string) $gravites["{$gravite}:"];
					$ligne['date'] = date('Y-m-d H:i:s', strtotime($matches[1]));
					$ligne['ip'] = trim($matches[2]);
					$ligne['pid'] = trim($matches[3]);
					$ligne['hit'] = _T('simplog:info_hit_' . strtolower(trim($matches[7])));
					$ligne['gravite'] = simplelog_contruire_gravite($niveau, $gravite);
					$ligne['niveau'] = $niveau;
					$ligne['texte'] = simplelog_contruire_texte(trim($matches[4]), trim($matches[5]), trim($matches[6]), $matches[9]);
					$ligne['code'] = !empty($matches[9]) ? true : false;
					$ligne['index'] = count($logs);
					$logs[] = $ligne;
					// Ajouter le niveau de gravité si besoin
					if (!in_array($niveau, $niveaux)) {
						$niveaux[] = $niveau;
					}
				}
			}
		}

		// Clore la balise <code> du message d'explication du dernier log et supprimer la colonne code devenuje inutile
		if (!empty($logs[count($logs) - 1]['code'])) {
			$logs[count($logs) - 1]['texte'] .= '</code>';
		}
		if (isset($logs[count($logs) - 1]['code'])) {
			unset($logs[count($logs) - 1]['code']);
		}
	}

	return [
		'logs'    => $logs,
		'niveaux' => $niveaux
	];
}

/**
 * Construit la structure du texte pour le présenter de la façon la plus lisible possible.
 *
 * @param string $fichier  Nom du fichier d'où provient le log
 * @param string $ligne    Ligne d'où provient le log
 * @param string $fonction La fonction dans laquelle le log est issu
 * @param string $message  Texte complémentaire d'explication
 *
 * @return string Texte complet du log
 */
function simplelog_contruire_texte(string $fichier, string $ligne, string $fonction, string $message) : string {
	// Le texte du message principal est formaté en plusieurs lignes :
	// - nom du fichier
	// - ligne de l'occurrence
	// - fonction dont est issu le log
	// - message complémentaire qui peut être sur plusieurs lignes et que l'on encapsule dans une balsie code
	$label_fichier = _T('simplog:label_fichier');
	$label_ligne = _T('simplog:label_ligne');
	$label_fonction = _T('simplog:label_fonction');
	$texte = ''
		 . ($fichier ? "-*<em>{$label_fichier}</em> : {$fichier}" : '')
		 . ($ligne ? "\n-*<em>{$label_ligne}</em> : {$ligne}" : '')
		 . ($fonction ? "\n-*<em>{$label_fonction}</em> : {$fonction}" : '')
		 . ($message ? "<br /><code>\n\r{$message}" : '');

	return $texte;
}

/**
 * Construit la structure affichée de la gravité.
 *
 * @param string $niveau  Identifiant numérique du niveau de la gravité (de 0 à 7) fourni sous forme de chaine.
 * @param string $gravite L'identifiant textuel de la gravité tel qu'inclu dans le log
 *
 * @return string Texte complet de la gravité
 */
function simplelog_contruire_gravite(string $niveau, string $gravite) : string {
	// la gravité est composée du niveau et de l'identifiant en minuscule
	return $niveau . ':' . strtolower($gravite);
}

/**
 * Fournit la liste des gravités sous la forme d'un tableau indexé par le niveau de gravité et donnant l'identifiant de la gravité.
 *
 * @return string[] Liste des gravités
 */
function simplog_lister_gravites() {
	static $gravites = [
		_LOG_HS              => 'HS:',
		_LOG_ALERTE_ROUGE    => 'ALERTE:',
		_LOG_CRITIQUE        => 'CRITIQUE:',
		_LOG_ERREUR          => 'ERREUR:',
		_LOG_AVERTISSEMENT   => 'WARNING:',
		_LOG_INFO_IMPORTANTE => '!INFO:',
		_LOG_INFO            => 'info:',
		_LOG_DEBUG           => 'debug:'
	];

	return $gravites;
}
