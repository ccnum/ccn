<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/saisies?lang_cible=pt_br
// ** ne pas modifier le fichier **

return [

	// A
	'afficher' => 'Exibir',
	'assets_global' => 'Carregar o javascript e os CSS em todas as páginas, na tag &lt;head&gt;',

	// B
	'bouton_parcourir_docs_article' => 'Procurar na matéria',
	'bouton_parcourir_docs_breve' => 'Procurar na nota',
	'bouton_parcourir_docs_rubrique' => 'Procurar na seção',
	'bouton_parcourir_mediatheque' => 'Procurar na mídiateca',

	// C
	'caracteres_restants' => 'caracteres restantes',
	'categorie_choix_label' => 'Escolha restrita',
	'categorie_defaut_label' => 'Diversos',
	'categorie_libre_label' => 'Campo livre',
	'categorie_objet_label' => 'Conteúdo editorial',
	'categorie_structure_label' => 'Estrutura',
	'coherence_afficher_si_appel' => '@label@: chamada incorreta a @erreurs@',
	'coherence_afficher_si_erreur_pluriel' => 'As exibições condicionais dos campos abaixo chamam campos inexistentes:',
	'coherence_afficher_si_erreur_singulier' => 'A exibição condicional do campo abaixo chama campos inexistentes:',
	'configuration' => 'Configuração do plugin Entradas de Formulário',
	'construire_action_annuler' => 'Cancelar',
	'construire_action_configurer' => 'Configurar',
	'construire_action_deplacer' => 'Mover',
	'construire_action_dupliquer' => 'Duplicar',
	'construire_action_dupliquer_copie' => '(cópia)',
	'construire_action_supprimer' => 'Excluir',
	'construire_ajouter_champ' => 'Incluir um campo',
	'construire_ajouter_groupe' => 'Incluir um grupo',
	'construire_attention_enregistrer' => 'Lembre-se de gravar as suas alterações!',
	'construire_attention_modifie' => 'O formulário abaixo é diferente do formulário inicial.Você tem a possibilidade de revertê-lo ao estado em que estava, antes das suas alterações.',
	'construire_attention_supprime' => 'As suas alterações incluem exclusões de campos. Por favor, confirme a gravação desta nova versão do formulário.',
	'construire_aucun_champs' => 'No momento, não há nenhum campo no formulário.',
	'construire_configurer_globales_label' => 'Configurar as opções globais',
	'construire_confirmer_supprimer_champ' => 'Você quer realmente excluir este campo?',
	'construire_info_nb_champs_masques' => '@nb@ campo(s) invisível(eis) o tempo de configurar o grupo.',
	'construire_position_explication' => 'Indique qual campo este deve preceder.',
	'construire_position_fin_formulaire' => 'No fim do formulário',
	'construire_position_fin_groupe' => 'No fim do grupo @groupe@',
	'construire_position_label' => 'Posição do campo',
	'construire_reinitialiser' => 'Reverter o formulário',
	'construire_reinitialiser_confirmer' => 'Você perderá todas as suas modificações. Quer realmente reverter à versão inicial do formulário?',
	'construire_verifications_label' => 'Verificação(ões) a realizar',
	'cvt_etapes_courante' => 'Passo @etape@ / @etapes@:  @label_etape@',

	// D
	'data_cols_label' => 'Respostas possíveis (em coluna)',
	'data_rows_label' => 'Perguntas (em linha)',

	// E
	'erreur_generique' => 'Há erros nos campos abaixo. Por favor, verifique as informações digitadas',
	'erreur_option_nom_unique' => 'Este nome já está sendo usado por outro campo e deve ser único, neste formulário.',
	'erreur_syntaxe_afficher_si' => 'Sintaxe incorreta do teste',
	'erreur_valeur_inacceptable' => 'Valor postado é inaceitável.',
	'etapes_formulaire' => 'Passos do formulário',
	'etapes_recapitulatif_label' => 'Resumo',
	'etapes_recapitulatif_texte' => 'Por favor, releia e verifique as suas respostas antes da validação final.',
	'explication_dev' => 'Para os devs',

	// F
	'fichier_erreur_explication_renvoi_alternative' => 'Você pode reenviar um novo arquivo, ou submeter o formulário assim como está, o arquivo anterior não é conservado.',
	'fichier_erreur_explication_renvoi_pas_alternative' => 'Você deve enviar um outro arquivo.',
	'format_date_attendu' => 'Informar uma data no formato dd/mm/aaaa.',
	'format_email_attendu' => 'Informar um endereço de e-mail no formato nome@dominio.com.br',

	// I
	'info_configurer_saisies' => 'Página de teste das entradas de dados',

	// L
	'label_annee' => 'Ano',
	'label_jour' => 'Dia',
	'label_mois' => 'Mês',

	// M
	'masquer' => 'Ocultar',

	// O
	'option_aff_art_interface_explication' => 'Exibir somente as matérias do idioma do visitante',
	'option_aff_art_interface_label' => 'Exibição multilíngue',
	'option_aff_langue_explication' => 'Exibe o idioma da matéria ou da seção selecionada antes do titulo',
	'option_aff_langue_label' => 'Exibir o idioma',
	'option_aff_rub_interface_explication' => 'Exibir apenas as seções do idioma do visitante',
	'option_aff_rub_interface_label' => 'Exibição multilíngue',
	'option_afficher_si_avec_post_explication' => 'Por padrão os valores das entradas ocultas pela exibição condicional não são postadas e, consequentemente, não são gravadas. Marque esta opção para alterar esse comportamento.',
	'option_afficher_si_avec_post_label' => 'Postar mesmo assim',
	'option_afficher_si_avec_post_label_case' => 'Postar o valor no caso de entrada ocultada',
	'option_afficher_si_explication' => 'Informe as condições para exibir o campo, em função do valor de outros campos. O identificador dos outros campos deve ser inserido entre <code>@</code>.<br />
Exemplo: <code>@selection_1@=="Toto"</code> condiciona a exibição do campo a que o campo  <code>selection_1</code> tenha o valor <code>Toto</code>.<br />
Pode-se usar operadores boleanos <code>||</code> (ou) e  <code>&&</code> (e)<br />
Você encontrará a <a href="https://contrib.spip.net/5080" target="_blank" rel="noopener noreferrer">documentação completa da sintaxe no site SPIP-contrib</a>.',
	'option_afficher_si_label' => 'Exibição condicional',
	'option_afficher_si_remplissage_uniquement_explication' => 'Marcando este checkbox, a exibição condicioinal se aplicará unicamente no preenchimento do formulário e não na exibição dos resultados.',
	'option_afficher_si_remplissage_uniquement_label' => 'Unicamente no preenchimento',
	'option_afficher_si_remplissage_uniquement_label_case' => 'Ocultar a entrada apenas no preenchimento',
	'option_attention_explication' => 'Uma mensagem mais importante que a explicação.',
	'option_attention_label' => 'Aviso',
	'option_attribut_title_label' => 'Valor com atributo title',
	'option_attribut_title_label_case' => 'Incluir um atributo title no label, contendo o valor técnico do campo. Use com moderação.',
	'option_attributs_explication' => 'Os atributos referem-se a cada campo HTML, inclusive para entradas com vários campos (<code>radio</code>, <code>checkbox</code> etc.).',
	'option_attributs_label' => 'Atributos HTML adicionais',
	'option_autocomplete_defaut' => 'Deixar por padrão',
	'option_autocomplete_explication' => 'Ao carregar a página, o seu navegador pode preencher previamente o campo em função do seu histórico',
	'option_autocomplete_label' => 'Preenchimento prévio do campo',
	'option_autocomplete_off' => 'Desativar',
	'option_autocomplete_on' => 'Ativar',
	'option_cacher_option_intro_label' => 'Esconder a primeira opção em branco.',
	'option_case_valeur_non_explication' => 'Valor postado se o checkbox não estiver selecionado. Atenção, trata-se de um valor técnico e não de um valor exibido.',
	'option_case_valeur_oui_explication' => 'Valor postado se o checkbox estiver selecionado. Atenção, trata-se de um valor técnico e não de um valor exibido.',
	'option_choix_alternatif_label' => 'Oferecer uma opção alternativa',
	'option_choix_alternatif_label_defaut' => 'Outra opção',
	'option_choix_alternatif_label_label' => 'Rótulo desta outra opção',
	'option_choix_destinataires_explication' => 'Lista de autores para seleção de destinatários pelo visitante que, por padrão, será a pessoa que configurou o site.',
	'option_choix_destinataires_label' => 'Destinatários possíveis',
	'option_class_label' => 'Classes CSS adicionais',
	'option_cols_explication' => 'Largura do bloco (em números de caracteres). Este opção não é sempre aplicável, já que os estilos CSS do seu site podem se sobrepor.',
	'option_cols_label' => 'Largura',
	'option_conteneur_class_label' => 'Classes CSS adicionais no container',
	'option_datas_explication' => 'Você deve informar uma opção por linha, no formato "chave|Label da opção".<br />
A chave deve ser única, curta, clara e não poderá ser alterada posteriormente.',
	'option_datas_explication_dev' => 'Disponibilizar uma lista de opções no formato de um vetor PHP (<code>array()</code>) ou SPIP (<code>#ARRAY</code>) do tipo <code>"chave"=>"Label da opção"</code>.',
	'option_datas_grille_explication' => 'Você deve informar uma opção por linha, no formato "chave|Label da opção" ou "chave|Label à esquerda|Label à direita"<br />
A chave deve ser única, curta, clara e não poderá ser alterada posteriormente.',
	'option_datas_grille_explication_dev' => 'Disponibilizar uma lista de opções, no formato de um vetor PHP (<code>array()</code>) ou SPIP (<code>#ARRAY</code>) do tipo <code>"chave"=>"Label da opção"</code> ou  <code>"chave"=>"Label à esquerda|Label à direita"</code>.',
	'option_datas_label' => 'Lista de opções aceitáveis',
	'option_datas_sous_groupe_explication' => 'Você deve indicar uma opção por linha, no formato "chave|Label" da opção.<br />
A chave deve ser única, curta, clara e não poderá ser alterada posteriormente.<br />
Você pode indicar o início de um subgrupo, no formato "*Título do subgrupo". Para encerrar um subgrupo, você pode iniciar um outro ou inserir uma linha contendo apenas "/*".',
	'option_datas_sous_groupe_explication_dev' => 'Disponibilizar uma lista de opções, no formato de um vetor PHP (<code>array()</code>) ou SPIP (<code>#ARRAY</code>), no formato <code>"chave" => "valor"</code>.<br />
Você pode reagrupar em subgrupo. Para isso, a <code>chave</code> deve ser o título do subgrupo, e o valor deve ser um vetor associativo do tipo <code>"chave" => "valor"</code>.',
	'option_defaut_label' => 'Valor padrão',
	'option_disable_avec_post_explication' => 'Igual na opção anterior, mas envia ainda o valor dentro um campo escondido.',
	'option_disable_avec_post_label' => 'Desativar mas enviar',
	'option_disable_choix_explication' => 'Informar as opções separadas por vírgulas, exemplo: <code>opção1,opção3</code>.',
	'option_disable_choix_explication_dev' => 'Informar as opções no formato de vetor, exemplo <code>["opção1","opção3"]</code>.',
	'option_disable_choix_label' => 'Desativar certas opções',
	'option_disable_explication' => 'O campo não pode mais obter foco.',
	'option_disable_label' => 'Desativar o campo',
	'option_erreur_obligatoire_explication' => 'Você pode personalizar a mensagem de erro exibida para indicar a obrigatoriedade (se não, deixe em branco).',
	'option_erreur_obligatoire_label' => 'Mensagem de erro pela obrigatoriedade',
	'option_explication_apres_attention' => 'Por questões de acessibilidade, explicações importantes devem ser sempre exibidas antes da entrada.',
	'option_explication_apres_label' => 'Explicações após o campo',
	'option_explication_explication' => 'Se necessário, uma frase curta descrevendo o objeto do campo.',
	'option_explication_label' => 'Explicação',
	'option_forcer_select_explication' => 'Se um grupo de palavras for selecionado, por padrão será uma entrada radio. Você pode forçar o uso de select.', # MODIF
	'option_forcer_select_label_case' => 'Forçar o uso de select',
	'option_groupe_affichage' => 'Exibição',
	'option_groupe_conditions' => 'Condições',
	'option_groupe_description' => 'Descrição',
	'option_groupe_utilisation' => 'Utilização',
	'option_groupe_validation' => 'Validação',
	'option_heure_pas_explication' => 'Ao usar o horário, é exibido um menu para ajudar na entrada de horas e minutos. Você pode escolher o intervalo de tempo entre cada opção (30 min por padrão)',
	'option_heure_pas_label' => 'Intervalo de minutos no menu de apoio à entrada de dados',
	'option_horaire_label' => 'Horário',
	'option_horaire_label_case' => 'Permitir informar também o horário',
	'option_id_explication' => 'Será automaticamente prefixado por <code>champ_</code>.',
	'option_id_groupe_label' => 'Grupo de palavras',
	'option_id_label' => 'Atributo <code>id</code> da entrada',
	'option_info_obligatoire_explication' => 'Você pode alterar o valor padrão da indicação de obrigatoriedade: <i>[Obrigatório]</i>. Para manter a informação padrão, deixar vazio. Para não exibir nada, insira apenas um espaço.',
	'option_info_obligatoire_label' => 'Indicação de obrigatoriedade',
	'option_inserer_barre_choix_edition' => 'barra de formatação completa',
	'option_inserer_barre_choix_forum' => 'barra dos fóruns',
	'option_inserer_barre_explication' => 'Inserir uma barra de ferramentas da Pena, se o plugin estiver ativo.',
	'option_inserer_barre_label' => 'Inserir uma barra de ferramentas ',
	'option_inserer_debut_label' => 'Código a inserir no início da entrada',
	'option_inserer_fin_label' => 'Código a inserir no fim da entrada',
	'option_label_case_label' => 'Rótulo localizado ao lado do checkbox',
	'option_label_explication' => 'O titulo que será exibido.',
	'option_label_label' => 'Rótulo',
	'option_label_non_explication' => 'Será visível na exibição dos resultados.',
	'option_label_non_label' => 'Label se o checkbox não estiver marcado',
	'option_label_oui_explication' => 'Será visível na exibição dos resultados.',
	'option_label_oui_label' => 'Label se o checkbox estiver marcado',
	'option_limite_branche_explication' => 'Limita a escolha a um ramo específico do site',
	'option_limite_branche_label' => 'Limitar a um ramo',
	'option_maximum_choix_explication' => 'Número máximo de opções?',
	'option_maximum_choix_label' => 'Limitar o número de opções',
	'option_maxlength_explication' => 'O visitante não poderá digitar mais do que este número de caracteres.',
	'option_maxlength_label' => 'Número máximo de caracteres.',
	'option_minlength_explication' => 'O visitante não poderá informar menos caracteres do que esse número.',
	'option_minlength_label' => 'Número mínimo de caracteres',
	'option_multiple_label' => 'Seleção múltipla',
	'option_nom_explication' => 'Um nome que identificará o campo.  Só pode conter letras minúsculas, números e o caracter "_".',
	'option_nom_label' => 'Nome do campo',
	'option_obligatoire_label' => 'Campo obrigatório',
	'option_onglet_label' => 'Aba',
	'option_onglet_label_case' => 'Exibir no formato de aba', # MODIF
	'option_onglet_vertical_explication' => 'Basta que uma única aba num grupo seja definida como vertical para que o conjunto de abas seja vertical.',
	'option_onglet_vertical_label_case' => 'Aba vertical',
	'option_option_destinataire_intro_label' => 'Rótulo da primeira opção em branco (quando em formato de lista)',
	'option_option_intro_label' => 'Rótulo da primeira opção em branco',
	'option_option_statut_label' => 'Exibir os status',
	'option_oui_non_valeur_non_explication' => 'Valor ostado se o não for selecionado.',
	'option_oui_non_valeur_oui_explication' => 'Valor postado se o sim for selecionado.',
	'option_placeholder_label' => 'Marcador de posição',
	'option_pliable_label' => 'Expansível',
	'option_pliable_label_case' => 'O grupo de campos poderá ser expandido', # MODIF
	'option_plie_label' => 'Já retraído',
	'option_plie_label_case' => 'Se o grupo de campos é expansível, ele já estará contraído na exibição do formulário.', # MODIF
	'option_poster_afficher_si_label_case' => 'Postar os valores de todas as entradas ocultadas',
	'option_previsualisation_explication' => 'Si o plugin Pena estiver ativo, adiciona uma aba para visualizar o texto digitado.',
	'option_previsualisation_label' => 'Ativar a visualização',
	'option_readonly_explication' => 'O campo pode ser lido, selecionado, mas não alterado.',
	'option_readonly_label' => 'Só leitura',
	'option_rows_explication' => 'Altura do bloco em número de linhas. Esta opção não é sempre aplicável, já que os estilos CSS do seu site poderão sobrepor-se.',
	'option_rows_label' => 'Número de linhas',
	'option_size_explication' => 'Largura do campo em número de caractéres. Esta opção não é sempre aplicável, já que os estilos CSS do seu site poderão sobrepor-se.',
	'option_size_label' => 'Tamanho do campo',
	'option_statut_label' => 'Status particular(es)',
	'option_type' => 'Tipo de entrada',
	'option_type_choix_label' => 'Tipo de opção',
	'option_type_choix_plusieurs' => 'Permitir que o visitante escolha <strong>diversos</strong> destinatários.',
	'option_type_choix_tous' => 'Incluir <strong>todos</strong> estes autores como destinatários. O usuário não terá nenhuma escolha.',
	'option_type_choix_un' => 'Permitir ao visitante escolher <strong>um único</strong> destinatário (no formato de lista).',
	'option_type_choix_un_radio' => 'Permite ao visitante escolher <strong>um único</strong> destinatário (no formato de checkboxes).',
	'option_type_color' => 'Cor',
	'option_type_explication' => 'Em modo "mascarado", o conteúdo do campo não será mostrado.',
	'option_type_label' => 'Tipo do campo',
	'option_type_password' => 'Texto mascarado durante o preenchimento (ex: senha).',
	'option_type_text' => 'Normal',
	'option_valeur_non_label' => 'Valor não',
	'option_valeur_oui_label' => 'Valor sim',
	'option_vue_masquer_sous_groupe' => 'Ao exibir o resultado, mostrar apenas o valor, sem o subgrupo',
	'options_dev_titre' => 'Opções para os devs',

	// P
	'plugin_yaml_inactif' => 'O plugin YAML está desativado. Você precisa ativá-lo para que esta página fique funcional.',

	// S
	'saisie_auteurs_explication' => 'Permite selecionar um ou mais autores',
	'saisie_auteurs_titre' => 'Autores',
	'saisie_case_explication' => 'Permite ativar ou desativar algo.',
	'saisie_case_titre' => 'Checkbox único',
	'saisie_checkbox_explication' => 'Permite escolher varias opções com checkboxes.',
	'saisie_checkbox_titre' => 'Checkboxes',
	'saisie_choix_grille_explication' => 'Permite apresentar uma série de perguntas de múltipla escolha de modo uniformizado no formato de grade',
	'saisie_choix_grille_titre' => 'Grade de perguntas',
	'saisie_date_explication' => 'Permite informar uma data com a ajuda de um calendário.',
	'saisie_date_titre' => 'Data',
	'saisie_destinataires_explication' => 'Permite escolher um ou mais destinatários entre contas pré-selecionadas.',
	'saisie_destinataires_titre' => 'Destinatários',
	'saisie_email_explication' => 'Permite ter um campos do tipo e-mail em HTML5.',
	'saisie_email_titre' => 'Endereço de e-mail',
	'saisie_explication_alerte_role_explication' => 'Se necessário, para a área restrita escolher apenas uma função de alerta (atributo html role: alert, status etc.).',
	'saisie_explication_alerte_role_label' => 'Função de alerta',
	'saisie_explication_alerte_type_explication' => 'Se necessário, unicamente para a área restrita escolher um tipo de alerta entre os propostos pelo SPIP (notice, error, success, info).',
	'saisie_explication_alerte_type_label' => 'Tipo de alerta',
	'saisie_explication_explication' => 'Um texto explicativo geral.',
	'saisie_explication_liens_meme_fenetre_label' => 'Abrir os links na mesma janela',
	'saisie_explication_masquer_label' => 'Incluir um botão exibir/ocultar a explicação',
	'saisie_explication_texte_label' => 'Texto da explicação',
	'saisie_explication_titre' => 'Explicação',
	'saisie_explication_titre_label' => 'Título da explicação',
	'saisie_fieldset_explication' => 'Uma área que poderá englobar vários campos.',
	'saisie_fieldset_titre' => 'Grupo de campos',
	'saisie_file_explication' => 'Envio de um arquivo',
	'saisie_file_titre' => 'Arquivo',
	'saisie_hidden_explication' => 'Um campo preenchido previamente, que o usuário não poderá ver.',
	'saisie_hidden_titre' => 'Campo invisível',
	'saisie_input_explication' => 'Uma simples linha de texto podendo ser visível ou mascarada (senha).',
	'saisie_input_titre' => 'Linha de texto',
	'saisie_mot_explication' => 'Uma ou mais palavras-chave de um grupo de palavras',
	'saisie_mot_titre' => 'Palavra-chave',
	'saisie_oui_non_explication' => 'Sim ou não, está claro? ;)',
	'saisie_oui_non_titre' => 'Sim ou não',
	'saisie_radio_defaut_choix1' => 'Um',
	'saisie_radio_defaut_choix2' => 'Dois',
	'saisie_radio_defaut_choix3' => 'Três',
	'saisie_radio_explication' => 'Permite escolher uma opção entre várias disponíveis.',
	'saisie_radio_titre' => 'Botões rádio',
	'saisie_selecteur_article' => 'Exibe um navegador de seleção de matéria',
	'saisie_selecteur_document' => 'Exibe um seletor de documento',
	'saisie_selecteur_rubrique' => 'Exibe um navegador de seleção de seção',
	'saisie_selecteur_rubrique_article' => 'Exibe um navegador de seleção de matéria ou de seção',
	'saisie_selecteur_rubrique_article_titre' => 'Matéria ou seção',
	'saisie_selection_explication' => 'Escolher uma opção em uma lista',
	'saisie_selection_multiple_explication' => 'Permite escolher várias opções em uma lista',
	'saisie_selection_multiple_titre' => 'Seleção múltipla',
	'saisie_selection_titre' => 'Lista dropdown / seleção',
	'saisie_textarea_explication' => 'Um campo de texto em várias linhas.',
	'saisie_textarea_titre' => 'Bloco de texto',
	'saisies_aplatir_tableau_montrer_groupe' => '@groupe@: @valeur@',

	// T
	'titre_page_saisies_doc' => 'Documentação das entradas de dados',
	'tous_visiteurs' => 'Todos os visitantes (mesmo os não registrados)',
	'tout_selectionner' => '(De)Selecionar tudo',

	// V
	'verifier_saisies_option_data_cle_manquante' => 'Sintaxe incorreta. Você confundiu o caracter pipe (|) com o L minúsculo (l)?',
	'verifier_saisies_option_data_cles_doubles' => 'Há pelo menos uma chave definida em duplicado',
	'verifier_saisies_option_data_sous_groupes_interdits' => 'Sintaxe incorreta. Os subgrupos não estão permitidos.',
	'verifier_saisies_option_data_verifier_cles_erreurs' => 'Sntaxe incorreta. Algumas chaves não correspondem aos critérios.',
	'verifier_valeurs_acceptables_explication' => 'Verifique se o valor postado se encontra entre os autorizados na definição do(s) campo(s). Não use esta opção se você preencher dinamicamente o(s) campo(s) nos eus templates ou os preencher via  JavaScript.',
	'verifier_valeurs_acceptables_label' => 'Verificar os valores possíveis',
	'vue_sans_reponse' => '<i>Sem resposta</i>',

	// Z
	'z' => 'zzz',
];
