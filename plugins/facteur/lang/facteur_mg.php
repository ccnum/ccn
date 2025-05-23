<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/facteur?lang_cible=mg
// ** ne pas modifier le fichier **

return [

	// C
	'config_info_enregistree' => 'La configuration du facteur a bien été enregistrée',
	'configuration_adresse_envoi' => 'Adresse d’envoi par défaut',
	'configuration_facteur' => 'Facteur',
	'configuration_facteur_smtp_tls_allow_self_signed' => 'Validation du certificat SSL',
	'configuration_mailer' => 'Méthode d’envoi',
	'configuration_smtp' => 'Choix de la méthode d’envoi de mail',
	'configuration_smtp_descriptif' => 'Si vous n’êtes pas sûrs, choisissez la fonction mail de PHP.',
	'corps_email_de_test' => 'Ceci est un email de test accentué',

	// E
	'email_envoye_par' => 'Envoyé par @site@',
	'email_test_envoye' => 'L’email de test a correctement été envoyé. Si vous ne le recevez pas correctement, vérifiez la configuration de votre serveur ou contactez un administrateur du serveur.',
	'erreur' => 'Erreur',
	'erreur_confirm_ip_sans_hostname' => 'Voulez-vous vraiment utiliser cette adresse IP comme SMTP Host ?',
	'erreur_dans_log' => ' : consultez le fichier log pour plus de détails',
	'erreur_envoi_bloque_constante' => 'Envoi bloqué par la constante <tt>_TEST_EMAIL_DEST</tt>.
Vérifiez votre fichier <tt>mes_options.php</tt>',
	'erreur_generale' => 'Il y a une ou plusieurs erreurs de configuration. Veuillez vérifier le contenu du formulaire.',
	'erreur_invalid_host' => 'Ce nom d’hôte n’est pas correct',
	'erreur_invalid_port' => 'Ce numéro de port n’est pas correct',
	'erreur_ip_sans_hostname' => 'Cette adresse IP ne correspond à aucun nom de domaine.',

	// F
	'facteur_adresse_envoi_email' => 'Email :',
	'facteur_adresse_envoi_nom' => 'Nom :',
	'facteur_bcc' => 'Copie Cachée (BCC) :',
	'facteur_cc' => 'Copie (CC) :',
	'facteur_copies' => 'Copies',
	'facteur_copies_descriptif' => 'Un email sera envoyé en copie aux adresses définies. Une seule adresse en copie et/ou une seule adresse en copie cachée.',
	'facteur_email_test' => 'Envoyer un email de test à :',
	'facteur_filtre_accents' => 'Transformer les accents en leur entités html (utile pour Hotmail notamment).',
	'facteur_filtre_css' => 'Transformer les styles contenus entre <head> et </head> en des styles "en ligne", utile pour les webmails car les styles en ligne ont la priorité sur les styles externes.',
	'facteur_filtre_images' => 'Embarquer les images référencées dans les emails',
	'facteur_filtre_iso_8859' => 'Convertir en ISO-8859-1',
	'facteur_filtres' => 'Filtres',
	'facteur_filtres_descriptif' => 'Des filtres peuvent être appliqués aux emails au moment de l’envoi.',
	'facteur_smtp_auth' => 'Requiert une authentification :',
	'facteur_smtp_auth_non' => 'non',
	'facteur_smtp_auth_oui' => 'oui',
	'facteur_smtp_host' => 'Hôte :',
	'facteur_smtp_password' => 'Mot de passe :',
	'facteur_smtp_port' => 'Port :',
	'facteur_smtp_secure' => 'Connexion sécurisée :',
	'facteur_smtp_secure_non' => 'non',
	'facteur_smtp_secure_ssl' => 'SSL (déprécié)',
	'facteur_smtp_secure_tls' => 'TLS (recommandé)',
	'facteur_smtp_sender' => 'Adresse de retour des erreurs (optionnel)',
	'facteur_smtp_sender_descriptif' => 'Définit dans l’entête du mail l’adresse email de retour des erreurs (ou Return-Path)',
	'facteur_smtp_tls_allow_self_signed_non' => 'le certificat SSL du serveur SMTP est émis par une Autorité de Certification (recommandé).',
	'facteur_smtp_tls_allow_self_signed_oui' => 'le certificat SSL du serveur SMTP est auto-signé.',
	'facteur_smtp_username' => 'Nom d’utilisateur :',

	// I
	'info_envois_bloques_constante' => 'Tous les envois sont bloqués par la constante <tt>_TEST_EMAIL_DEST</tt>.',
	'info_envois_forces_vers_email' => 'Tous les envois sont forcés vers l’adresse <b>@email@</b> par la constante <tt>_TEST_EMAIL_DEST</tt>',

	// L
	'label_email_test_avec_piece_jointe' => 'Avec une pièce jointe',
	'label_email_test_from' => 'Expéditeur',
	'label_email_test_from_placeholder' => 'from@example.org (optionnel)',
	'label_email_test_important' => 'Cet email est important',
	'label_facteur_forcer_from' => 'Forcer cette adresse d’envoi quand le <tt>From</tt> n’est pas sur le même domaine',
	'label_message_envoye' => 'Message envoyé :',

	// M
	'message_identite_email' => 'La <a href="@url@">configuration du plugin <i>Facteur</i></a> surcharge cette adresse email avec <b>@email@</b> pour l’envoi de courriels.',

	// N
	'note_test_configuration' => 'Un email sera envoyé à cette adresse.',

	// P
	'personnaliser' => 'Personnaliser ces réglages',

	// S
	'sujet_alerte_mail_fail' => '[MAIL] FAIL envoi à @dest@ (était : @sujet@)',

	// T
	'tester' => 'Tester',
	'tester_la_configuration' => 'Tester la configuration',
	'titre_configurer_facteur' => 'Configuration de Facteur',

	// U
	'utiliser_mail' => 'Utiliser la fonction <tt>mail()</tt> de PHP',
	'utiliser_reglages_site' => 'Utiliser les réglages du site SPIP',
	'utiliser_smtp' => 'Utiliser SMTP',

	// V
	'valider' => 'Valider',
	'version_html' => 'Version HTML.',
	'version_texte' => 'Version texte.',
];
