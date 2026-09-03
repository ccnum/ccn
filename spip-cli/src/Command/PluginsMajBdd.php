<?php

namespace Spip\Cli\Command;

use Spip\Cli\Plugins\ErrorsTrait;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'CoreMajBdd.php';

class PluginsMajBdd extends CoreMajBdd
{
    use ErrorsTrait;

    protected function configure(): void
    {
        $this
            ->setName('plugins:maj:bdd')
            ->setDescription('Mettre à jour la base de données et configurations des plugins.');
    }

    // actualiser les plugins
    // avant de se relancer pour finir les maj des plugins
    protected function upgrader()
    {
        spip_log('Mettre a jour les plugins', 'maj.' . _LOG_INFO_IMPORTANTE);
        $this->viderCache();

        include_spip('inc/texte');
        include_spip('inc/filtres');

        // ne pas arreter l'install en cours : timeout 24h
        if (!defined('_UPGRADE_TIME_OUT')) {
            define('_UPGRADE_TIME_OUT', 24 * 3600);
        }

        // on installe les plugins maintenant,
        // cela permet aux scripts d'install de faire des affichages (moches...)
        ob_start();
        plugin_installes_meta();
        $content = ob_get_clean();
        if ($content) {
            $this->presenterHTML($content);
        }

        // et on finit en rechargeant les options de CK
        $this->produireCacheCouteauKiss();

        // on purge les eventuelles erreurs d'activation
        $this->showPluginsErrors();

        if (trim($content)) {
            $this->io->text('Fin mise à jour des plugins');
        } else {
            $this->io->text('Aucune mise à jour de plugins');
        }

        spip_log('Fin de mise a jour plugins site', 'maj.' . _LOG_INFO_IMPORTANTE);
    }

    protected function produireCacheCouteauKiss()
    {
        include_spip('formulaires/configurer_ck');
        if (function_exists('formulaires_configurer_ck_charger_dist')) {
            $c = formulaires_configurer_ck_charger_dist();
            $code = ck_produire_code($c);
            ck_produire_options($code);
        }
    }
}
