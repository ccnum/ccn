<?php
/**
 * Serveur de favicon SVG dynamique
 * Retourne le SVG correspondant au site SPIP en cours
 * Détecte le site par domaine ou par configuration SPIP
 */

// Récupérer l'hôte HTTP
$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : 'localhost';
// Supprimer le port s'il existe
$host = explode(':', $host)[0];

// Construire une liste de candidats à partir du domaine
$site_candidates = array();
if ($host !== 'localhost' && $host !== '127.0.0.1' && strpos($host, '.') !== false) {
    $site_candidates[] = strtolower(explode('.', $host)[0]);
} elseif (!empty($host) && $host !== 'localhost' && $host !== '127.0.0.1') {
    $site_candidates[] = strtolower($host);
}

// Essayer de charger SPIP et ajouter le nom de site SPIP comme candidat
define('_ECRIRE_INC_VERSION', '1');
@include(__DIR__ . '/ecrire/init.php');
if (isset($GLOBALS['meta']['nom_site_spip']) && !empty($GLOBALS['meta']['nom_site_spip'])) {
    $site_candidates[] = strtolower(preg_replace('/\s+/', '', $GLOBALS['meta']['nom_site_spip']));
}

// Normaliser et dédupliquer les candidats
$site_candidates = array_values(array_unique(array_filter($site_candidates, function ($value) {
    return trim($value) !== '';
})));

$svg_dir = __DIR__ . '/plugins/thematique/squelettes/img/pictos_sites/';
$site_name = null;
foreach ($site_candidates as $candidate) {
    $path = $svg_dir . $candidate . '.svg';
    if (file_exists($path)) {
        $site_name = $candidate;
        break;
    }
}

// Si aucun candidat trouvé, utiliser un fallback fiable
if (!$site_name) {
    if (file_exists($svg_dir . 'selfdata.svg')) {
        $site_name = 'selfdata';
    } else {
        $files = glob($svg_dir . '*.svg');
        if (!empty($files)) {
            $site_name = pathinfo($files[0], PATHINFO_FILENAME);
        }
    }
}

// Convertir le nom en nom de fichier SVG
$svg_filename = strtolower(preg_replace('/\s+/', '', $site_name ?? '')) . '.svg';

// Chemin du fichier SVG
$favicon_path = __DIR__ . '/plugins/thematique/squelettes/img/pictos_sites/' . $svg_filename;

// Servir le fichier
if (file_exists($favicon_path)) {
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: max-age=31536000, public');
    readfile($favicon_path);
    exit;
}

// Si le fichier SVG n'existe pas, retourner 404
header('HTTP/1.0 404 Not Found');
echo '404 - SVG favicon not found: ' . $svg_filename;
?>
