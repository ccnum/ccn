<?php

function left_join_pre_boucle($boucle) {
	static $trouver_table;

	if(!isset($boucle->left_join))
		return $boucle;
	if(!count($boucle->join)) {
		erreur_squelette("Critère {left_join} utilisé sans jointure", $boucle);
		return $boucle;
	}
	$debug = $boucle->left_join == 'debug';
	$jointures = ($boucle->left_join == '' || $debug)
		? array_keys($boucle->join)
		: preg_split('/\s+/', $boucle->left_join);

	$from = $boucle->from;
	$flip = array_flip($from);
	foreach($jointures as $j) {
		if($j == 'debug') { $debug = true; continue; }
		// $j forme 'L1' ou 'spip_table'
		$table = isset($from[$j]) ? $j : ( isset($flip[$j]) ? $flip[$j] : '');
		if(!$table) {
			// alias de table sous forme 'AUTEURS' ou 'DOCUMENTS' ?
			$alias = strtolower($j);
			$j2 = isset($GLOBALS['table_des_tables'][$alias]) ? $GLOBALS['table_des_tables'][$alias] : $j;
			// $j sous forme 'auteurs' ou 'auteur') ?
			if(!$trouver_table)
				$trouver_table = charger_fonction('trouver_table', 'base');
			if($table_sql = table_objet_sql($j2))
				$table = isset($flip[$table_sql]) ? $flip[$table_sql] : '';
		}
		if(!$table) {
			$join = array_map(function($k) use(&$from) { return $k . '/' . $from[$k]; }, array_keys($boucle->join));
			erreur_squelette("Critère {left_join} : jointure '$j' introuvable. Jointures disponibles : " 	. implode(', ', $join), $boucle);
		} else
			if(isset($boucle->join[$table]))
				$boucle->from_type[$table] = 'LEFT';
	}

	if($debug) {
		$join = array_map(function($k) use(&$from) { return $k . '/' . $from[$k]; }, array_keys($boucle->join));
		$left = implode(', ', array_keys($boucle->from_type));
		erreur_squelette("<strong>Informations debug sur le critère {left_join}</strong>
				<br><br><strong>Boucle</strong> : $boucle->id_boucle<br><strong>Fichier</strong>&nbsp;:&nbsp;" . $boucle->descr['sourcefile']
			. "<br><strong>Paramètres du critère</strong> : $boucle->left_join<br><strong>Jointures disponibles</strong> : " 	. implode(', ', $join)
			. "<br><strong>LEFT JOIN appliqué à</strong> : " . $left);
	}

	return $boucle;
}


?>