<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/saisies?lang_cible=es
// ** ne pas modifier le fichier **

return [

	// A
	'afficher' => 'Mostrar',
	'assets_global' => 'Cargue el javascript y CSS en todas las páginas, en la etiqueta &lt;head&gt;',

	// B
	'bouton_parcourir_docs_article' => 'Buscar artículo',
	'bouton_parcourir_docs_breve' => 'Buscar breve',
	'bouton_parcourir_docs_rubrique' => 'Buscar la sección',
	'bouton_parcourir_mediatheque' => 'Examinar mediateca',

	// C
	'caracteres_restants' => 'caracteres restantes',
	'categorie_choix_label' => 'Opción limitada',
	'categorie_defaut_label' => 'Diverso',
	'categorie_libre_label' => 'Campo libre',
	'categorie_objet_label' => 'Contenido editorial',
	'categorie_structure_label' => 'Estructura',
	'coherence_afficher_si_appel' => '@label@ : llamada incorrecta a @erreurs@',
	'coherence_afficher_si_erreur_pluriel' => 'Los campos condicionales que se muestran a continuación requieren campos no existentes:',
	'coherence_afficher_si_erreur_singulier' => 'El campo condicional que se muestra a continuación utiliza campos que no existen:',
	'configuration' => 'Configuración del plugin Saisies',
	'construire_action_annuler' => 'Anular',
	'construire_action_configurer' => 'Configurar',
	'construire_action_deplacer' => 'Mover',
	'construire_action_dupliquer' => 'Duplicar',
	'construire_action_dupliquer_copie' => '(copia)',
	'construire_action_supprimer' => 'Eliminar',
	'construire_ajouter_champ' => 'Añadir un campo',
	'construire_ajouter_groupe' => 'Agregar un grupo',
	'construire_attention_enregistrer' => '¡No olvide guardar sus cambios!',
	'construire_attention_modifie' => 'Este formulario es diferente al original. Tiene la posibilidad de restablecerlo conforme a su estado inical. ',
	'construire_attention_supprime' => 'Sus cambios implican suprimir campos. Confirme por favor esta nueva versión del formulario. ',
	'construire_aucun_champs' => 'Actualmente no existen campos en este formulario. ',
	'construire_configurer_globales_label' => 'Configurar opciones globales',
	'construire_confirmer_supprimer_champ' => '¿Desea eliminar realmente este campo?',
	'construire_info_nb_champs_masques' => '@nb@ campo(s) oculto(s) el tiempo de configurar el grupo.',
	'construire_position_explication' => 'Indique delante de qué otro campo se colocará.',
	'construire_position_fin_formulaire' => 'Al final del formulario',
	'construire_position_fin_groupe' => 'Al final del grupo @groupe@',
	'construire_position_label' => 'Posición del campo',
	'construire_reinitialiser' => 'Restablecer el formulario',
	'construire_reinitialiser_confirmer' => 'Va a perder todos los cambios. ¿Está seguro de volver al formulario original?',
	'construire_verifications_label' => 'Tipo de verificación a efectuar', # MODIF
	'cvt_etapes_courante' => 'Escenario @etape@ / @etapes@ :  @label_etape@',

	// D
	'data_cols_label' => 'Posibles respuestas (en columna)',
	'data_rows_label' => 'Preguntas (en línea)',

	// E
	'erreur_generique' => 'Hay errores en los siguientes campos, revise por favor sus entradas',
	'erreur_option_nom_unique' => 'Este nombre ya ha sido utilizado en otro campo, y ha de ser único en el formulario.',
	'erreur_syntaxe_afficher_si' => 'Sintaxis de prueba incorrecta',
	'erreur_valeur_inacceptable' => 'Valor publicado no es aceptable.',
	'etapes_formulaire' => 'Pasos del formulario',
	'etapes_recapitulatif_label' => 'Resumen',
	'etapes_recapitulatif_texte' => 'Por favor lea sus respuestas y verifíquelas antes de la validación final.',
	'explication_dev' => 'para los devs',

	// F
	'fichier_erreur_explication_renvoi_alternative' => 'Puede volver a enviar un archivo nuevo o enviar el formulario tal cual, el archivo antiguo no se conserva.',
	'fichier_erreur_explication_renvoi_pas_alternative' => 'Necesitas enviar otro archivo.',
	'format_date_attendu' => 'Ingrese una fecha en el formato dd/mm/aaaa.',
	'format_email_attendu' => 'Ingrese una dirección de correo electrónico en el formato vous@fournisseur.fr',

	// I
	'info_configurer_saisies' => 'Página de prueba de las entradas',

	// L
	'label_annee' => 'Año',
	'label_jour' => 'Día',
	'label_mois' => 'Mes',

	// M
	'masquer' => 'Ocultar',

	// O
	'option_aff_art_interface_explication' => 'Mostrar sólo los artículos en el idioma del usuario',
	'option_aff_art_interface_label' => 'Aparencia multilingüe',
	'option_aff_langue_explication' => 'Muestra el idioma del artículo o de la sección delante del título',
	'option_aff_langue_label' => 'Mostrar el idioma',
	'option_aff_rub_interface_explication' => 'Mostrar sólo las secciones en el idioma del usuario',
	'option_aff_rub_interface_label' => 'Apariencia multilingüe',
	'option_afficher_si_avec_post_explication' => 'De forma predeterminada, los valores de las entradas ocultas por la visualización condicional no se publican y, por lo tanto, no se guardan. Marque esta casilla para cambiar este comportamiento.',
	'option_afficher_si_avec_post_label' => 'Publica todo igual',
	'option_afficher_si_avec_post_label_case' => 'Publicar valor en caso de enmascaramiento de entrada',
	'option_afficher_si_explication' => 'Indique las condiciones para mostrar el campo en función del valor de los otros campos. El identificador de los otros campos debe ser indicarse entre <code>@</code>. <br />Ejemplo <code>@selection_1@=="Toto"</code> condiciona la visualización del campo con la condición de que el campo <code>selection_1</code> tenga por valor <code>Toto</code>.
Es posible utilizar los operadores booleanos <code>||</code> (o) y <code>&&</code> (and). <br />
Encontrará la <a href="https://contrib.spip.net/5080" target="_blank" rel="noopener noreferrer">documentación completa de la sintaxis en SPIP-contrib</a>.', # MODIF
	'option_afficher_si_label' => 'Visualización condicional',
	'option_afficher_si_remplissage_uniquement_explication' => 'Al marcar esta casilla, la visualización condicional se aplicará solo al completar el formulario, no al mostrar los resultados.',
	'option_afficher_si_remplissage_uniquement_label' => 'Solo al llenar',
	'option_afficher_si_remplissage_uniquement_label_case' => 'Ocultar entrada solo al llenar',
	'option_attention_explication' => 'Un mensaje más importante que la explicación.',
	'option_attention_label' => 'Aviso',
	'option_attribut_title_label' => 'Valor del atributo title',
	'option_attribut_title_label_case' => 'Ponga un atributo de título en la etiqueta, que contenga el valor técnico del campo. Para ser usado con moderación.',
	'option_attributs_explication' => 'Los atributos se relacionan con cada campo html, incluso para entradas con varios campos (<code>radio</code>, <code>checkbox</code>, etc.).',
	'option_attributs_label' => 'Atributos HTML adicionales',
	'option_autocomplete_defaut' => 'Dejar por defecto',
	'option_autocomplete_explication' => 'Al cargar la página, su navegador puede rellenar el campo en función de su historial',
	'option_autocomplete_label' => 'Pre-relleno del campo',
	'option_autocomplete_off' => 'Desactivar',
	'option_autocomplete_on' => 'Activar',
	'option_cacher_option_intro_label' => 'Esconder la primera opción vacía',
	'option_case_valeur_non_explication' => 'Valor publicado si la casilla no está marcada. Tenga en cuenta que este es un valor técnico y no un valor mostrado.',
	'option_case_valeur_oui_explication' => 'Valor publicado si la casilla está marcada. Tenga en cuenta que este es un valor técnico y no un valor mostrado.',
	'option_choix_alternatif_label' => 'Proponer una opción alternativa',
	'option_choix_alternatif_label_defaut' => 'Otra elección',
	'option_choix_alternatif_label_label' => 'Etiqueta de esta elección alternativa',
	'option_choix_destinataires_explication' => 'Lista de autores para una selección de destinatarios por parte del usuario de Internet, en su defecto será la persona que instaló el sitio.',
	'option_choix_destinataires_label' => 'Destinatarios posibles',
	'option_class_label' => 'Clases CSS adicionales',
	'option_cols_explication' => 'Ancho del bloque (en número de caracteres). Esta opción no se aplica siempre, porque puede ser cancelada por los estilos CSS de tu sitio.',
	'option_cols_label' => 'Ancho',
	'option_conteneur_class_label' => 'Clases CSS adicionales en el contenedor',
	'option_datas_explication' => 'Debe indicar una opción por línea en el formulario "clave|Etiqueta de elección".<br />La clave debe ser única, breve, clara y no debe modificarse posteriormente.',
	'option_datas_explication_dev' => 'Proporcione una lista de opciones en forma de una matriz PHP (<code>array()</code>) o SPIP (<code>#ARRAY</code>) de tipo <code>"clave"=>"Etiqueta de elección"</código>.',
	'option_datas_grille_explication' => 'Debe indicar una opción por línea en el formulario "clave|Etiqueta de elección" o "clave|Etiqueta a la izquierda|Etiqueta a la derecha"<br />La clave debe ser única, breve, clara y no debe modificarse posteriormente.',
	'option_datas_grille_explication_dev' => 'Proporcione una lista de opciones en forma de una matriz PHP (<code>array()</code>) o SPIP (<code>#ARRAY</code>) de tipo <code>"clave"=>"Etiqueta de elección"</code> o <code>"key"=>"Etiqueta a la izquierda|Etiqueta a la derecha"</code>.',
	'option_datas_label' => 'Lista de opciones posibles',
	'option_datas_sous_groupe_explication' => 'Debe indicar una opción por línea en el formulario "clave|Etiqueta" de la elección.<br />La clave debe ser única, breve, clara y no debe modificarse posteriormente.<br />Puede indicar el comienzo de una subgrupo en la forma "*Título del subgrupo". Para finalizar un subgrupo puede iniciar otro, o poner una línea que contenga solo "/*".',
	'option_datas_sous_groupe_explication_dev' => 'Proporcione una lista de opciones en forma de una tabla PHP (<code>array()</code>) o SPIP (<code>#ARRAY</code>) en forma de <code>"clave" => "valor"</code>.<br />Puede agrupar en subgrupos. Para hacer esto, la <code>clave</code> debe ser el título del subgrupo, y el valor en sí mismo debe ser una table asociativa de tipo <code>"clave" => "valor"</code>.',
	'option_defaut_label' => 'Valor por defecto',
	'option_disable_avec_post_explication' => 'Como la opción anterior, pero publica el valor en un campo escondido.',
	'option_disable_avec_post_label' => 'Deactivar pero enviar',
	'option_disable_choix_explication' => 'Indique las opciones separadas por una coma, ejemplo: <code>elección1, elección3</code>.',
	'option_disable_choix_explication_dev' => 'Indique las opciones en forma de tabla, ejemplo: <code>["elección1","elección3"]</code>.',
	'option_disable_choix_label' => 'Deshabilitar ciertas opciones',
	'option_disable_explication' => 'El campo ya no puede obtener el foco.',
	'option_disable_label' => 'Deactivar el campo',
	'option_erreur_obligatoire_explication' => 'Puede personalizar el mensaje de error mostrado para indicar una obligación (sino dejar en blanco).',
	'option_erreur_obligatoire_label' => 'Mensaje de error obligatorio',
	'option_explication_apres_attention' => 'Por razones de accesibilidad, las explicaciones importantes siempre deben estar antes de escribir.',
	'option_explication_apres_label' => 'Explicación después del campo',
	'option_explication_explication' => 'Si hace falta, una frase corta que describe el campo',
	'option_explication_label' => 'Explicación',
	'option_forcer_select_explication' => 'Si se selecciona un grupo de palabras, por defecto será una entrada de radio. Puede forzar el uso de una selección.', # MODIF
	'option_forcer_select_label_case' => 'Forzar el uso de un select',
	'option_groupe_affichage' => 'Apariencia',
	'option_groupe_conditions' => 'Condiciones',
	'option_groupe_description' => 'Descripción',
	'option_groupe_utilisation' => 'Uso',
	'option_groupe_validation' => 'Validación',
	'option_heure_pas_explication' => 'Cuando usa el horario, se muestra un menú para ayudar a introducir horas y minutos. Aquí puede elegir el intervalo de tiempo entre cada opción (por defecto 30 minutos).',
	'option_heure_pas_label' => 'Intervalo de minutos en el menú de ayuda a la entrada',
	'option_horaire_label' => 'Horario',
	'option_horaire_label_case' => 'Permite introducir también la hora',
	'option_id_explication' => 'Tendrá automáticamente el prefijo <code>campo_</code>.',
	'option_id_groupe_label' => 'Grupo de palabras-claves',
	'option_id_label' => 'Atributo <code>id</code> de la entrada',
	'option_info_obligatoire_explication' => 'Puede cambiar la indicación de requisito predeterminada: <i>[Obligatorio]</i>. Para mantener la información predeterminada, no deje nada. Para mostrar nada, coloque un texto compuesto solo de espacios.',
	'option_info_obligatoire_label' => 'Indicación de campo obligatorio',
	'option_inserer_barre_choix_edition' => 'Barra de edición completa',
	'option_inserer_barre_choix_forum' => 'barra de los foros',
	'option_inserer_barre_explication' => 'Insertar una barra tipográfica si ésta está activada.',
	'option_inserer_barre_label' => 'Insertar una barra de herramientas',
	'option_inserer_debut_label' => 'Código a insertar al principio de la entrada',
	'option_inserer_fin_label' => 'Código a insertar al final de la entrada',
	'option_label_case_label' => 'Etiqueta posicionada al lado de la casilla',
	'option_label_explication' => 'El título que se mostrará.',
	'option_label_label' => 'Etiqueta',
	'option_label_non_explication' => 'Será visible cuando se muestren los resultados.',
	'option_label_non_label' => 'Etiquetar si la casilla no está marcada',
	'option_label_oui_explication' => 'Será visible cuando se muestren los resultados.',
	'option_label_oui_label' => 'Etiqueta si la casilla está marcada',
	'option_limite_branche_explication' => 'Limitar la elección a una rama específica de un sitio',
	'option_limite_branche_label' => 'Limitar a una rama',
	'option_maximum_choix_explication' => '¿Número máximo de opciones?',
	'option_maximum_choix_label' => 'Limitar el número de opciones',
	'option_maxlength_explication' => 'El usuario no puede ingresar más caracteres que este número.',
	'option_maxlength_label' => 'Número máximo de caracteres',
	'option_multiple_label' => 'Selección múltiple',
	'option_nom_explication' => 'Un nombre informático que identificará el campo. Sólo puede contener caracteres alfanuméricos minúsculos o el carácter "_".',
	'option_nom_label' => 'Nombre del campo',
	'option_obligatoire_label' => 'Campo obligatorio',
	'option_onglet_label' => 'Pestaña',
	'option_onglet_label_case' => 'Mostrar como una pestaña.', # MODIF
	'option_onglet_vertical_explication' => 'Solo se necesita marcar una pestaña en un grupo como vertical para que todas las pestañas sean verticales.',
	'option_onglet_vertical_label_case' => 'Pestaña vertical',
	'option_option_destinataire_intro_label' => 'Etiqueta de la primera opción vacía (cuando esté en forma de lista)',
	'option_option_intro_label' => 'Etiqueta de la primera opción vacía',
	'option_option_statut_label' => 'Mostrar el estatus',
	'option_oui_non_valeur_non_explication' => 'Valor publicado si no se selecciona.',
	'option_oui_non_valeur_oui_explication' => 'Valor publicado si se selecciona sí.',
	'option_placeholder_label' => 'Placeholder',
	'option_pliable_label' => 'Desplegable',
	'option_pliable_label_case' => 'El grupo de campos se podrá contraer y desplegar.', # MODIF
	'option_plie_label' => 'Ya está contraido',
	'option_plie_label_case' => 'Si el grupo de campos se puede contraer, ya estará contraido cuando se enseñe el formulario.', # MODIF
	'option_poster_afficher_si_label_case' => '
Publicar valores de todas las entradas ocultas',
	'option_previsualisation_explication' => 'Si la barra tipográfica es activa, añade una pestaña de previsualización del texto.',
	'option_previsualisation_label' => 'Activar la previsualización',
	'option_readonly_explication' => 'El campo se puede leer, seleccionar, pero no se puede modificar.',
	'option_readonly_label' => 'Sólo lectura',
	'option_rows_explication' => 'Altura del bloque en número de líneas. Esta opción no se aplica siempre, porque puede ser cancelada por los estilos CSS de su sitio.',
	'option_rows_label' => 'Número de líneas',
	'option_size_explication' => 'Ancho del campo (número de caracteres). Esta opción no se aplica siempre, porque puede ser cancelada por los estilos CSS del sitio.',
	'option_size_label' => 'Tamaño del campo',
	'option_statut_label' => '
Estado(s) especial(es)',
	'option_type' => 'Tipo de entrada',
	'option_type_choix_label' => 'Tipo de elección',
	'option_type_choix_plusieurs' => 'Permitir que el internauta elija <strong>varios</strong> destinatarios.',
	'option_type_choix_tous' => 'Ponga a <strong>todas</strong> estas personas como destinatarios. El usuario no tendrá elección.',
	'option_type_choix_un' => 'Permitir que el usuario de Internet elija <strong>una sola persona</strong> (en forma de lista desplegable).',
	'option_type_choix_un_radio' => 'Permitir que el usuario de Internet elija <strong>solo una</strong> persona (en forma de lista con viñetas).',
	'option_type_color' => 'Color',
	'option_type_explication' => 'En modo "escondido", el contenido del campo no será visible.',
	'option_type_label' => 'Tipo del campo',
	'option_type_password' => 'Texto escondido mientras tecleando (por ej. contraseña)',
	'option_type_text' => 'Normal',
	'option_valeur_non_label' => 'Sin valor',
	'option_valeur_oui_label' => 'Valor si',
	'option_vue_masquer_sous_groupe' => 'Al mostrar el resultado, mostrar solo el valor, sin el subgrupo',
	'options_dev_titre' => 'Opciones para devs',

	// P
	'plugin_yaml_inactif' => 'El complemento YAML está inactivo. Debe activarlo para que esta página sea funcional.',

	// S
	'saisie_auteurs_explication' => 'Le permite seleccionar un autor, una autora o varios',
	'saisie_auteurs_titre' => 'Autores',
	'saisie_case_explication' => 'Permite activar o desactivar algo.',
	'saisie_case_titre' => 'Casilla única',
	'saisie_checkbox_explication' => 'Permite elegir varias opciones con las casillas a marcar.',
	'saisie_checkbox_titre' => 'Casillas a marcar',
	'saisie_choix_grille_explication' => 'Permite realizar una serie de preguntas de opción múltiple de forma estandarizada y en forma de cuadrícula',
	'saisie_choix_grille_titre' => 'Grilla de preguntas',
	'saisie_date_explication' => 'Le permite ingresar una fecha usando un calendario',
	'saisie_date_titre' => 'Fecha',
	'saisie_destinataires_explication' => 'Le permite elegir destinatarios de cuentas preseleccionadas.',
	'saisie_destinataires_titre' => 'Personas destinatarias',
	'saisie_email_explication' => 'Permite tener un campo de tipo de correo electrónico en HTML5.',
	'saisie_email_titre' => 'Dirección de correo electrónico',
	'saisie_explication_explication' => 'Una explicación general.',
	'saisie_explication_liens_meme_fenetre_label' => 'Abrir enlaces en la misma ventana',
	'saisie_explication_masquer_label' => 'Agregar un botón de explicación para mostrar/ocultar',
	'saisie_explication_texte_label' => 'Texto de explicación',
	'saisie_explication_titre' => 'Explicación',
	'saisie_explication_titre_label' => 'Explicación del título',
	'saisie_fieldset_explication' => 'Un marco que podrá englobar varios campos.',
	'saisie_fieldset_titre' => 'Grupo de campos',
	'saisie_file_explication' => 'Mandar un archivo',
	'saisie_file_titre' => 'Archivo',
	'saisie_hidden_explication' => 'Un campo precompletado que el usuario no podrá ver.',
	'saisie_hidden_titre' => 'Campo escondido',
	'saisie_input_explication' => 'Una sola línea de texto, que puede ser visible u ocultada (contraseña).',
	'saisie_input_titre' => 'Línea de texto',
	'saisie_mot_explication' => 'Palabras clave de un grupo de palabras',
	'saisie_mot_titre' => 'Palabra-clave',
	'saisie_oui_non_explication' => 'Sí o no, ¿está claro? :)',
	'saisie_oui_non_titre' => 'Sí o no',
	'saisie_radio_defaut_choix1' => 'Uno',
	'saisie_radio_defaut_choix2' => 'Dos',
	'saisie_radio_defaut_choix3' => 'Tres',
	'saisie_radio_explication' => 'Le permite elegir una de varias opciones disponibles.',
	'saisie_radio_titre' => 'Botones de opción',
	'saisie_selecteur_article' => 'Muestra un navegador de selección de artículo',
	'saisie_selecteur_document' => 'Muestra un selector de documento',
	'saisie_selecteur_rubrique' => 'Muestra un navegador de selección de sección',
	'saisie_selecteur_rubrique_article' => 'Muestra un navegador de selección de artículo o de sección',
	'saisie_selecteur_rubrique_article_titre' => 'Artículo o sección',
	'saisie_selection_explication' => 'Elegir una opción dentro de una lista desplegable.',
	'saisie_selection_multiple_explication' => 'Permite elegir varias opciones con una lista.',
	'saisie_selection_multiple_titre' => 'Selección múltiple',
	'saisie_selection_titre' => 'Lista desplegable / selección',
	'saisie_textarea_explication' => 'Un campo de texto sobre varias líneas.',
	'saisie_textarea_titre' => 'Bloque de texto',
	'saisies_aplatir_tableau_montrer_groupe' => '@groupe@ : @valeur@',

	// T
	'titre_page_saisies_doc' => 'Documentación de saisies',
	'tous_visiteurs' => 'Todos los visitantes (incluso no registrados)',
	'tout_selectionner' => '(Des) elección todo',

	// V
	'verifier_saisies_option_data_cle_manquante' => 'Sintaxis incorrecta. ¿Confundiría el carácter de barra vertical (|) con la L minúscula (l)?',
	'verifier_saisies_option_data_cles_doubles' => 'Al menos una clave está duplicada.',
	'verifier_saisies_option_data_sous_groupes_interdits' => 'Sintaxis incorrecta. No se permiten subgrupos.',
	'verifier_saisies_option_data_verifier_cles_erreurs' => 'Sintaxis incorrecta. Algunas claves no cumplen los criterios.',
	'verifier_valeurs_acceptables_explication' => 'Verificar que el valor publicado esté entre los autorizados al definir el(los) campo(s). No use esta opción si llena dinámicamente los campos en sus plantillas o los llena usando javascript.',
	'verifier_valeurs_acceptables_label' => 'Comprobar posibles valores',
	'vue_sans_reponse' => '<i>Sin respuesta</i>',

	// Z
	'z' => 'zzz',
];
