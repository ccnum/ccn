<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/saisies?lang_cible=it
// ** ne pas modifier le fichier **

return [

	// A
	'afficher' => 'Visualizzare',
	'assets_global' => 'Carica javascript e CSS su tutte le pagine, nel tag &lt;head&gt;',

	// B
	'bouton_parcourir_docs_article' => 'Sfoglia l’articolo',
	'bouton_parcourir_docs_breve' => 'Sfoglia la breve',
	'bouton_parcourir_docs_rubrique' => 'Sfoglia la rubrica',
	'bouton_parcourir_mediatheque' => 'Sfoglia la mediateca',

	// C
	'caracteres_restants' => 'caratteri rimanenti',
	'categorie_choix_label' => 'Scelta limitata',
	'categorie_defaut_label' => 'Varie',
	'categorie_libre_label' => 'Campo libero',
	'categorie_objet_label' => 'Contenuti editoriali',
	'categorie_structure_label' => 'Struttura',
	'configuration' => 'Configurazione del plugin Saisies',
	'construire_action_annuler' => 'Annulla',
	'construire_action_configurer' => 'Configura',
	'construire_action_deplacer' => 'Sposta',
	'construire_action_dupliquer' => 'Duplica',
	'construire_action_dupliquer_copie' => '(copia)',
	'construire_action_supprimer' => 'Elimina',
	'construire_ajouter_champ' => 'Aggiungi un campo',
	'construire_ajouter_groupe' => 'Aggiungi gruppo',
	'construire_attention_enregistrer' => 'Non dimenticare di salvare le tue modifiche!',
	'construire_attention_modifie' => 'Il modulo in oggetto è diverso dal modulo iniziale. Hai la possibilità di reinizializzare il suo stato a quello precedente alle modifiche.',
	'construire_attention_supprime' => 'Le modifiche includono l’eliminazione di alcuni campi. Conferma il salvataggio di questa nuova versione del modulo.',
	'construire_aucun_champs' => 'Al momento non è presente alcun campo in questo modulo.',
	'construire_configurer_globales_label' => 'Configura le opzioni globali',
	'construire_confirmer_supprimer_champ' => 'Vuoi veramente eliminare questo campo?',
	'construire_info_nb_champs_masques' => '@nb@ campo(i) con maschera. Configura il gruppo.',
	'construire_position_explication' => 'Indica prima di quale altro campo sarà spostato quello corrente.',
	'construire_position_fin_formulaire' => 'Alla fine del modulo',
	'construire_position_fin_groupe' => 'Alla fine del gruppo @groupe@',
	'construire_position_label' => 'Posizione del campo',
	'construire_reinitialiser' => 'Reinizializza il modulo',
	'construire_reinitialiser_confirmer' => 'Perderai tutte le modifiche. Sei sicuro di voler tornare al modulo iniziale?',
	'construire_verifications_label' => 'Tipo di verifica da effettuare', # MODIF
	'cvt_etapes_courante' => 'Step @etape@ / @etapes@ :  @label_etape@',

	// D
	'data_cols_label' => 'Possibili risposte (in colonna)',
	'data_rows_label' => 'Domande (in linea)',

	// E
	'erreur_generique' => 'Ci sono degli errori nei campi di seguito, si prega di verificare gli inserimenti',
	'erreur_option_nom_unique' => 'Questo nome è già utilizzato da un altro campo e deve essere univoco all’interno del modulo.',
	'erreur_syntaxe_afficher_si' => 'Sintassi errata del test',
	'erreur_valeur_inacceptable' => 'Valore inserito non valido',
	'etapes_formulaire' => 'Step del modulo',
	'etapes_recapitulatif_label' => 'Riepilogo',
	'etapes_recapitulatif_texte' => 'Si prega di rivedere le risposte e verificarle prima della convalida finale.',
	'explication_dev' => 'Per gli sviluppatori',

	// F
	'fichier_erreur_explication_renvoi_alternative' => 'Puoi inviare nuovamente un nuovo file o inviare il modulo così com’è, il vecchio file non viene mantenuto.',
	'fichier_erreur_explication_renvoi_pas_alternative' => 'Devi inviare un altro file.',
	'format_date_attendu' => 'Immettere una data nel formato gg/mm/aaaa.',
	'format_email_attendu' => 'Inserisci un indirizzo email nel formato nome@indirizzo.it',

	// I
	'info_configurer_saisies' => 'Pagina di test di Saisies',

	// L
	'label_annee' => 'Anno',
	'label_jour' => 'Giorno',
	'label_mois' => 'Mese',

	// M
	'masquer' => 'Nascondi',

	// O
	'option_aff_art_interface_explication' => 'Mostra unicamente gli articoli della lingua dell’utente',
	'option_aff_art_interface_label' => 'Visualizzazione multilingua',
	'option_aff_langue_explication' => 'Mostra la lingua dell’articolo o della rubrica selezionata davanti al titolo',
	'option_aff_langue_label' => 'Mostra la lingua',
	'option_aff_rub_interface_explication' => 'Mostra unicamente le rubriche della lingua dell’utente',
	'option_aff_rub_interface_label' => 'Visualizzazione multilingua',
	'option_afficher_si_avec_post_explication' => 'Per default, i valori delle voci nascoste dalla visualizzazione condizionale non vengono pubblicati e quindi non salvati. Seleziona questa casella per modificare questo comportamento.',
	'option_afficher_si_avec_post_label' => 'Posta comunque',
	'option_afficher_si_avec_post_label_case' => 'Mostra valore in caso di mascheramento dell’input',
	'option_afficher_si_explication' => 'Specificare le condizioni per visualizzare il campo in base al valore degli altri campi. L’identificatore degli altri campi deve essere inserito tra <code>@</code>. <br />Esempio<code>@selezione_1@=="Tizio"</code> condiziona la visualizzazione del campo in modo che il campo <code>selezione_1</code> abbia la chiave <code>Tizio</code>. <br />
Puoi usare gli operatori booleani <code>||</code> (or) e  <code>&&</code> (e). <br />
Troverai la <a href="https://contrib.spip.net/5080" target="_blank" rel="noopener noreferrer">documentazione completa sulla sintassi su SPIP-contrib</a>.', # MODIF
	'option_afficher_si_label' => 'Visualizzazione condizionata',
	'option_afficher_si_remplissage_uniquement_explication' => 'Selezionando questa casella, la visualizzazione condizionale verrà applicata solo durante la compilazione del modulo, non durante la visualizzazione dei risultati.',
	'option_afficher_si_remplissage_uniquement_label' => 'Solo durante il riempimento',
	'option_afficher_si_remplissage_uniquement_label_case' => 'Nascondi input solo durante il riempimento',
	'option_attention_explication' => 'Un messaggio più importante dei una spiegazione.',
	'option_attention_label' => 'Avvertimento',
	'option_attribut_title_label' => 'Valore attributo titolo',
	'option_attribut_title_label_case' => 'Inserisci un attributo title nell’etichetta, contenente il valore tecnico del campo. Utilizzare con moderazione.',
	'option_attributs_explication' => 'Gli attributi si riferiscono a ciascun campo html, anche per le voci con più campi (<code>radio</code>, <code>checkbox</code>, ecc...).',
	'option_attributs_label' => 'Attributi HTML aggiuntivi',
	'option_autocomplete_defaut' => 'Lascia predefinito',
	'option_autocomplete_explication' => 'Al caricamento della pagina, il tuo navigatore può preimpostare il campo in funzione della sua storia',
	'option_autocomplete_label' => 'Preimpostazione del campo',
	'option_autocomplete_off' => 'Disattiva',
	'option_autocomplete_on' => 'Attiva',
	'option_cacher_option_intro_label' => 'Nascondi la prima scelta vuota',
	'option_case_valeur_non_explication' => 'Valore pubblicato se la casella non è selezionata. Si prega di notare che questo è un valore tecnico e non un valore visualizzato.',
	'option_case_valeur_oui_explication' => 'Valore pubblicato se la casella è selezionata. Si prega di notare che questo è un valore tecnico e non un valore visualizzato.',
	'option_choix_alternatif_label' => 'Offri una scelta alternativa',
	'option_choix_alternatif_label_defaut' => 'Altra scelta',
	'option_choix_alternatif_label_label' => 'Etichetta di scelta alternativa',
	'option_choix_destinataires_explication' => 'Uno o più autori tra i quali l’utente potrà fare una scelta. Se non si seleziona niente, è l’autore che ha installato il sito che sarà scelto.',
	'option_choix_destinataires_label' => 'Possibili destinatari',
	'option_class_label' => 'Classi CSS supplementari',
	'option_cols_explication' => 'Larghezza del blocco in numero di caratteri. Questa opzione non è sempre applicata poichè gli stili CSS la possono annullare.',
	'option_cols_label' => 'Larghezza',
	'option_conteneur_class_label' => 'Classi CSS aggiuntive sul contenitore',
	'option_datas_explication' => 'E’ necessario indicare una scelta per riga nel modulo "cle|etichetta_scelta"<br />. La chiave deve essere univoca, breve, chiara e non deve essere modificata in seguito.<br />',
	'option_datas_grille_explication' => 'Devi indicare una scelta per riga nel modulo "chiave|Etichetta di scelta" oppure "chiave|Etichetta a sinistra|Etichetta a destra"<br />. La chiave deve essere unica, breve chiara e non deve essere modificata successivamente<br />',
	'option_datas_grille_explication_dev' => 'Fornire un elenco di scelte sotto forma di matrice PHP (<code>array()</code>) o SPIP (<code>#ARRAY</code>) di tipo <code>"cle"=>"Etichetta di scelta"</code> oppure <code>"cle"=>"Etichetta a sinistra|Etichetta a destra"</code>.',
	'option_datas_label' => 'Elenco delle scelte possibili',
	'option_datas_sous_groupe_explication' => 'E’ necessario indicare una scelta per riga nel modulo "chiave|Etichetta" della scelta.<br />La chiave deve essere univoca, breve, chiara e non deve essere modificata successivamente.<br />E’ possibile indicare l’inizio di una sottogruppo nella forma "*Titolo del sottogruppo". Per terminare un sottogruppo puoi iniziarne un altro, oppure inserire una riga contenente solo "/*".',
	'option_defaut_label' => 'Valore predefinito',
	'option_disable_avec_post_explication' => 'Identica all’opzione precedente ma invia lo stesso il valore in un campo nascosto.',
	'option_disable_avec_post_label' => 'Disattiva ma invia',
	'option_disable_choix_explication' => 'Indica le scelte separate da una virgola, esempio: <code>scelta1,scelta3</code>.',
	'option_disable_choix_explication_dev' => 'Indicare le scelte sotto forma di tabella, per esempio: <code>["scelta1","scelta3"]</code>.',
	'option_disable_choix_label' => 'Disabilita alcune scelte',
	'option_disable_explication' => 'Il campo non può ottenere il focus.',
	'option_disable_label' => 'Disattiva il campo',
	'option_erreur_obligatoire_explication' => 'È possibile personalizzare il messaggio di errore visualizzato per indicare l’obbligo (altrimenti lasciare vuoto).',
	'option_erreur_obligatoire_label' => 'Messaggio di errore per indicare l’obbligo',
	'option_explication_explication' => 'Se necessario, una frase breve che descrive il campo.',
	'option_explication_label' => 'Spiegazione',
	'option_forcer_select_explication' => 'Se viene selezionato un gruppo di parole, per impostazione predefinita sarà una voce radio. È possibile forzare l’uso di un select.', # MODIF
	'option_forcer_select_label_case' => 'Forza l’uso di un select',
	'option_groupe_affichage' => 'Visualizzazione',
	'option_groupe_description' => 'Descrizione',
	'option_groupe_utilisation' => 'Utilizzazione',
	'option_groupe_validation' => 'Validazione',
	'option_heure_pas_explication' => 'Quando si utilizza il programma, apparirà un menu per aiutare ad inserire ore e minuti. Qui puoi scegliere l’intervallo di tempo tra ogni scelta (per impostazione predefinita 30 minuti).',
	'option_heure_pas_label' => 'Intervallo di minuti nel menu di aiuto di input',
	'option_horaire_label' => 'Orario',
	'option_horaire_label_case' => 'Consenti anche l’inserimento dell’ora',
	'option_id_groupe_label' => 'Gruppo di parole',
	'option_info_obligatoire_explication' => 'E’ possibile modificare l’indicazione di obbligo per impostazione predefinita: <i>[Obbligatorio]</i>. Per mantenere le informazioni di default, non inserire niente. Per non visualizzare niente, metti un testo composto solo da spazio.',
	'option_info_obligatoire_label' => 'Indicazione obbligatorio',
	'option_inserer_barre_choix_edition' => 'barra del testo completa',
	'option_inserer_barre_choix_forum' => 'barra dei forum',
	'option_inserer_barre_explication' => 'Inserisci una barra del testo se disponibile (porte-plume attivo).',
	'option_inserer_barre_label' => 'Inserisci una barra di utility',
	'option_label_case_label' => 'Etichetta a lato della casella',
	'option_label_explication' => 'Il titolo che sarà mostrato.',
	'option_label_label' => 'Etichetta',
	'option_label_non_explication' => 'Sarà visibile durante la visualizzazione dei risultati.',
	'option_label_non_label' => 'Etichetta se la casella non è selezionata',
	'option_label_oui_explication' => 'Sarà visibile durante la visualizzazione dei risultati.',
	'option_label_oui_label' => 'Etichetta se la casella è selezionata',
	'option_limite_branche_explication' => 'Limitare la scelta a un ramo specifico del sito',
	'option_limite_branche_label' => 'Limita ad un ramo',
	'option_maximum_choix_explication' => 'Numero massimo di scelte?',
	'option_maximum_choix_label' => 'Limita il numero di scelte',
	'option_maxlength_explication' => 'L’utente non può digitare più caratteri del numero qui indicato.',
	'option_maxlength_label' => 'Numero massimo di caratteri',
	'option_multiple_label' => 'Scelta multipla',
	'option_nom_explication' => 'Un nome informatico che indentifica il campo. Deve contentere solo caratteri alfanumerici minuscoli o il carattere "_".',
	'option_nom_label' => 'Nome del campo',
	'option_obligatoire_label' => 'Campo obbligatorio',
	'option_onglet_label' => 'Scheda',
	'option_onglet_label_case' => 'Visualizza come scheda.', # MODIF
	'option_option_destinataire_intro_label' => 'Etichetta di prima scelta vuota (quando in forma di elenco)',
	'option_option_intro_label' => 'Etichetta del primo campo vuoto',
	'option_option_statut_label' => 'Mostra gli stati',
	'option_oui_non_valeur_non_explication' => 'Valore pubblicato se è selezionato no.',
	'option_oui_non_valeur_oui_explication' => 'Valore pubblicato se è selezionato si.',
	'option_placeholder_label' => 'Segnaposto',
	'option_pliable_label' => 'Richiudibile',
	'option_pliable_label_case' => 'Il gruppo di campi può essere chiuso.', # MODIF
	'option_plie_label' => 'Già chiuso',
	'option_plie_label_case' => 'Se il gruppo di campi è richiudibile, sarà già chiuso alla visualizzazione del modulo.', # MODIF
	'option_poster_afficher_si_label_case' => 'Mostra i valori di tutte le voci nascoste',
	'option_previsualisation_explication' => 'Se porte-plume è attivo, aggiungi una scheda per previsualizzare la resa del testo inserito.',
	'option_previsualisation_label' => 'Attiva la previsualizzazione',
	'option_readonly_explication' => 'Il campo può essere letto, selezionato, ma non modificato.',
	'option_readonly_label' => 'Sola lettura',
	'option_rows_explication' => 'Altezza del blocco in numero ri righe. Questa opzione non è sempre applicata poichè gli stili CSS del sito potrebbero annullarla.',
	'option_rows_label' => 'Numero di righe',
	'option_size_explication' => 'Larghezza del campo in numero di caratteri. Questa opzione non è sempre applicata poich%egrave; gli stili CSS del sito potrebbero annullarla.',
	'option_size_label' => 'Dimensione del campo',
	'option_statut_label' => 'Stato/i speciale/i',
	'option_type' => 'Tipo di input',
	'option_type_choix_label' => 'Tipo di scelta',
	'option_type_choix_plusieurs' => 'Consenti all’utente di scegliere <strong>più</strong> destinatari.',
	'option_type_choix_tous' => 'Imposta <strong>tutti</strong> questi autori come destinatari. L’utente non avrà alcuna scelta.',
	'option_type_choix_un' => 'Consenti all’utente di scegliere <strong>un solo</strong> destinatario (sotto forma di elenco a discesa)',
	'option_type_choix_un_radio' => 'Consenti all’utente di scegliere <strong>una singola</strong> persona (sotto forma di elenco puntato).',
	'option_type_color' => 'Colore',
	'option_type_explication' => 'In modalità "mascherata", il contenuto del campo non sarà visibile.',
	'option_type_label' => 'Tipo del campo',
	'option_type_password' => 'Testo nascosto durante l’inserimento (per esempio una password)',
	'option_type_text' => 'Normale',
	'option_valeur_non_label' => 'Valore no',
	'option_valeur_oui_label' => 'Valore si',
	'option_vue_masquer_sous_groupe' => 'Quando si visualizza il risultato, visualizzare solo il valore, senza il sottogruppo',
	'options_dev_titre' => 'Opzioni per gli sviluppatori',

	// P
	'plugin_yaml_inactif' => 'Il plugin YAML non è attivo. Devi attivarlo per far funzionare questa pagina.',

	// S
	'saisie_auteurs_explication' => 'Consente di selezionare uno o più autori',
	'saisie_auteurs_titre' => 'Autori',
	'saisie_case_explication' => 'Consente di attivare o disattivare qualcosa.',
	'saisie_case_titre' => 'Casella di spunta',
	'saisie_checkbox_explication' => 'Consente di scegliere più opzioni da spuntare.',
	'saisie_checkbox_titre' => 'Caselle di spunta',
	'saisie_choix_grille_explication' => 'Ti consente di porre una serie di domande a scelta multipla in un formato standard e a griglia',
	'saisie_choix_grille_titre' => 'Griglia delle domande',
	'saisie_date_explication' => 'Consente di inserire una data con l’aiuto di un calendario',
	'saisie_date_titre' => 'Data',
	'saisie_destinataires_explication' => 'Consente di scegliere uno o più destinatari tra gli autori selezionati.',
	'saisie_destinataires_titre' => 'Destinatari',
	'saisie_email_explication' => 'Permette di avere un campo di tipo email in HTML5.',
	'saisie_email_titre' => 'Indirizzo email',
	'saisie_explication_explication' => 'Un testo esplicativo generale.',
	'saisie_explication_liens_meme_fenetre_label' => 'Apri i link nella stessa finestra',
	'saisie_explication_masquer_label' => 'Aggiungi un pulsante mostra/nascondi spiegazione',
	'saisie_explication_texte_label' => 'Testo esplicativo',
	'saisie_explication_titre' => 'Spiegazione',
	'saisie_explication_titre_label' => 'Titolo esplicativo',
	'saisie_fieldset_explication' => 'Un blocco che può contenere più campi.',
	'saisie_fieldset_titre' => 'Gruppo di campi',
	'saisie_file_explication' => 'Invio di un file',
	'saisie_file_titre' => 'File',
	'saisie_hidden_explication' => 'Un campo preimpostato che l’utente non potrà vedere.',
	'saisie_hidden_titre' => 'Campo nascosto',
	'saisie_input_explication' => 'Una semplice riga di testo, che può essere visibile o mascherata (password).',
	'saisie_input_titre' => 'Riga di testo',
	'saisie_mot_explication' => 'Parole chiave da un gruppo di parole',
	'saisie_mot_titre' => 'Parola chiave',
	'saisie_oui_non_explication' => 'Si o no',
	'saisie_oui_non_titre' => 'Si o no',
	'saisie_radio_defaut_choix1' => 'Uno',
	'saisie_radio_defaut_choix2' => 'Due',
	'saisie_radio_defaut_choix3' => 'Tre',
	'saisie_radio_explication' => 'Consente di scegliere un’opzione tra più disponibili.',
	'saisie_radio_titre' => 'Scelta unica',
	'saisie_selecteur_article' => 'Mostra un navigatore per la selezione di un articolo',
	'saisie_selecteur_document' => 'Visualizza un selettore di documenti',
	'saisie_selecteur_rubrique' => 'Mostra un navigatore per la selezione di una rubrica',
	'saisie_selecteur_rubrique_article' => 'Mostra un navigatore per la selezione di un articolo o di una rubrica',
	'saisie_selecteur_rubrique_article_titre' => 'Selettore di articolo o di rubrica',
	'saisie_selection_explication' => 'Scegli una opzione nel menu a tendina.',
	'saisie_selection_multiple_explication' => 'Consente di scegliere più opzioni con un elenco.',
	'saisie_selection_multiple_titre' => 'Scelta multipla',
	'saisie_selection_titre' => 'Elenco a discesa/selezione',
	'saisie_textarea_explication' => 'Un campo di testo su più linee.',
	'saisie_textarea_titre' => 'Blocco di testo',
	'saisies_aplatir_tableau_montrer_groupe' => '@groupe@ : @valeur@',

	// T
	'titre_page_saisies_doc' => 'Documentazione',
	'tous_visiteurs' => 'Tutti gli utenti (anche non registrati)',
	'tout_selectionner' => '(De)seleziona tutto',

	// V
	'verifier_saisies_option_data_cle_manquante' => 'Sintassi errata. Forse hai scambiato il ​​carattere pipe (|) con la L minuscola (l)?',
	'verifier_saisies_option_data_cles_doubles' => 'Almeno una chiave è definita in duplicato.',
	'verifier_saisies_option_data_sous_groupes_interdits' => 'Sintassi errata. Non sono ammessi sottogruppi.',
	'verifier_saisies_option_data_verifier_cles_erreurs' => 'Sintassi errata. Alcune chiavi non soddisfano i criteri.',
	'verifier_valeurs_acceptables_explication' => 'Verificare che il valore inserito sia tra quelli autorizzati in fase di definizione del/i campo/i. Non utilizzare questa opzione se riempi dinamicamente i campi nei tuoi modelli o li riempi con Javascript.', # MODIF
	'verifier_valeurs_acceptables_label' => 'Verificare i valori possibili',
	'vue_sans_reponse' => '<i>Senza risposta</i>',

	// Z
	'z' => 'zzz',
];
