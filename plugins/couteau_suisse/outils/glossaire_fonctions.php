<?php

// Outils GLOSSAIRE - 26 mai 2007
// Serieuse refonte et integration au Couteau Suisse : Patrice Vanneufville
// Doc : https://contrib.spip.net/?article2206

include_spip('inc/charsets');

// liste des accents (sans casse)
define('_GLOSSAIRE_ACCENTS', '#(19[2-9]|2[023][0-9]|21[0-46-9]|24[0-689]|25[0-4]|33[89]|35[23]|376)|a(?:acute|circ|elig|grave|ring|tilde|uml)|ccedil|e(?:acute|circ|grave|th|uml)|i(?:acute|circ|grave|uml)|ntilde|o(?:acute|circ|elig|grave|slash|tilde|uml)|s(?:caron|zlig)|thorn|u(?:acute|circ|grave|uml)|y(?:acute|uml)');

// on calcule ici la constante _GLOSSAIRE_QUERY, surchargeable dans config/mes_options.php
function glossaire_groupes() {
	$groupes = trim($GLOBALS['glossaire_groupes']);
	if(!strlen($groupes)) return _q('Glossaire');
		else {
			$groupes = explode(':', $groupes);
			foreach($groupes as $i=>$g) $groupes[$i] = _q(trim($g));
			return implode(" OR type=", $groupes);
		}
}

// Separateur des titres de mots stockes en base
if(!defined('_GLOSSAIRE_TITRE_BASE_SEP')) define('_GLOSSAIRE_TITRE_BASE_SEP', '/');
// Separateur utilise pour fabriquer le titre de la fenetre de glossaire (fichiers fonds/glossaire_xx.html).
if(!defined('_GLOSSAIRE_TITRE_SEP')) define('_GLOSSAIRE_TITRE_SEP', '<br />');
// Balises a echapper avant le traitement du glossaire
if(!defined('_GLOSSAIRE_ECHAPPER')) define('_GLOSSAIRE_ECHAPPER', '|cite|a|acronym|abbr');
// chaine pour interroger la base (SPIP <= 1.92)
if(!defined('_SPIP19300'))
	@define('_GLOSSAIRE_QUERY', 'SELECT id_mot, titre, texte, descriptif FROM spip_mots WHERE type=' . glossaire_groupes() . ' ORDER BY id_mot ASC');

// surcharge possible de cette fonction glossaire_generer_url_dist par : glossaire_generer_url($id_mot, $titre_mot)
// si elle existe, elle sera utilisee pour generer l'url cliquable des mots trouves
//   exemple pour annuler le clic : function glossaire_generer_url($id_mot, $titre_mot) { return 'javascript:;'; }
function glossaire_generer_url_dist($id_mot, $titre_mot) {
	if(strpos($titre_mot, '=')!==false) {
		list(, $lien) = glossaire_redirection($titre_mot);
		if($lien) return calculer_url($lien);
	}
	if(defined('_SPIP40100'))
		return generer_objet_url($id_mot, 'mot');
	if(defined('_SPIP19300'))
		return generer_url_entite($id_mot, 'mot');
	// avant SPIP 2.0 :
	charger_generer_url(); return generer_url_mot($id_mot);
}

// surcharge possible de cette fonction glossaire_generer_mot_dist par : glossaire_generer_mot($id_mot, $mot)
// si elle existe, elle sera utilisee pour remplacer le mot detecte dans la phrase
/* exemple pour utiliser un fond personnalise, mettre une couleur de groupe ou inserer un logo par exemple :
	function glossaire_generer_mot($id_mot, $mot) {
		return recuperer_fond('/fonds/mon_glossaire', array('id_mot'=>$id_mot, 'mot'=>$mot));
	}*/
function glossaire_generer_mot_dist($id_mot, $mot) {
	return $mot;
}

/* surcharge possible de cette fonction glossaire_attributs_lien_dist par : glossaire_attributs_lien($lien, $titre, $gloss_id)
 si elle existe, elle sera utilisee pour les attributs (sauf class et name) du <a> place autour du mot detecte.
 $lien : lien du mot - $titre : titre brut du mot - $les_titres : array() des differents titres possibles du mot
 Exemple :
	function glossaire_attributs_lien($id_mot, $lien, $titre, $les_titres) {
		return "href='$lien' title=\"" . attribut_html($les_titres[0]). '"';
	} */
function glossaire_attributs_lien_dist($id_mot, $lien, $titre, $les_titres) {
	return "href='$lien'";
}


// traitement pour #TITRE/mots : retrait des expressions regulieres
function cs_glossaire_titres($titre) {
	if(strpos($titre, ',')===false && strpos($titre, '=')===false) return $titre;
	list(,,$mots) = glossaire_parse($titre);
	return $mots;
}

// Cette fonction retire du texte les boites de definition et les liens du glossaire
function cs_retire_glossaire($texte) {
	$texte = preg_replace(',<span class="gl_(jst?|d[td])".*?</span>,s', '', $texte);
	if(!defined('_GLOSSAIRE_JS')) $texte = preg_replace(',<span class="gl_dl">.*?</span>,s', '', $texte);
	return preg_replace(',<a [^>]+class=\'cs_glossaire\'><span class=\'gl_mot\'>(.*?)</span></a>,s', '$1', $texte);
}
$GLOBALS['cs_introduire'][] = 'cs_retire_glossaire';

// remplace les accents unicode par l'equivalent charset/unicode/html
function glossaire_accents($regexpr) {
	if (strpos($regexpr, '&')===false) return $regexpr;
	return preg_replace_callback(",&#([0-9]+);,", 'glossaire_accents_callback', str_replace('& ','&amp; ',$regexpr));
}

// $matches est un caractere unicode sous forme &#XXX;
// ici on cherche toutes les formes de ce caractere, minuscule ou majuscule : unicode, charset et html
function glossaire_accents_callback($matches) {
	$u = unicode2charset($matches[0]);	// charset
	$u2 = init_mb_string()?mb_strtoupper($u):strtoupper($u);	// charset majuscule
	$u3 = htmlentities($u2, ENT_QUOTES, $GLOBALS['meta']['charset']);	// html majuscule
	$u4 = html2unicode($u3); // unicode majuscule
	$a = array_unique(array($u, $u2, htmlentities($u, ENT_QUOTES, $GLOBALS['meta']['charset']), $u3, $matches[0], $u4));
//	$a = array_unique(array($u, htmlentities($u, ENT_QUOTES, $GLOBALS['meta']['charset']), $matches[0]));
	return '(?:'.implode('|', $a).')';
}
function glossaire_echappe_balises_callback($matches) {
 global $gloss_ech, $gloss_ech_id;
 $gloss_ech[] = $matches[0];
 return '@@E'.$gloss_ech_id++.'@@';
}
function glossaire_echappe_mot_callback($matches) {
 global $gloss_mots, $gloss_mots_id, $gloss_id;
 $gloss_mots[] = $matches[0];
 return '@@M'.$gloss_mots_id++.'#'.$gloss_id.'@@';
}

function glossaire_safe($texte) {
	// on retire les notes avant propre()
	return safehtml(cs_propre(preg_replace(', *\[\[(.*?)\]\],msS', '', nl2br(trim($texte)))));
}

// renvoie le tableau des mots du glossaire
function glossaire_query_tab() {
	// interrogation personnalisee de la base
	if(defined('_GLOSSAIRE_QUERY')) {
		$res = array();
		$fetch = function_exists('sql_fetch')?'sql_fetch':'spip_fetch_array'; // Pour SPIP 1.92
		$query = function_exists('sql_query')?sql_query(_GLOSSAIRE_QUERY):spip_query(_GLOSSAIRE_QUERY); // Pour SPIP 1.92
		while($r = $fetch($query)) $res[] = $r;
		return $res;
	}
	return sql_allfetsel('id_mot,titre,texte,descriptif', 'spip_mots', 'type='.glossaire_groupes(), '', 'id_mot ASC');
}

function glossaire_redirection($titre) {
	$titre = preg_split(','.preg_quote(_GLOSSAIRE_TITRE_BASE_SEP,',').'\s*=,', $titre, 2);
	return array(trim($titre[0]), isset($titre[1])?trim($titre[1]):'');
}

// parse toutes les formes du titre d'un mot-cle du glossaire
// prendre en compte les formes du mot : architrave/architraves
function glossaire_parse($titre) {
	$mots = $regs = $titres = array(); $ok_mots = true;
	// cas d'une redirection
 	if(strpos($titre, '=')!==false) list($titre) = glossaire_redirection($titre);
	foreach(explode(_GLOSSAIRE_TITRE_BASE_SEP, str_replace('</','@@tag@@',$titre)) as $m) {
		// interpretation des expressions regulieres grace aux virgules : ,un +mot,i
		$m = trim(str_replace('@@tag@@','</',$m));
		if(strncmp($m,',',1)===0)
			$ok_mots = $ok_mots && !preg_match('/^,\w{1,3},$/', $regs[] = $m);
		else {
			$mots[] = charset2unicode($m);
			$titres[] = $m;
			$ok_mots = $ok_mots && mb_strlen($m)>3;
		}
	}
	if(count($titres))
		$titres = implode(_GLOSSAIRE_TITRE_SEP, $titres);
	elseif(count($regs)) {
		preg_match('/^,(.*),\w*$/', $regs[0], $rr);
		if (strpos($titres = $rr[1], '\\')!==false) {
			$titres = preg_replace('@\\\\([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|:,-])@', '$1', $titres);
			$titres = preg_replace(',\\\\[bswd],i', '', $titres);
		}
	} else
		$titres = '??';
	if(count($mots)) {
		$mots = array_unique($mots);
		array_walk($mots, 'cs_preg_quote');
		// expliciter l'apostrophe et les accents
		$mots = str_replace("'", "(?:'|&#8217;)", glossaire_accents(implode('|', $mots)));
	} else $mots = '';
	$ok_regexp = count($regs)?preg_replace($regs, 't', 'test', 1) !== null : true;
	return array($mots, $ok_regexp?$regs:array(), $titres, $ok_regexp, $ok_mots);
}

function glossaire_gogogo($texte, $mots, $limit, &$unicode) {
	// prudence 2 : on protege TOUTES les balises HTML comprenant le mot
	if (strpos($texte, '<')!==false)
		$texte = preg_replace_callback(",<[a-z][^>]*(?:$mots)[^>]*>,Ui", 'glossaire_echappe_balises_callback', $texte);
// echo "lesmots : $lesmots"; static $iii; echo '<hr>TEXTE #',++$iii,'<hr>',$texte,'<hr>';
	// prudence 3 : en iso-8859-1, (\W) comprend les accents, mais pas en utf-8... Donc on passe en unicode
	if(($GLOBALS['meta']['charset'] != 'iso-8859-1') && !$unicode)
		{ $texte = charset2unicode($texte); $unicode = true; }
	// prudence 4 : on neutralise le mot si on trouve un accent (HTML ou unicode) juste avant ou apres
	if (strpos($texte, '&')!==false) {
		$texte = preg_replace_callback(',&(?:'._GLOSSAIRE_ACCENTS.");(?:$mots),i", 'glossaire_echappe_balises_callback', $texte);
		$texte = preg_replace_callback(",(?:$mots)&(?:"._GLOSSAIRE_ACCENTS.');,i', 'glossaire_echappe_balises_callback', $texte);
	}
	// a chaque mot reconnu, on pose une balise temporaire cryptee
	return trim(preg_replace_callback(",(?<=\W)(?:$mots)(?=\W),i", 'glossaire_echappe_mot_callback', " $texte ", $limit));
}

// cette fonction n'est pas appelee dans les balises html echappees : html|code|cadre|frame|script|cite|acronym|abbr|a|etc...
// si $liste=true alors la fonction renvoie la liste des mots trouves
// chaque element du tableau renvoye est array('mot trouve', id_mot, 'lien mot', 'titre mot');
function cs_rempl_glossaire($texte, $liste = false) {
	global $gloss_tab1, $gloss_tab2, $gloss_id, $gloss_mots, $gloss_mots_id, $gloss_ech, $gloss_ech_id;
	// si [!glossaire] est trouve on sort
	if(strpos($texte, _CS_SANS_GLOSSAIRE)!==false)
		return $liste ? array() : str_replace(_CS_SANS_GLOSSAIRE, '', $texte);
	// mise en static de la table des mots pour eviter d'interrroger la base a chaque fois
	// attention aux besoins de memoire...
	static $limit, $glossaire_generer_url, $glossaire_generer_mot, $glossaire_array = NULL;
	if(!isset($glossaire_array)) {
		$glossaire_array = glossaire_query_tab();
		$glossaire_generer_url = function_exists('glossaire_generer_url') ? 'glossaire_generer_url' : 'glossaire_generer_url_dist';
		$limit = defined('_GLOSSAIRE_LIMITE') ? _GLOSSAIRE_LIMITE : -1;
		$glossaire_generer_mot = function($m) {
		return '<a ' . $GLOBALS["gloss_tab1"][$m[2]] . "_" . $GLOBALS["gl_i"]++
			. "' class='cs_glossaire'><span class='gl_mot'>"
			. (function_exists('glossaire_generer_mot')
			     ? glossaire_generer_mot($m[2], $GLOBALS['gloss_mots'][$m[1]])
			     : $GLOBALS['gloss_mots'][$m[1]]
			  )
			. "</span>" . $GLOBALS["gloss_tab2"][$m[2]] . "</a>";
		};
	}
	$unicode = false;
	// initialisation des globales d'echappement
	$gloss_ech = $gloss_mots = array();
	$gloss_ech_id = $gloss_mots_id = 0;
	// prudence 1 : protection des liens SPIP
	if(strpos($texte, '[') !== false)
		$texte = preg_replace_callback(',\[[^][]*->>?[^]]*\],msS', 'glossaire_echappe_balises_callback', $texte);
	// parcours de tous les mots, sauf celui qui peut faire partie du contexte (par ex : /spip.php?mot5)
	$mot_contexte = (isset($GLOBALS['contexte']['id_mot']) && $GLOBALS['contexte']['id_mot'])
		? $GLOBALS['contexte']['id_mot'] : _request('id_mot');
	foreach ($glossaire_array as $mot) if (($gloss_id = $mot['id_mot']) <> $mot_contexte) {
		// parser le mot-cle du glossaire
		// contexte de langue a prendre en compte ici
		list($les_mots, $les_regexp, $les_titres, $ok_regexp, $ok_mots) = glossaire_parse($titre = extraire_multi($mot['titre']));
		$mot_present = false;
		if(!$ok_regexp)
			spip_log(couteauprive_T('glossaire:nom') . '. ' . couteauprive_T('erreur_syntaxe').$titre);
		elseif(count($les_regexp)) {
			// prudence 2 : on protege QUELQUES balises HTML
			if (strpos($texte, '<')!==false)
				$texte = preg_replace_callback(",<(?:div|span|input) [^>]*>,Ui", 'glossaire_echappe_balises_callback', $texte);
			// a chaque expression reconnue, on pose une balise temporaire cryptee
			// ce remplacement est puissant, attention aux balises HTML ; par exemple, eviter : ,div,i
			$texte = preg_replace_callback($les_regexp, 'glossaire_echappe_mot_callback', $texte, $limit);
			// TODO 1 : sous PHP 5.0, un parametre &$count permet de savoir si un remplacement a eu lieu
			// et s'il faut construire la fenetre de glossaire.
			// TODO 2 : decrementer le parametre $limit pour $les_mots, si &$count est renseigne.
			// en attendant, construisons qd meme la fenetre...
			$mot_present = true;
		}
		if($les_mots && preg_match(",\W(?:$les_mots)\W,i", " $texte ")) {
			$texte = glossaire_gogogo($texte, $les_mots, $limit, $unicode);
			$mot_present = true;
		}
		// si un mot est trouve, on construit la fenetre de glossaire
		if($mot_present) {
			$lien = $glossaire_generer_url($gloss_id, $titre);
			// $definition = strlen($mot['descriptif'])?$mot['descriptif']:$mot['texte'];
			if($liste)
				// on ne renvoie que la liste des mots trouves
				$gloss_tab1[$gloss_id] = array($gloss_id, $lien, $les_titres);
			else {
				// l'attribut 'name' en fin de chaine est complete plus tard pour eviter les doublons :
				$gloss_tab1[$gloss_id] = (function_exists('glossaire_attributs_lien')
					?glossaire_attributs_lien($gloss_id, $lien, $titre, explode(_GLOSSAIRE_TITRE_SEP, $les_titres))
					:"href='$lien'") . " name='mot$gloss_id";
				$gloss_tab2[$gloss_id] = defined('_CS_PRINT')?'':recuperer_fond(
					defined('_GLOSSAIRE_JS')?'fonds/glossaire_js':'fonds/glossaire_css',
					array('id_mot' => $gloss_id, 'titre' => $les_titres,
						'texte' => glossaire_safe($mot['texte']),
						'descriptif' => glossaire_safe($mot['descriptif'])));
			}
		}
	}
	$GLOBALS['gl_i'] = 0;
	if($liste)
		$texte = (preg_match_all(',@@M(\d+)#(\d+)@@,', $texte, $reg, PREG_SET_ORDER)
			&& array_walk($reg,
				function (&$v) { $v = array_merge(array($GLOBALS['gloss_mots'][$v[1]]), $GLOBALS['gloss_tab1'][$v[2]]); })
		) ? $reg : array();
	else {
		// remplacement des echappements
		$texte = preg_replace_callback(',@@E(\d+)@@,', function($m) { return $GLOBALS['gloss_ech'][$m[1]]; }, $texte);
		// remplacement final des balises posees ci-dessus
		$texte = preg_replace_callback(',@@M(\d+)#(\d+)@@,', $glossaire_generer_mot, $texte);
	}
	// nettoyage
	unset($gloss_tab1, $gloss_tab2, $gloss_id, $gloss_mots, $gloss_mots_id, $gloss_ech, $gloss_ech_id);
	if($liste) return $texte;
	// ordre correct des balises en cas d'acronyme ou d'abreviation
	if(strpos($texte, '</span></a></a')!==false)
		$texte = preg_replace(',(<a(bbr|cronym) [^>]+>)(<a [^>]+class=\'cs_glossaire\'><span class=\'gl_mot\'>)(.*?)</span>(<span class="gl_.*?</span>)</a></a\\2>,smS', '$3$1$4</a$2></span>$5</a>', $texte);
	return $texte;
}

// filtre appliquant l'insertion du glossaire
function cs_glossaire($texte) {
	return cs_echappe_balises(_GLOSSAIRE_ECHAPPER, 'cs_rempl_glossaire', $texte);
}

// filtre renvoyant la liste des mots trouves dans le texte
function cs_mots_glossaire($texte, $type='', $sep='') {
	if(strpos($texte, "<span class='gl_mot'>")!==false && preg_match_all(",'gl_mot'>(.*?)</span>,", $texte, $reg)) {
		// glossaire deja present, on simplifie donc le texte
		$texte = implode('  ', $reg[1]);
	}
	// recuperation de la liste des mots
	$mots = cs_echappe_balises(_GLOSSAIRE_ECHAPPER, 'cs_rempl_glossaire', $texte, true);
	if(!count($mots)) return strlen($sep) ? '' : $mots;
	$titre_fct = strpos($type, '_unique')===false
		? function(&$v) { return str_replace("<br />", " / ", $v[3]); }
		: function(&$v) { return reset($ttt = explode(_GLOSSAIRE_TITRE_SEP, $v[3])); };
	switch($type) {
		case '': return $mots;
		case 'id_mot':
			array_walk($mots, function (&$v) { $v = $v[1]; });
			break;
		case 'mot':
			array_walk($mots, function(&$v) { $v = $v[0]; });
			break;
		case 'titre': case 'titre_unique':
			//array_walk($mots, create_function('&$v', "\$v=$titre;"));
			array_walk($mots, function(&$v) use($titre_fct) { $v = $titre_fct($v); });
			break;
		case 'lien_mot':
			//array_walk($mots, create_function('&$v', '$v="<a href=\"$v[2]\">$v[0]</a>";'));
			array_walk($mots, function(&$v) { $v = "<a href=\"$v[2]\">$v[0]</a>"; });
			break;
		case 'lien_titre': case 'lien_titre_unique':
			//array_walk($mots, create_function('&$v', '$v="<a href=\"$v[2]\">".'.$titre.'."</a>";'));
			array_walk($mots, function(&$v) use($titre_fct) { $v = "<a href=\"$v[2]\">" . $titre_fct($v) . "</a>"; });
			break;
		case 'nuage': case 'nuage_unique':
			$stats = array(); $min = 999999; $max = 0;
			foreach($mots as $m) $stats[$m[1]]++;
			$m = min($stats); $d = max($stats) - $m;
			// array_walk($stats, create_function('&$v',  $d?"\$v=round((\$v-$m)*9/$d)+1;":'$v=1;')); // valeurs de 1 a 10
			array_walk($stats, $d ? function(&$v) use($d,$m) { $v = round(($v-$m)*9/$d)+1; } : function(&$v) { $v = 1; }); // valeurs de 1 a 10
			// array_walk($mots, create_function('&$v,$k,&$s', '$v="<a href=\"$v[2]\" class=\"nuage".$s[$v[1]]."\">".'.$titre.'."</a>";'), $stats);
			array_walk($mots, function (&$v,$k,&$s) use($titre_fct) { $v = "<a href=\"$v[2]\" class=\"nuage" . $s[$v[1]] . "\">" . $titre_fct($v) . "</a>"; }, $stats);
			break;
		default: return "#GLOSSAIRE/$type?";
	}
	$mots = array_unique($mots);
	return strlen($sep) ? implode($sep, $mots) : $mots;
}

// fonction pipeline SPIP>=3.0
function glossaire_affiche_milieu($flux) {
	if($flux['args']['exec']=='mot') {
		$titre = sql_getfetsel('titre', 'spip_mots', '(id_mot='.intval($flux['args']['id_mot']).') AND (type='.glossaire_groupes().')');
		if(!$titre) return $flux; // Ce n'est pas un mot du glossaire
		include_spip('inc/presentation');
		$flux['data'] .= debut_cadre_relief(cs_icone(24), true).'<b>'
			. cs_lien(generer_url_ecrire('admin_couteau_suisse', 'cmd=descrip&outil=glossaire#cs_infos'), couteauprive_T('glossaire:nom')).'</b>'
			. '<br/>&nbsp; > <b>'._T('info_titre').'</b> '.htmlentities($titre, ENT_QUOTES, $GLOBALS['meta']['charset']);
		list(,$lien) = glossaire_redirection($titre);
		if($lien && $lien = calculer_url($lien, '', 'tout'))
			$flux['data'] .= '<br/>&nbsp; > <b>'._T('info_lien_hypertexte').'</b> '.cs_lien($lien['url'], $lien['titre']);
		$flux['data'] .= fin_cadre_relief(true);
	}
	return $flux;
}
?>