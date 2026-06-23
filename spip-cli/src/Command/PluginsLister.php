<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Spip\Cli\Plugins\ErrorsTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsLister extends Command
{
    use ErrorsTrait;

    public function showActifs(InputInterface $input, $raw = false)
    {
        $options = [
            'dist' => null,
            'procure' => false,
            'php' => false,
            'spip' => false,
            'compatibles' => null,
            'incompatibles' => null,
        ];
        $list = [];

        if ($input->getOption('php')) {
            $options['php'] = true;
            $options['procure'] = true;
            $list[] = 'Uniquement les extensions PHP procurées';
        }

        if ($input->getOption('procure')) {
            $options['procure'] = true;
            $list[] = 'Uniquement les plugins procurés';
        }

        if ($input->getOption('dist')) {
            $options['dist'] = true;
            $list[] = 'Uniquement les plugins-dist';
        } elseif ($input->getOption('no-dist')) {
            $options['dist'] = false;
            $list[] = 'Sans les plugins-dist';
        }

        [$options, $list] = $this->buildOptionsCompatibles($input, $options, $list);

        if ($input->getOption('spip')) {
            $options['spip'] = true;
            $list[] = 'Uniquement SPIP';
        }

        if ($list && !$raw) {
            $this->io->listing($list);
        }

        $actifs = $this->getPluginsActifs($options);
        $actifs = $this->filterPluginsCompatibles($actifs, $options);
        $this->showPlugins($actifs, $input->getOption('short'), $raw);
    }

    public function showInactifs(InputInterface $input, $raw = false)
    {

        $options = [
            'compatibles' => null,
            'incompatibles' => null,
        ];

        $list = ['Liste des plugins inactifs'];
        [$options, $list] = $this->buildOptionsCompatibles($input, $options, $list);

        if (!$raw) {
            $this->io->listing($list);
        }
        $inactifs = $this->getPluginsInactifs();
        $inactifs = $this->filterPluginsCompatibles($inactifs, $options);
        $this->showPlugins($inactifs, $input->getOption('short'), $raw);
    }

    public function getExportFile(InputInterface $input)
    {
        $name = $input->getOption('name') . '.txt';
        return _DIR_TMP . $name;
    }

    public function exportActifs(InputInterface $input, $raw = false)
    {
        $file = $this->getExportFile($input);

        $modes = [
            'procure' => false,
            'php' => false,
        ];
        if ($input->getOption('dist')) {
            $modes['dist'] = true;
        } elseif ($input->getOption('no-dist')) {
            $modes['dist'] = false;
        }

        $actifs = $this->getPluginsActifs($modes);

        $list = implode($raw ? "\n" : ' ', array_map('strtolower', array_keys($actifs)));
        if (file_put_contents($file, $list . "\n")) {
            $this->io->check('Export dans : ' . $file);
        } else {
            $this->io->fail('Export raté : ' . $file);
        }
        $this->io->text($list);
        // $this->io->columns(explode(" ", $list), 6, true);
    }

    public function actualiserPlugins()
    {
        include_spip('inc/plugin');
        actualise_plugins_actifs();
    }

    /**
     * Obtenir la liste des plugins actifs
     *
     * @param array $options {
     *     @var bool|null Afficher|Exclure|Uniquement les extensions PHP
     *     @var bool Afficher le faux plugin 'SPIP'
     *     @var bool|null Afficher|Exclure|Uniquement les plugins dist
     * }
     * @return array
     */
    public function getPluginsActifs(array $options = [])
    {
        $options += [
            'procure' => null,
            'php' => null,
            'spip' => false, // only SPIP ?
            'dist' => null,
        ];
        $plugins = unserialize($GLOBALS['meta']['plugin']);
        if ($options['spip']) {
            return ['SPIP' => array_merge(['prefixe' => 'spip'], $plugins['SPIP'])];
        }
        unset($plugins['SPIP']);

        foreach ($plugins as $k => $v) {
            $plugins[$k] = array_merge(['prefixe' => strtolower($k)], $v);
            $is = [
                'php' => ($k === 'PHP' || strpos($k, 'PHP:') === 0),
                'dist' => ($v['dir_type'] === '_DIR_PLUGINS_DIST'),
                'procure' => (strpos($v['dir'], 'procure:') !== false),
            ];
            foreach ($is as $quoi => $test) {
                if ($options[$quoi] !== null && ($options[$quoi] xor $is[$quoi])) {
                    unset($plugins[$k]);
                }
            }
        }

        return $plugins;
    }

    public function getPluginsInactifs()
    {
        include_spip('inc/plugin');
        // chercher dans les plugins dispo
        $get_infos = charger_fonction('get_infos', 'plugins');

        $dirs = ['_DIR_PLUGINS' => _DIR_PLUGINS];
        if (defined('_DIR_PLUGINS_SUPPL') && _DIR_PLUGINS_SUPPL) {
            $dirs['_DIR_PLUGINS_SUPPL'] = _DIR_PLUGINS_SUPPL;
        }

        $list = [];
        foreach ($dirs as $const => $dp) {
            $plugins = liste_plugin_files($dp);
            foreach ($plugins as $dir) {
                $infos = $get_infos($dir, false, $dp);
                $list[] = [
                    'prefixe' => strtolower($infos['prefix']),
                    'etat' => $infos['etat'],
                    'version' => $infos['version'],
                    'dir' => $dir,
                    'dir_type' => $const,
                ];
            }
        }
        return $list;
    }

    public function showPlugins(array $list, $short = false, $raw = false)
    {
        if ($raw) {
            // affichage technique : dans l'ordre d'appel, en liste texte sans mise en forme
            foreach ($list as $infos) {
                $this->showPluginDesc($infos, ['short' => $short, 'raw' => $raw]);
            }
        } else {
            // affichage humain, trie et en tableau
            ksort($list);
            if ($short) {
                $list = array_keys($list);
                $list = array_map('strtolower', $list);
                $this->io->columns($list, 6, true);
            } else {
                $this->io->atable($list);
            }
            $nb = count($list);
            $this->io->care("$nb plugins");
        }
    }

    public function showDiffPlugins(array $listOld, array $listNew, $short = false, $raw = false)
    {
        $removed = array_diff_key($listOld, $listNew);

        foreach ($removed as $infos) {
            $this->showPluginDesc($infos, [
                'diff' => 'removed',
                'short' => $short,
                'raw' => $raw,
            ]);
        }

        foreach ($listNew as $prefix => $infos) {
            if (!array_key_exists($prefix, $listOld)) {
                $this->showPluginDesc($infos, [
                    'diff' => 'added',
                    'short' => $short,
                    'raw' => $raw,
                ]);
            } elseif ($infos !== $listOld[$prefix]) {
                $this->showPluginDesc($listOld[$prefix], [
                    'diff' => 'removed',
                    'short' => $short,
                    'raw' => $raw,
                ]);
                $this->showPluginDesc($infos, [
                    'diff' => 'added',
                    'short' => $short,
                    'raw' => $raw,
                ]);
            }
        }

    }

    public function presenterListe(array $liste)
    {
        if (count($liste) > 4) {
            $this->io->columns($liste, 6, true);
        } else {
            $this->io->listing($liste);
        }
    }

    public function actualiserSVP()
    {
        /* actualiser la liste des paquets locaux */
        if (include_spip('inc/svp_depoter_local')) {
            /* sans forcer tout le recalcul en base, mais en
              récupérant les erreurs XML */
            $err = [];
            svp_actualiser_paquets_locaux(false, $err);
            if ($err) {
                $this->io->care('Erreurs XML présentes :');
                $this->io->care($err);
            }
        }
    }

    protected function configure(): void
    {
        $this->setName('plugins:lister')
            ->setDescription('Liste les plugins du site.')

            ->addOption('dist', null, InputOption::VALUE_NONE, 'Uniquement les plugins dist')
            ->addOption('no-dist', null, InputOption::VALUE_NONE, 'Exclure les plugins dist')

            ->addOption('inactifs', null, InputOption::VALUE_NONE, 'Liste les plugins inactifs.')

            ->addOption('compatibles', null, InputOption::VALUE_NONE, 'Liste les plugins compatibles.')
            ->addOption('incompatibles', null, InputOption::VALUE_NONE, 'Liste les plugins incompatibles.')
            ->addOption(
                'compatibles-avec',
                null,
                InputOption::VALUE_REQUIRED,
                'Liste les plugins compatibles avec une version de SPIP indiquée.',
            )
            ->addOption(
                'incompatibles-avec',
                null,
                InputOption::VALUE_REQUIRED,
                'Liste les plugins incompatibles avec une version de SPIP indiquée.',
            )

            ->addOption('spip', null, InputOption::VALUE_NONE, 'Uniquement SPIP')
            ->addOption('procure', null, InputOption::VALUE_NONE, 'Uniquement les plugins procurés')
            ->addOption('php', null, InputOption::VALUE_NONE, 'Uniquement les extensions PHP présentes')

            ->addOption('short', null, InputOption::VALUE_NONE, 'Affiche simplement le préfixe')
            ->addOption('raw', null, InputOption::VALUE_NONE, 'Affiche texte brut, sans mise en forme')

            ->addOption('export', 'e', InputOption::VALUE_NONE, 'Exporter la liste des plugins actifs dans un fichier')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nom du fichier d’export', 'plugins')

            ->addUsage('--no-dist')
            ->addUsage('--inactifs')
            ->addUsage('--compatibles')
            ->addUsage('--compatibles-avec=4.2.0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $raw = ($input->getOption('raw') ? true : false);

        $this->demarrerSpip();
        if (!$raw) {
            $this->io->title('Liste des plugins');
        }

        $this->actualiserPlugins();

        if ($input->getOption('inactifs')) {
            $this->showInactifs($input, $raw);
        } elseif ($input->getOption('export')) {
            $this->exportActifs($input, $raw);
        } else {
            $this->showActifs($input, $raw);
        }

        return Command::SUCCESS;
    }

    private function buildOptionsCompatibles(InputInterface $input, array $options, array $list): array
    {

        $version_spip = $GLOBALS['spip_version_branche'];
        if ($input->getOption('compatibles')) {
            $options['compatibles'] = $version_spip;
            $list[] = 'Uniquement les plugins compatibles avec le SPIP (' . $options['compatibles'] . ')';
        } elseif ($input->getOption('compatibles-avec')) {
            $options['compatibles'] = $input->getOption('compatibles-avec');
            $list[] = 'Uniquement les plugins compatibles avec SPIP ' . $options['compatibles'];
        }

        if ($input->getOption('incompatibles')) {
            $options['incompatibles'] = $version_spip;
            $list[] = 'Uniquement les plugins incompatibles avec SPIP ' . $options['incompatibles'];
        } elseif ($input->getOption('incompatibles-avec')) {
            $options['incompatibles'] = $input->getOption('incompatibles-avec');
            $list[] = 'Uniquement les plugins incompatibles avec SPIP ' . $options['incompatibles'];
        }

        return [$options, $list];
    }

    private function filterPluginsCompatibles(array $plugins, array $options): array
    {
        if (
            !isset($options['compatibles']) && !isset($options['incompatibles'])
        ) {
            return $plugins;
        }

        include_spip('inc/plugin');
        $plugins = $this->addDataPaquets($plugins);
        if (isset($options['compatibles'])) {
            $spip = $options['compatibles'];
            foreach ($plugins as $key => $desc) {
                if (
                    isset($desc['compatibilite_spip']) && !plugin_version_compatible($desc['compatibilite_spip'], $spip)
                ) {
                    unset($plugins[$key]);
                }
            }
        }

        if (isset($options['incompatibles'])) {
            $spip = $options['incompatibles'];
            foreach ($plugins as $key => $desc) {
                if (
                    isset($desc['compatibilite_spip']) && plugin_version_compatible($desc['compatibilite_spip'], $spip)
                ) {
                    unset($plugins[$key]);
                }
            }
        }

        return $plugins;
    }

    private function addDataPaquets(array $plugins): array
    {
        $get_infos = charger_fonction('get_infos', 'plugins');
        foreach ($plugins as $key => $plugin) {
            $paquet = $get_infos($plugin['dir'], false, constant($plugin['dir_type']));
            if ($paquet) {
                $plugins[$key] = $plugin + [
                    'compatibilite_spip' => $paquet['compatibilite'],
                ];
            }
        }
        return $plugins;
    }

    /**
     * Affiche une courte info de plugin
     *
     * @param array $infos description du plugin
     * @param array $options
     *    - $diff Type de diff (added, removed, changed)
     *    - $short Affichage court ?
     *    - $raw Affichage brut ? (echo…)
     */
    private function showPluginDesc(array $infos, array $options = [])
    {
        $options += [
            'diff' => null,
            'short' => false,
            'row' => false,
        ];
        if ($options['raw']) {
            switch ($options['diff']) {
                case 'added':
                    $before = ' + ';
                    break;
                case 'removed':
                    $before = ' - ';
                    break;
                case 'changed':
                    $before = ' ! ';
                    break;
                default:
                    $before = '   ';
                    break;
            }
            if ($options['short']) {
                echo $before . $infos['prefixe'] . "\n";
            } else {
                echo $before
                  . $infos['prefixe'] . ' '
                  . $infos['version'] . ' '
                  . constant($infos['dir_type']) . $infos['dir']
                  . "\n";
            }
        } else {
            switch ($options['diff']) {
                case 'added':
                    $before = ' <fg=green;options=bold>+</> ';
                    break;
                case 'removed':
                    $before = ' <fg=red;options=bold>-</> ';
                    break;
                case 'changed':
                    $before = ' <fg=yellow;options=bold>!</> ';
                    break;
                default:
                    $before = '   ';
                    break;
            }
            if ($options['short']) {
                $this->io->writeln($before . $infos['prefixe']);
            } else {
                $this->io->writeln(
                    $before
                    . $infos['prefixe'] . ' '
                    . $infos['version'] . ' '
                    . constant($infos['dir_type']) . $infos['dir'],
                );
            }
        }
    }
}
