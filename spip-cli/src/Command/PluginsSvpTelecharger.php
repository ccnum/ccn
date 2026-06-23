<?php

namespace Spip\Cli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PluginsSvpTelecharger extends PluginsActiver
{
    protected $title = 'Télécharger des plugins';

    public function activePlugins($prefixes)
    {
        include_spip('inc/autoriser');
        include_spip('inc/svp_decider');
        include_spip('inc/svp_actionner');

        $decideur = new \Decideur();
        $actionneur = new \Actionneur();

        foreach ($prefixes as $prefix) {
            $this->io->comment("Plugin en cours d'installation : " . $prefix);
            $infos = $decideur->infos_courtes('UPPER(pl.prefixe) = LOWER("' . strtoupper($prefix) . '")');
            if (empty($infos['i'])) {
                $this->io->error('Le plugin ' . $prefix . " n'est pas référencé");
                continue;
            }
            $a_installer[key($infos['i'])] = 'geton';
            $decideur->erreur_sur_maj_introuvable = false;
            $res = $decideur->verifier_dependances($a_installer);

            if (!$decideur->ok) {
                $this->io->error('>Le plugin ' . $prefix . ' ne peut être installé :');
                foreach ($decideur->err as $id => $errs) {
                    foreach ($errs as $err) {
                        $this->io->error('    ' . $err);
                    }
                }
                continue;
            }

            $actions = $decideur->presenter_actions('todo');
            $this->io->comment("Pour l'installation du plugin " . $prefix . ' les actions suivantes sont prévues : ');
            foreach ($actions as $action) {
                $this->io->comment("\t" . $action);
            }
            $todo = [];
            foreach ($decideur->todo as $_todo) {
                $todo[$_todo['i']] = $_todo['todo'];
            }
            $actionneur->ajouter_actions($todo);
            $actionneur->verrouiller();
            $actionneur->sauver_actions();

            autoriser_exception('ajouter', '_plugins', '*');
            while ($res = $actionneur->one_action()) {
                $this->io->comment($res['n'] . ' action réalisée : ' . $res['todo']);
            }

            $actionneur->deverrouiller();
            $actionneur->sauver_actions();

            include_spip('inc/svp_depoter_local');
            svp_actualiser_paquets_locaux();
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('plugins:svp:telecharger')
            ->setDescription('Télécharger des plugins depuis les dépôts.')
            ->addArgument(
                'from',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Active les plugins listés. Détermine automatiquement l’option from-xxx.',
            )
            ->addOption('from-file', null, InputOption::VALUE_OPTIONAL, 'Chemin d’un fichier d’export')
            ->addOption('from-url', null, InputOption::VALUE_OPTIONAL, 'Url d’un site SPIP')
            ->addOption(
                'from-list',
                null,
                InputOption::VALUE_OPTIONAL,
                'Liste de préfixes à activer, séparés par virgule',
            )
            ->addOption(
                'import',
                'e',
                InputOption::VALUE_NONE,
                'Importer la liste des plugins actifs depuis un fichier',
            )
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nom du fichier d’import', 'plugins')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Activer tous les plugins disponibles.')
            ->addOption(
                'short',
                null,
                InputOption::VALUE_NONE,
                'Affiche simplement le préfixe sur la liste des plugins actifs',
            )
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Activer les plugins sans poser de question');
    }
}
