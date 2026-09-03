<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SynchroCles extends Command
{
    /**
     * @var Application
     */
    protected $app;

    public function lancerRecupCles($config, $verbeux, $io)
    {
        $distant = $config->cles;
        $local = './config/';
        $io->text("copie : $distant -> $local");
        $io->text('');
        $port = $config->port ?: 22;
        if ($config->host) {
            $SSH = $config->host;
        } else {
            $SSH = "$config->user@$config->hostName";
        }
        $cle_ssh = '';
        $port = '';
        if ($config->chemin_cle) {
            if ($config->port) {
                $port = "-p $config->port";
            }
            $cle_ssh = "-i $config->chemin_cle $port";
        }
        $args = $verbeux ? '-azv' : '-az';
        $commande_rsync = "rsync -e 'ssh $cle_ssh' $args --delete-after $SSH:$distant $local";
        if ($verbeux) {
            $io->text('commande rsync :');
            $io->text($commande_rsync);
            $io->text('');
        }
        passthru($commande_rsync, $retour);
        if ($retour != '0') {
            $io->error('Erreur Rsync');
        } else {
            $io->success('rsync');
        }
    }

    protected function configure(): void
    {
        $this->setName('synchro:cles')
            ->setDescription('Synchroniser le fichier config/cles.php du SPIP')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        //$this->demarrerSpip();

        /*
         * SPIP est t'il installe
         */
        include_spip('inc/install');
        if (!_FILE_CONNECT) {
            $this->io->error('Il faut que le SPIP soit installé');
            return Command::FAILURE;
        }

        /*
         * Affichage verbeux avec option -v
         */
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $verbeux = true;
        } else {
            $verbeux = false;
        }

        $config = $this->recupConfig();
        if (empty($config)) {
            $this->io->error('le fichier de configuration synchroSPIP.json est vide ou inexistant');
            return Command::FAILURE;
        }

        /*
         * Debut du script
         */
        $this->io->title('Début du script');

        if ($verbeux) {
            $this->io->section('Debut rsync');
        }
        $this->lancerRecupCles($config->config_ssh, $verbeux, $this->io);

        return Command::SUCCESS;
    }

    protected function recupConfig()
    {
        $config = @file_get_contents('config/synchroSPIP.json');
        $config = json_decode($config);
        return $config;
    }
}
