<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchroBdd extends Command
{
    /**
     * @var Application
     */
    protected $app;

    protected function configure(): void
    {
        $this->setName('synchro:bdd')
            ->setDescription(
                "Synchroniser la BDD du SPIP depuis celle d'un autre site (avec maj des metas pour conserver les paramètres spécifiques du SPIP, cf config générée via synchro:init)",
            )
            ->addOption('rsync', 'r', InputOption::VALUE_NONE, 'Jouer rsync')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'backup bdd')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'force la restauration')
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
         * Pour l'instant, cela ne fonctionne que pour une bdd en mysql
         */
        $connect = analyse_fichier_connection(_FILE_CONNECT);
        $type_bdd = $connect['4'];
        if ($type_bdd !== 'mysql') {
            $this->io->error('La synchro ne fonctionne qu\'avec une bdd en mysql');
            return Command::FAILURE;
        }

        if (!@file_exists('config/synchroSPIP.json')) {
            $this->io->error(
                'Le fichier de configuration synchroSPIP.json est inexistant. Créer ce fichier avec synchro:init',
            );
            return Command::FAILURE;
        }

        $config = $this->recupConfig();
        if (empty($config)) {
            $this->io->error('Le fichier de configuration synchroSPIP.json est vide ou mal formé');
            return Command::FAILURE;
        }

        /*
         * Debut du script
         */
        $this->io->title('Début du script');

        /*
         */
        if ($config->config_ssh && $config->config_mysql_local && $config->config_mysql_serveur) {
            if ($output->isVerbose()) {
                $this->io->section('Debut synchro bdd');
            }
            $this->synchroBdd(
                $config->config_ssh,
                $config->config_mysql_local,
                $config->config_mysql_serveur,
                $input->getOption('backup'),
                $input->getOption('force'),
                $output->isVerbose(),
            );
        }

        /*
         * maj de spip_meta
         */
        if ($config->metas) {
            if ($output->isVerbose()) {
                $this->io->section('Maj des metas');
            }
            $this->majMeta($config->metas, $output->isVerbose());
            $this->io->success('maj des metas');
        }

        /*
         * Rsync
         */
        if ($input->getOption('rsync') && $config->rsync && $config->config_ssh) {
            if ($output->isVerbose()) {
                $this->io->section('Debut rsync');
            }
            SynchroFichiers::lancerRsync($config->rsync, $config->config_ssh, $output->isVerbose(), $this->io);
        }

        return Command::SUCCESS;
    }

    protected function synchroBdd($ssh, $local, $serveur, $forcer_backup, $force_restauration, $verbeux)
    {
        $passServeur = '-p';
        if ($serveur->pwd) {
            $passServeur = "--password='\"'\"'$serveur->pwd'\"'\"'";
        }
        $passLocal = '';
        if ($local->pwd) {
            $passLocal = "--password='$local->pwd'";
        }

        $serveurParam = ($serveur->host ?? null) ? " -h $serveur->host " : '';
        $serveurParam .= ($serveur->port ?? null) ? " -P $serveur->port " : '';
        $localParam = ($local->host ?? null) ? " -h $local->host " : '';
        $localParam .= ($local->port ?? null) ? " -P $local->port " : '';

        $dumpParameters = ' --single-transaction --quick ';

        /*
         * Doit on faire un backup de la bdd
         */
        if ($forcer_backup || $local->backup) {
            $chemin = sous_repertoire(_DIR_TMP . 'dump');
            $name_backup = $chemin . $local->bdd . '_' . date('Y-m-d_H-i-s') . '.sql';
            $commande_backup = "mysqldump -u $local->user $passLocal $dumpParameters --opt $local->bdd > $name_backup";
            if ($verbeux) {
                $this->io->text('commande backup :');
                $this->io->text($commande_backup);
            }
            passthru($commande_backup, $retour);
            if ($retour != '0') {
                $this->io->error('Erreur backup bdd');
                return Command::FAILURE;
            }
            $this->io->success('backup bdd : ' . $name_backup);

        }

        if ($ssh->host) {
            $SSH = $ssh->host;
        } else {
            $SSH = "$ssh->user@$ssh->hostName";
            if ($ssh->port) {
                $SSH .= " -i $ssh->chemin_cle -p $ssh->port";
            }
        }
        $commande_ssh = "ssh $SSH 'mysqldump $serveurParam -u $serveur->user $passServeur $dumpParameters --opt $serveur->bdd' "
            . "|mysql $localParam -u $local->user $passLocal $local->bdd";
        if ($force_restauration) {
            $commande_ssh .= ' --force';
        }
        if ($verbeux) {
            $this->io->text('commande ssh :');
            $this->io->text($commande_ssh);
        }
        passthru($commande_ssh, $retour);
        if ($retour != '0') {
            $this->io->error('Erreur de Synchro de bdd');
            return Command::FAILURE;
        }
        $this->io->success('synchro bdd');

    }

    protected function majMeta($metas, $verbeux)
    {
        include_spip('inc/config');
        foreach ($metas as $meta => $valeur) {
            $valeur = json_decode(json_encode($valeur), true);
            if ($verbeux) {
                if (is_array($valeur)) {
                    $this->io->text("meta $meta ==> " . $this->implode_recursive($valeur));
                } else {
                    $this->io->text("meta $meta ==> $valeur");
                }
            }
            if (!ecrire_config($meta, $valeur)) {
                $this->io->error('Erreur de mise à jour des metas');
                return Command::FAILURE;
            }
        }
    }

    protected function recupConfig()
    {
        $config = @file_get_contents('config/synchroSPIP.json');
        $config = json_decode($config);
        return $config;
    }

    protected function implode_recursive(array $array, string $sep = "\n\t"): string
    {
        $string = '';
        $j = 0;
        foreach ($array as $i => $a) {
            if (is_array($a)) {
                $string .= "[$j] => \n\t" . $this->implode_recursive($a, $sep);
            } else {
                $string .= "[$i] => " . $a . $sep;
            }
            $j++;
        }
        return $string;
    }
}
