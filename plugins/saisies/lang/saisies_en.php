<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/saisies?lang_cible=en
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'afficher' => 'Display',
	'assets_global' => 'Charger le javascript et les CSS sur toutes les pages, dans la balise &lt;head&gt;
Load javascript and CSS on all pages, in the &lt;head&gt; tag',

	// B
	'bouton_parcourir_docs_article' => 'Browse through the article',
	'bouton_parcourir_docs_breve' => 'Browse through the news item',
	'bouton_parcourir_docs_rubrique' => 'Browse through the section',
	'bouton_parcourir_mediatheque' => 'Browse through the multimedia library',

	// C
	'caracteres_restants' => 'remaining characters  ',
	'categorie_choix_label' => 'Limited selection',
	'categorie_defaut_label' => 'miscellaneous', # RELIRE
	'categorie_libre_label' => 'Free field',
	'categorie_objet_label' => 'Editorial content',
	'categorie_structure_label' => 'Structure',
	'configuration' => 'Configuration of the plugin Saisies', # RELIRE
	'construire_action_annuler' => 'Cancel',
	'construire_action_configurer' => 'Set up',
	'construire_action_deplacer' => 'Move',
	'construire_action_dupliquer' => 'Duplicate',
	'construire_action_dupliquer_copie' => '(copy)',
	'construire_action_supprimer' => 'Delete',
	'construire_ajouter_champ' => 'Add a field',
	'construire_ajouter_groupe' => 'Add a group',
	'construire_attention_enregistrer' => 'Remember to save your changes!',
	'construire_attention_modifie' => 'The form below is different from the initial form. You can reset it to the state before the changes.',
	'construire_attention_supprime' => 'Your changes include deletions of fields. Please confirm the registration of the new version of the form.',
	'construire_aucun_champs' => 'There is currently no field in this form.',
	'construire_configurer_globales_label' => 'Configure global options',
	'construire_confirmer_supprimer_champ' => 'Do you really want to delete this field?',
	'construire_info_nb_champs_masques' => '@nb@ hidden field(s) the time to set up the group.',
	'construire_position_explication' => 'Specify before which other field this one should be placed.',
	'construire_position_fin_formulaire' => 'At the end of the form',
	'construire_position_fin_groupe' => 'At the end of the group @groupe@',
	'construire_position_label' => 'Position of the field',
	'construire_reinitialiser' => 'Reset form',
	'construire_reinitialiser_confirmer' => 'You will lose all your changes. Are you sure you want to go back to the original form?',
	'construire_verifications_label' => 'Checking to be performed',
	'cvt_etapes_courante' => 'Step @etape@ / @etapes@ :  @label_etape@',

	// D
	'data_cols_label' => 'Possible answers (down)',
	'data_rows_label' => 'Questions ( across)',

	// E
	'erreur_generique' => 'There are errors in the fields below, please check your inputs',
	'erreur_option_nom_unique' => 'This name is already used by another field and it must be unique in this form.',
	'erreur_syntaxe_afficher_si' => 'Wrong syntax of the test',
	'erreur_valeur_inacceptable' => 'Entered value cannot be accepted.',
	'etapes_formulaire' => 'Steps of the form',
	'etapes_recapitulatif_label' => 'Overview',
	'etapes_recapitulatif_texte' => 'Please review your answers and check them before final validation.',

	// F
	'fichier_erreur_explication_renvoi_alternative' => 'You can resend a new file, or submit the form as is, the old file is not kept.',
	'fichier_erreur_explication_renvoi_pas_alternative' => 'You can send another file.',
	'format_date_attendu' => 'Enter a date in dd/mm/yyyy format.',
	'format_email_attendu' => 'Enter an email address in the format you@provider.tld',

	// I
	'info_configurer_saisies' => 'Test page for Entries',

	// L
	'label_annee' => 'Year',
	'label_jour' => 'Day',
	'label_mois' => 'Month',

	// M
	'masquer' => 'Hide',

	// O
	'option_aff_art_interface_explication' => 'Display only the articles in the user’s language',
	'option_aff_art_interface_label' => 'Multilingual display',
	'option_aff_langue_explication' => 'Display the selected language of the article or section before the title',
	'option_aff_langue_label' => 'Display the language',
	'option_aff_rub_interface_explication' => 'Display only the sections in the user’s language',
	'option_aff_rub_interface_label' => 'Multilingual display',
	'option_afficher_si_avec_post_explication' => 'By default, the values of entries hidden by the conditional display are not posted, and therefore not saved. Check this box to change this behaviour.',
	'option_afficher_si_avec_post_label' => 'Post anyway',
	'option_afficher_si_avec_post_label_case' => 'Post the value in case of entry masking',
	'option_afficher_si_explication' => 'Specify the conditions to display the field based on the value of the other fields. The identifier of the other fields has to be entered between <code>@</code>. <br />Example <code>@selection_1@=="Toto"</code> conditions the display of the field only when field <code>selection_1</code> has a value of <code>Toto</code>.<br />
It is possible to use Boolean operators <code> || </code> (or) and <code> && </code> (and).<br/>
You will find <a href="https://contrib.spip.net/5080" target="_blank" rel="noopener noreferrer">the full documentation of the syntax on contrib.spip</a>.', # MODIF
	'option_afficher_si_label' => 'Conditional display',
	'option_afficher_si_remplissage_uniquement_explication' => 'By checking this box, the conditional display will only apply when filling in the form, not when displaying the results.',
	'option_afficher_si_remplissage_uniquement_label' => 'Only when filling the form',
	'option_afficher_si_remplissage_uniquement_label_case' => 'Hide entries only during filling ',
	'option_attention_explication' => 'A message more important than the explanation.',
	'option_attention_label' => 'Warning',
	'option_attribut_title_label' => 'Title attribute value',
	'option_attribut_title_label_case' => 'Put a title attribute in the label, containing the technical value of the field. Use sparingly.',
	'option_autocomplete_defaut' => 'Keep default',
	'option_autocomplete_explication' => 'At page load, your browser may pre-fill the field based on its history',
	'option_autocomplete_label' => 'Pre-fill the field',
	'option_autocomplete_off' => 'Disable',
	'option_autocomplete_on' => 'Enable',
	'option_cacher_option_intro_label' => 'Hide the first empty choice',
	'option_case_valeur_non_explication' => 'Posted value if the checkbox is not selected. Attention, this is a technical value and not a displayed value.',
	'option_case_valeur_oui_explication' => 'Posted value if the checkbox is selected. Attention, this is a technical value and not a displayed value.',
	'option_choix_alternatif_label' => 'Suggest an alternative choice',
	'option_choix_alternatif_label_defaut' => 'Other choice',
	'option_choix_alternatif_label_label' => 'Label for this alternative choice',
	'option_choix_destinataires_explication' => 'One or several authors among which the user can select as recipients. Otherwise, it will be the person who installed the site.',
	'option_choix_destinataires_label' => 'Possible recipients',
	'option_class_label' => 'Additional CSS Classes',
	'option_cols_explication' => 'Field width in characters. This option is not always applied/used because the CSS styles of your site may override it.',
	'option_cols_label' => 'Width',
	'option_conteneur_class_label' => 'Additional CSS classes on the container',
	'option_datas_explication' => 'You must specify a choice for each row in the form of "key|label of the choice".<br />The key must be unique, short, unambiguous and not prone to be changed later.<br />', # MODIF
	'option_datas_grille_explication' => 'You must indicate one choice per line in the form "key|choice label" or "key|left label|right label"<br />The key must be unique, short, clear and must not be changed afterwards.<br />', # MODIF
	'option_datas_label' => 'List of the available choices',
	'option_datas_sous_groupe_explication' => 'You can indicate a choice by line using the format "key|Label" of the choice. <br />The key must be unique, brief, clear and must not be changed afterwards.<br />You can indicate the start of a subgroup using the format "*Subgroup title". To end a subgroup you can start another one, or put a line containing only  "/*".',
	'option_defaut_label' => 'Default value',
	'option_disable_avec_post_explication' => 'Same as previous option position but still post value in a hidden field.',
	'option_disable_avec_post_label' => 'Disabled but posted.',
	'option_disable_choix_explication' => 'Indicate the choices separated by a comma, example: choix1,choix3', # MODIF
	'option_disable_choix_label' => 'Disable some choices',
	'option_disable_explication' => 'The field can not get the focus.',
	'option_disable_label' => 'Disable the field',
	'option_erreur_obligatoire_explication' => 'You can customize the error message displayed to show an obligation (otherwise leave blank).',
	'option_erreur_obligatoire_label' => 'Error message for the obligation',
	'option_explication_explication' => 'If necessary, a short sentence describing the subject field.',
	'option_explication_label' => 'Explanation',
	'option_forcer_select_explication' => 'If a group of words is selected, by default it will be a radio entry. You can force the use of a select.', # MODIF
	'option_forcer_select_label_case' => 'Force the use of a select',
	'option_groupe_affichage' => 'Display',
	'option_groupe_description' => 'Description',
	'option_groupe_utilisation' => 'Usage',
	'option_groupe_validation' => 'Validation',
	'option_heure_pas_explication' => 'When using the schedule, a menu is displayed to help enter hours and minutes. Here you can choose the time interval between each option (default 30 minutes).',
	'option_heure_pas_label' => 'Interval of the minutes in the help menu of the input',
	'option_horaire_label' => 'Schedule',
	'option_horaire_label_case' => 'Allow to fill in the time',
	'option_id_groupe_label' => 'Keyword group',
	'option_info_obligatoire_explication' => 'You can change the default required indication: <i>[Required]</i>. To keep the default information, leave it blank. To display nothing, put a text composed only of space.',
	'option_info_obligatoire_label' => 'Indication of obligation',
	'option_inserer_barre_choix_edition' => 'complete editing toolbar',
	'option_inserer_barre_choix_forum' => 'forums toolbar',
	'option_inserer_barre_explication' => 'Insert a porte-plume toolbar if that tool is activated.',
	'option_inserer_barre_label' => 'Insert a toolbar',
	'option_label_case_label' => 'Label located beside the check box',
	'option_label_explication' => 'The title that will be displayed.',
	'option_label_label' => 'Label',
	'option_label_non_explication' => 'Will be visible when displaying the results.',
	'option_label_non_label' => 'Label if the box is not checked',
	'option_label_oui_explication' => 'Will be visible when displaying the results.',
	'option_label_oui_label' => 'Label if the box is checked',
	'option_limite_branche_explication' => 'Limit the choice to one specific branch of the site',
	'option_limite_branche_label' => 'Limit to one branch',
	'option_maximum_choix_explication' => 'Maximum number of choices?',
	'option_maximum_choix_label' => 'Limit the number of choices',
	'option_maxlength_explication' => 'The user can not type more characters than this number.',
	'option_maxlength_label' => 'Maximum number of characters',
	'option_multiple_label' => 'Multiple selection',
	'option_nom_explication' => 'A computer ID name that identifies the field. It may only contain lowercase alphanumeric characters or the underscore character "_".',
	'option_nom_label' => 'Field name',
	'option_obligatoire_label' => 'Required field',
	'option_onglet_label' => 'Tab',
	'option_onglet_label_case' => 'Display as a tab.', # MODIF
	'option_option_destinataire_intro_label' => 'Label of first choice empty (in list format)',
	'option_option_intro_label' => 'Label for the first empty choice',
	'option_option_statut_label' => 'Show the status',
	'option_oui_non_valeur_non_explication' => 'Posted value if no is selected.',
	'option_oui_non_valeur_oui_explication' => 'Posted value if yes is selected.',
	'option_placeholder_label' => 'Placeholder',
	'option_pliable_label' => 'Expandable',
	'option_pliable_label_case' => 'The group of fields can be expanded or shrunk.', # MODIF
	'option_plie_label' => 'Already shrunk',
	'option_plie_label_case' => 'If the group of fields can be expanded and shrunk, then this option will make it already shrink with the form displays.', # MODIF
	'option_poster_afficher_si_label_case' => 'Post the values of all hidden entries',
	'option_previsualisation_explication' => 'If porte-plume is activated, add a tab to preview the appearance of the text entered.',
	'option_previsualisation_label' => 'Activate previews',
	'option_readonly_explication' => 'The field can be viewed, selected, but not modified.',
	'option_readonly_label' => 'Read only',
	'option_rows_explication' => 'Field height in lines. This option is not always applied/used because the CSS styles of your site can cancel it.',
	'option_rows_label' => 'Lines number',
	'option_size_explication' => 'Field width in characters. This option is not always applied/used because the CSS styles of your site can cancel it.',
	'option_size_label' => 'Field size',
	'option_statut_label' => 'Specific status',
	'option_type' => 'Type of entry',
	'option_type_choix_label' => 'Type of choice',
	'option_type_choix_plusieurs' => 'Allow the user to choose <strong>several</ strong> message recipients.',
	'option_type_choix_tous' => 'Put <strong>all</strong> of these people as recipients. The user will have no choice.',
	'option_type_choix_un' => 'Allow the user to choose <strong>one person</strong> (as a drop-down list).',
	'option_type_choix_un_radio' => 'Allow the user to select <strong>only</strong>  one person (in checklist format).',
	'option_type_color' => 'Color',
	'option_type_explication' => 'In "disguised" mode, the field contents as typed will be replaced with asterisks.',
	'option_type_label' => 'Field type',
	'option_type_password' => 'Text, hidden during input (eg. password)',
	'option_type_text' => 'Normal',
	'option_valeur_non_label' => 'Value No',
	'option_valeur_oui_label' => 'Value Yes',
	'option_vue_masquer_sous_groupe' => 'When displaying the result, show only the value, without the subgroup',

	// P
	'plugin_yaml_inactif' => 'The YAML plugin is inactive. You must enable it for this page to be functional.',

	// S
	'saisie_auteurs_explication' => 'Allows you to select one or more authors',
	'saisie_auteurs_titre' => 'Authors',
	'saisie_case_explication' => 'Used to activate or deactivate a particular option.',
	'saisie_case_titre' => 'Single check box',
	'saisie_checkbox_explication' => 'Used to select several options using check boxes.',
	'saisie_checkbox_titre' => 'Check boxes',
	'saisie_choix_grille_explication' => 'Allows a series of multiple-choice questions to be asked in a standardized manner and in a grid format',
	'saisie_choix_grille_titre' => 'Question grid',
	'saisie_date_explication' => 'Allows you to enter a date using a calendar',
	'saisie_date_titre' => 'Date',
	'saisie_destinataires_explication' => 'Allows you to choose recipients from pre-selected accounts.',
	'saisie_destinataires_titre' => 'Recipients',
	'saisie_email_explication' => 'Allows to have an email type field in HTML5.',
	'saisie_email_titre' => 'E-mail adress',
	'saisie_explication_explication' => 'A general explanatory description.',
	'saisie_explication_liens_meme_fenetre_label' => 'Open links in the same window',
	'saisie_explication_masquer_label' => 'Add a show/hide explanation button',
	'saisie_explication_texte_label' => 'Text of the explanation',
	'saisie_explication_titre' => 'Explanation',
	'saisie_explication_titre_label' => 'Title of the explanation',
	'saisie_fieldset_explication' => 'A frame which may include several fields.',
	'saisie_fieldset_titre' => 'Fieldset',
	'saisie_file_explication' => 'Send a file',
	'saisie_file_titre' => 'File',
	'saisie_hidden_explication' => 'A pre-filled field that the user will never see.',
	'saisie_hidden_titre' => 'Hidden field',
	'saisie_input_explication' => 'A simple line of text that can be visible or hidden (password).',
	'saisie_input_titre' => 'Textfield',
	'saisie_mot_explication' => 'Keywords from a word group',
	'saisie_mot_titre' => 'Keyword',
	'saisie_oui_non_explication' => 'Either a Yes or No response',
	'saisie_oui_non_titre' => 'Yes or No',
	'saisie_radio_defaut_choix1' => 'One',
	'saisie_radio_defaut_choix2' => 'Two',
	'saisie_radio_defaut_choix3' => 'Three',
	'saisie_radio_explication' => 'Allows you to choose one of several available options.',
	'saisie_radio_titre' => 'Radio buttons',
	'saisie_selecteur_article' => 'Display an article selection browser',
	'saisie_selecteur_document' => 'Display a document selector',
	'saisie_selecteur_rubrique' => 'Display a section selector browser',
	'saisie_selecteur_rubrique_article' => 'Display an article or section selector browser',
	'saisie_selecteur_rubrique_article_titre' => 'Article or section',
	'saisie_selection_explication' => 'Select an option from a dropdown list box.',
	'saisie_selection_multiple_explication' => 'Used for selecting several options from a list.',
	'saisie_selection_multiple_titre' => 'Multiple selection',
	'saisie_selection_titre' => 'Drop-down list (or selection)', # MODIF
	'saisie_textarea_explication' => 'A multilines text field.',
	'saisie_textarea_titre' => 'Textarea',
	'saisies_aplatir_tableau_montrer_groupe' => '@groupe@: @valeur@',

	// T
	'titre_page_saisies_doc' => 'Documentation of input fields',
	'tous_visiteurs' => 'All visitors (even if not registered)',
	'tout_selectionner' => '(Un)select all',

	// V
	'verifier_saisies_option_data_cle_manquante' => 'Incorrect syntax. Are you mixing up the pipe character (|) with the lower case L (l)?',
	'verifier_saisies_option_data_cles_doubles' => 'At least one key is defined as duplicate.',
	'verifier_saisies_option_data_sous_groupes_interdits' => 'Incorrect syntax. Subgroups are not allowed.',
	'verifier_saisies_option_data_verifier_cles_erreurs' => 'Incorrect syntax. Some keys do not meet the criteria.',
	'verifier_valeurs_acceptables_explication' => 'Check that the posted value is among those allowed when defining fields. Do not use this option if you dynamically fill fields in your templates or fill them with javascript.', # MODIF
	'verifier_valeurs_acceptables_label' => 'Check the acceptable values', # MODIF
	'vue_sans_reponse' => '<i>(no data entered)</i>',

	// Z
	'z' => 'zzz'
);
