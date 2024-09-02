<?php

// Pour qu'un fichier .php puisse invoquer ce bloc, il faut qu'il définisse :
/*
 * define('accès_autorisé', TRUE); // Sert à autoriser l'import du fichier env. NE PAS COMMIT !!!!!!!!!
 */

if (!defined('accès_autorisé')) {
	die('Direct access not permitted -> check env file for more information');
}
$mot_de_passe_import_utilisateurs = 'ohB3eey6waizahM0yeVi';
$database = 'thematiques';
$db_username = 'thematiques';
$db_password = 'g9kr458d';
