<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans https://git.spip.net/spip-contrib-extensions/tarteaucitron.git

return [

	// C
	'cfg_adblocker' => 'Afficher un message si un adblocker est détecté',
	'cfg_afficher_bandeau' => 'Afficher le bandeau ?',
	'cfg_afficher_bandeau_attention' => 'Il est hautement recommandé d’afficher le bandeau.',
	'cfg_afficher_bandeau_explication' => 'Si pas de bandeau le consentement est alors implicite.',
	'cfg_ajouter_modele' => 'Ajoutez le modèle que vous souhaitez @modeles@ à l’endroit où le service doit s’afficher',
	'cfg_aucun' => 'Aucun',
	'cfg_avance' => 'Réglages avancés',
	'cfg_bandeau_bas_page' => 'Bas de la page',
	'cfg_bandeau_haut_page' => 'Haut de la page',
	'cfg_bandeau_milieu' => 'Au milieu',
	'cfg_btn_acceptonly' => 'Bouton `Tout accepter` seulement',
	'cfg_btn_accetpall' => 'Boutons',
	'cfg_btn_accetpall_attention' => 'Il est fortement recommandé d’afficher ces boutons',
	'cfg_btn_accetpall_explication' => 'En plus du bouton ’Personnaliser’, faut-il afficher les boutons ’Tout accepter’ et ’Tout refuser’ ?',
	'cfg_btn_aucun' => 'Aucun bouton',
	'cfg_btn_deux' => 'Afficher les deux',
	'cfg_btn_showicon' => 'Afficher le Cookie ?',
	'cfg_btn_showicon_explication' => 'Ce bouton permet à l’internaute de ré-afficher le panneau de gestion des cookies à tout moment.',
	'cfg_choose_option' => 'Choisissez une option',
	'cfg_close_popup' => 'Afficher un bouton de fermeture sur le bandeau',
	'cfg_close_popup_explication' => 'Permet au visiteur de fermer le bandeau sans accepter ou refuser',
	'cfg_consent_mode' => 'Consentement Mode',
	'cfg_consent_mode_explication' => 'Si vous utilisez des services Google ads & GA4 ou Clarity & Bing Ads, vous pouvez désactiver le "consent mode"',
	'cfg_cookiedomain' => 'Domaine',
	'cfg_cookiedomain_explication' => 'Si le cookie est partagé avec d’autres sites du même domaine, veuillez renseigner ce champ.',
	'cfg_cookiename' => 'Nom du cookie',
	'cfg_cookieslist' => 'Afficher la liste des cookies installés ?',
	'cfg_cookieslist_explication' => 'Permet au visiteur de visualiser facilement les cookies actifs.',
	'cfg_desinstaller' => 'Désactiver',
	'cfg_display_service' => 'Afficher le service @service@',
	'cfg_exemple' => 'Exemple',
	'cfg_exemple_explication' => 'Explication de cet exemple',
	'cfg_explication' => '<li>Recherchez et activez votre service.</li>
							<li>Saisissez les éventuels paramètres des services activés.</li>
							<br>
							<div>Optionnel (suivant les services) :</div>
							<br>
							<li>Insérer le(s) modèle(s) éventuel(s) du service à l’endroit où vous voulez faire apparaître le service grâce à la balise #MODELE où directement dans du contenu éditorial via le porte-plume.</li>
							<li>Pour faire apparaître un raccourci vers le modèle <b>tac_mon_modele.html</b> dans le porte-plume, ajoutez une icône <b>squelettes/icones_barre/tac_mon_modele.png</b> de 17px de côté pour permettre aux rédacteurs d’insérer du contenu facilement.</li>
							<li>En cas de problème avec le plugin ou un service en particulier, créez une issue sur <a href="https://git.spip.net/spip-contrib-extensions/tarteaucitron/issues" target="_blank">le dépôt du plugin</a>.</li>',
	'cfg_externalcss' => 'Désactiver le CSS de TarteAuCitron',
	'cfg_externalcss_explication' => 'Permet d’utiliser des règles CSS personnalisées',
	'cfg_group_services' => 'Regrouper les services',
	'cfg_group_services_explication' => 'Par type de service',
	'cfg_icon' => 'Icône',
	'cfg_icon_explication' => 'Choisissez une image carrée de 50px de côté',
	'cfg_icon_texte' => 'Gestionnaire de cookies - ouverture d’une fenêtre',
	'cfg_iconposition' => 'Positionner le bouton',
	'cfg_image' => 'Image',
	'cfg_installer' => 'Activer',
	'cfg_mandatory' => 'Afficher les cookies obligatoires',
	'cfg_mandatory_explication' => 'Montre au visiteur que des cookies obligatoires non désactivables sont utilisés',
	'cfg_moreinfolink' => 'Afficher le lien `En savoir plus`',
	'cfg_nettoyer_iframes' => 'Traiter les anciens articles',
	'cfg_nettoyer_iframes_explication' => 'Le plugin TarteAuCitron pour SPIP fournit un script qui vous permettra de remplacer les iframes de vos anciens articles par les modèles adéquats. Au besoin, adaptez-le.<br>
										   Quand vous lancez le script, celui-ci lance d’abord une simulation. Si vous souhaitez que les modifications soient effectives, il faut ajouter <code>&modif=1</code> dans l’url.',
	'cfg_nettoyer_iframes_launch' => 'Lancer le script',
	'cfg_ouverture_type' => 'Type de bouton',
	'cfg_parametre_service' => 'Paramétrer le service <b>@service@</b>',
	'cfg_placement_bandeau' => 'Placement vertical du bandeau',
	'cfg_position_bd' => 'En bas à droite',
	'cfg_position_bg' => 'En bas à gauche',
	'cfg_position_hd' => 'En haut à droite',
	'cfg_position_hg' => 'En haut à gauche',
	'cfg_readmorelink' => 'URL du lien ’En savoir plus’',
	'cfg_readmorelink_explication' => 'Ex. : spip.php ?article1, rgpd, gestion-des-cookies, etc.',
	'cfg_remove_credit' => 'Supprimer les crédits',
	'cfg_remove_credit_attention' => 'En supprimant ce lien, vous enlevez de la visibilité pour les développeurs du projet Open Source TarteAuCitron.',
	'cfg_remove_credit_explication' => 'Enlève le lien vers le projet TarteAuCitron',
	'cfg_small_alert' => 'Petite alerte',
	'cfg_text_alertbigprivacy' => 'Texte',
	'cfg_text_alertbigprivacy_explication' => 'Modifier le texte par défaut du bandeau.<br>Note : ce champ accepte les Blocs multilingues',
	'cfg_text_disclaimer' => 'Texte d’avertissement',
	'cfg_text_disclaimer_explication' => 'Modifier le texte d’avertissement par défaut.<br>Note : ce champ accepte les Blocs multilingues',
	'cfg_text_info' => 'Info',
	'cfg_text_info_explication' => 'Modifier le texte d’info par défaut.<br>Note : ce champ accepte les Blocs multilingues',
	'cfg_titre_activation_services' => 'Services activés',
	'cfg_titre_ajouter_services' => 'Activation des services',
	'cfg_titre_bandeau' => 'Affichage',
	'cfg_titre_liste_services' => 'Liste des services',
	'cfg_titre_placeholder_recherche_services' => 'Instagram, Spotify, etc.',
	'cfg_titre_recherche_services' => 'Rechercher',
	'cfg_titre_result_recherche_services' => 'Résultats de recherche',
	'cfg_titre_technique' => 'Technique',

	// I
	'id_contenu' => 'Insérer l’id de votre contenu :',

	// L
	'legend_bandeau_principal' => 'Le bandeau principal',
	'legend_cookie' => 'Ouverture du panneau',
	'legend_cookies_management_panel' => 'Panneau de gestion des cookies',

	// S
	'services_fb_explication' => '',
	'services_fb_label' => 'Boutons de Like et Partage Facebook',
	'services_fb_pixel_explication' => 'Enter your FacebookPixel ID',
	'services_fb_pixel_label' => 'Facebook Pixel',
	'services_fb_pixel_placeholder' => 'YOUR_ID',
	'services_fb_placeholder' => '',
	'services_gmap_explication' => 'Enter your Google Map API Key',
	'services_gmap_label' => 'Google Map API Key',
	'services_gmap_placeholder' => 'API KEY',
	'services_gtag_explication' => 'Replace GA_MEASUREMENT_ID with the ID of the Google Analytics property to which you want to send data',
	'services_gtag_label' => 'Google global site tag (gtag.js)',
	'services_gtag_placeholder' => 'GA_MEASUREMENT_ID',
	'services_matomo_id_explication' => 'Remplacer par l’ID du site',
	'services_matomo_id_label' => 'Matomo ID',
	'services_matomo_id_placeholder' => 'YOUR_SITE_ID_FROM_MATOMO',
	'services_matomo_url_explication' => 'Renseigner l’URL du serveur Matomo',
	'services_matomo_url_label' => 'Matomo URL',
	'services_matomo_url_placeholder' => 'YOUR_MATOMO_URL',

	// T
	'tarteaucitron_titre' => 'Tarteaucitron',
	'titre_page_configurer_tarteaucitron' => 'Tarteaucitron',
];
