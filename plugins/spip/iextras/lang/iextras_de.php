<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/iextras?lang_cible=de
// ** ne pas modifier le fichier **

return [

	// A
	'action_associer' => 'Dieses Feld verwalten',
	'action_associer_title' => 'Anzeige dieses Zusatzfelds verwalten',
	'action_desassocier' => 'entfernen',
	'action_desassocier_title' => 'Anzeige dieses Zusatzfelds nicht mehr verwalten',
	'action_descendre' => 'Nach unten',
	'action_descendre_title' => 'Feld eine Position nach unten bewegen',
	'action_modifier' => 'ändern',
	'action_modifier_title' => 'Parameter des Zusatzfelds ändern',
	'action_monter' => 'Nach oben',
	'action_monter_title' => 'Feld eine Position nach oben bewegen',
	'action_supprimer' => 'löschen',
	'action_supprimer_title' => 'Feld aus der Datenbank löschen',
	'afficher_noms' => 'Bezeichnungen der Felder anzeigen',

	// B
	'bouton_importer' => 'importieren',

	// C
	'caracteres_autorises_champ' => 'Mögliche Zeichen : Buchstaben ohne Akzent, Ziffern, - und _',
	'caracteres_interdits' => 'Für dieses Feld ungeeignete Zeichen wurden verwendet.',
	'champ_deja_existant' => 'Ein Feld mit dieser Bezeichnung existiert bereits',
	'champ_sauvegarde' => 'Zusatzfeld gesichert !',
	'champs_extras' => 'Zusatzfelder',
	'champs_extras_de' => 'Zusatzfelder von : @objet@',

	// E
	'erreur_action' => 'Aktion @action@ ist unbekannt.',
	'erreur_enregistrement_champ' => 'Problem beim Anlegen des Zusatzfelds',
	'erreur_format_export' => 'Exportformat @format@ unbekannt.',
	'erreur_nom_champ_mysql_keyword' => 'Dieser Feldname ist ein reservierter Begriff von SQL und kann nicht verwendet werden .',
	'erreur_nom_champ_utilise' => 'Diese Bezeichnung wird bereits von SPIP oder einem Plugin verwendet.',
	'exporter_objet' => 'Zusatzfelder dieses Objekts exportieren : @objet@',
	'exporter_objet_champ' => 'Zusatzfelder exportieren : @objet@ / @nom@',
	'exporter_tous' => 'Alle Zusatzfelder exportieren',
	'exporter_tous_explication' => 'Alle Zusatzfelder im Format YAML exportieren, um sie in einem Importformular verwenden zu können.',
	'exporter_tous_php' => 'PHP-Export',
	'exporter_tous_php_explication' => 'Exportieren Sie im Format PHP zur Wiederverwendung in einem Plugin, das ausschließlich von Champs Extras Core abhängig ist.',

	// I
	'icone_creer_champ_extra' => 'Neues Zusatzfeld anlegen',
	'importer_explications' => 'Durch das Importieren von zusätzlichen Feldern auf diese Website werden
alle bereits vorhandenen zusätzlichen Felder durch die in der Importdatei angegebenen neuen Felder ergänzt.
Die neuen Felder werden an die bereits vorhandenen zusätzlichen Felder angehängt.',
	'importer_fichier' => 'Datei importieren',
	'importer_fichier_explication' => 'Export als YAML-Datei',
	'importer_fusionner' => 'Vorhandene Felder ändern',
	'importer_fusionner_explication' => 'Wenn auf der Website bereits zusätzliche Felder vorhanden sind, die importiert werden sollen,
werden diese beim Importieren standardmäßig ignoriert. Sie können jedoch
alle Informationen in diesen Feldern durch die Informationen in der importierten Datei ersetzen.',
	'importer_fusionner_non' => 'Bereits in der Website vorhandene Felder nicht modifizieren',
	'importer_fusionner_oui' => 'Mit importierten Daten übereinstimmende Felder modifizieren',
	'info_description_champ_extra' => 'Auf dieser Seite werden Zusatzfelder verwaltet.
						Das sind zusätzliche Datenfelder in der SPIP-Datenbank,
						die über Eingabefelder Daten erhalten.',
	'info_description_champ_extra_creer' => 'Sie können neue Felder erstellen, die dann
auf dieser Seite im Rahmen „Liste der redaktionellen Objekte” sowie in den Formularen angezeigt werden.',
	'info_modifier_champ_extra' => 'Zusatzfeld ändern',
	'info_nouveau_champ_extra' => 'Neues Zusatzfeld',
	'info_saisie' => 'Eingabe :',

	// L
	'label_attention' => 'Wichtige Erläuterungen',
	'label_champ' => 'Feldname',
	'label_class' => 'CSS-Klassen',
	'label_conteneur_class' => 'CSS Klassen des übergeordneten Containers',
	'label_critere_vu' => 'Dieses Feld wird vom Kriterium <code>{vu}</code> gewichtet',
	'label_datas' => 'Liste der Werte',
	'label_explication' => 'Erläuterung der Eingabe',
	'label_label' => 'Bezeichnung der Eingabe',
	'label_obligatoire' => 'Obligatorisches Feld ?',
	'label_rechercher' => 'Suche',
	'label_rechercher_ponderation' => 'Gewichtung in Suchen',
	'label_restrictions_auteur' => 'Nach Autor',
	'label_restrictions_branches' => 'Nach Zweig',
	'label_restrictions_compositions' => 'Nach Composition',
	'label_restrictions_groupes' => 'Nach Gruppe',
	'label_restrictions_secteurs' => 'Nach Sektor',
	'label_saisie' => 'Typ der Eingabe',
	'label_sql' => 'SQL-Definition',
	'label_table' => 'Objekt',
	'label_traitements' => 'Automatische Verarbeitung',
	'label_versionner' => 'Inhalte des Felds versionieren',
	'legend_declaration' => 'Deklaration',
	'legend_options_saisies' => 'Optionen der Eingabe',
	'legend_options_techniques' => 'Technisch',
	'legend_restriction' => 'Einschränkung',
	'legend_restrictions_modifier' => 'Eingabe Ändern',
	'legend_restrictions_voir' => 'Eingabe anzeigen',
	'liste_des_extras' => 'Liste der Zusatzfelder',
	'liste_des_extras_possibles' => 'Liste der nicht verwalteten Felder',
	'liste_objets_applicables' => 'Liste der SPIP-Objekte',

	// M
	'masquer_noms' => 'Feldnamen verbergen',

	// N
	'nb_element' => '1 Element',
	'nb_elements' => '@nb@ Elemente',

	// P
	'precisions_pour_attention' => 'Für sehr wichtige Hinweise.
		Mit Zurückhaltung zu verwenden !
		Kann einen Zeichenkette im Format « plugin:chaine » sein.',
	'precisions_pour_class' => 'Diesem Element CSS-Klassen zuordnen,
		mit Leerzeichen als Trenner. Beispiel : "inserer_barre_edition" für einen Block mit dem Plugin Porte Plume',
	'precisions_pour_explication' => 'Sie können zu dieser Eingabe zusätzliche Informationen bereitstellen.
		Das kann eine Zeichenkette im Format « plugin:chaine » in einer Sprachdatei sein.',
	'precisions_pour_label' => ' Kann eine Zeichenkette im Format « plugin:chaine » sein.',
	'precisions_pour_nouvelle_saisie' => 'Ermöglicht die Änderung des Typs der Eingabe für dieses Feld',
	'precisions_pour_rechercher' => 'Dieses Feld in Suchen einbeziehen ?',
	'precisions_pour_restrictions_branches' => 'Bezeichner der einzuschränkenden Zweige (Trenner « :»)',
	'precisions_pour_restrictions_compositions' => 'Bezeichnungen der einzuschränkenden Kompositionen (Trenner « :»)',
	'precisions_pour_restrictions_groupes' => 'Bezeichnungen der einzuschränkenden Gruppen (Trenner « :»)',
	'precisions_pour_restrictions_secteurs' => 'Bezeichnungen der einzuschränkenden Sektoren (séparateur « :»)',
	'precisions_pour_saisie' => 'Eingabe dieses Typs anzeigen :',
	'precisions_pour_traitements' => 'Eine automatische Verarbeitung 
		auf das Ergebnis im Feld  #NOM_DU_CHAMP anwenden :',
	'precisions_pour_versionner' => 'Die Versionierung erfolgt nur, wenn das Plugin
		« révisions » aktiv ist und das SPIP-Objekt des Felds versioniert wird.',

	// R
	'radio_restrictions_auteur_admin' => 'Nur Admins (auch für Rubriken)',
	'radio_restrictions_auteur_admin_complet' => 'Nur Haupt-Webmaster',
	'radio_restrictions_auteur_aucune' => 'Alle dürfen',
	'radio_restrictions_auteur_webmestre' => 'Nur Webmaster',
	'radio_traitements_aucun' => 'Kein',
	'radio_traitements_raccourcis' => 'Verarbeitung des SPIP-Kürzel (propre)',
	'radio_traitements_typo' => 'Nur typografische Verarbeitung (typo)',

	// S
	'saisies_champs_extras' => 'Von « Zusatzfelder »',
	'saisies_saisies' => 'Von « Eingaben »',
	'supprimer_reelement' => 'Dieses Feld löschen ?',

	// T
	'titre_iextras' => 'Zusatzfelder',
	'titre_iextras_exporter' => 'Zusatzfelder exportieren',
	'titre_iextras_exporter_importer' => 'Zusatzfelder importieren oder exportieren',
	'titre_iextras_importer' => 'Zusatzfelder importieren',
	'titre_page_iextras' => 'Zusatzfelder',

	// V
	'veuillez_renseigner_ce_champ' => 'Bitte füllen Sie dieses Feld aus !',
];
