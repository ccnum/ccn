<?php

/**
 * Plugin TarteAuCitron
 * Licence GPL3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/distant');
include_spip('inc/tarteaucitron');
/**
 * Mise à jour du fichier services/services.html
 * appelé avec ?action=tarteaucitron_referencer_services
 * autorisé pour les seuls webmestres
 */
function action_tarteaucitron_referencer_services_dist() {
	$url_json = 'https://tarteaucitron.io/json.php'; // URL correcte
	$json_file = recuperer_url($url_json);

	if (!$json_file || !isset($json_file['page'])) {
		echo "❌ Impossible de récupérer le JSON depuis $url_json\n";
		return;
	}

	$data = json_decode($json_file['page'], true);

	if (!is_array($data)) {
		echo "❌ Erreur : JSON invalide ou structure non attendue\n";
		return;
	}

	tarteaucitron_generer_modeles($data);
	tarteaucitron_generer_json($data);
}
