<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

define('_NO_CACHE', -1);
define('_INTERDIRE_COMPACTE_HEAD_ECRIRE', true);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", "On");
define('SPIP_ERREUR_REPORT', E_ALL);
define('_TEST_EMAIL_DEST', 'pierrekuhn@ik.me');

define('_DOC_MAX_SIZE', 3000);