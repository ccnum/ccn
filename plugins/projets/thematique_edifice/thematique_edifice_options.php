<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Edifice : un prof ne publie pas de ressource, seuls les intervenants et les
// administrateurs le peuvent (cf thematique_peut_publier_ressource()).
define('_THEMATIQUE_RESSOURCE_SANS_PROF', true);

// Logo CCN du menu haut : site des CCN plutôt que la page projet d'Erasme.
define('_THEMATIQUE_URL_LOGO_CCN', 'https://classeculturellenumerique.org/');
