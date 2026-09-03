<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SynchroInit extends Command
{
    /**
     * @var Application
     */
    protected $app;

    protected function configure(): void
    {
        $this->setName('synchro:init')
            ->setDescription('Initialiser le json de configuration pour la synchro')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

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

        if ($GLOBALS['spip_loaded']) {
            chdir($GLOBALS['spip_racine']);
            $this->initialiser_json($connect);
        }
        return Command::SUCCESS;
    }

    protected function initialiser_json($connect)
    {
        if (!file_exists('config/synchroSPIP.json')) {
            $json = [
                'config_ssh' => [
                    'host' => '',
                    'user' => '',
                    'port' => '',
                    'chemin_cle' => '',
                    'hostName' => '',
                    'cles' => '',
                ],
                'config_mysql_local' => [
                    'host' => '',
                    'port' => '',
                    'user' => $connect['1'],
                    'pwd' => $connect['2'],
                    'bdd' => $connect['3'],
                    'backup' => false,
                ],
                'config_mysql_serveur' => [
                    'host' => '',
                    'port' => '',
                    'user' => '',
                    'pwd' => '',
                    'bdd' => '',
                ],
                'rsync' => [
                    'IMG' => '',
                ],
                'metas' => [
                    'adresse_site' => $GLOBALS['meta']['adresse_site'],
                    'auto_compress_js' => 'non',
                    'auto_compress_css' => 'non',
                    'image_process' => 'gd2',
                    'facteur/mailer' => 'mail',
                    'activer_statistiques' => 'non',
                    'activer_referers' => 'non',
                ],
            ];

            $dest = 'config/synchroSPIP.json';
            $fichier = json_encode($json, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
            if (ecrire_fichier($dest, $fichier)) {
                $this->io->success('Création du fichier config/synchroSPIP.json');
            } else {
                $this->io->error('Erreur de création du fichier config/synchroSPIP.json');
            }
        } else {
            $this->io->text('le fichier config/synchroSPIP.json existe déjà !');
        }

    }
}
