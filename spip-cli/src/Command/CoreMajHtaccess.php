<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoreMajHtaccess extends Command
{
    protected $title = 'Mise à jour du .htaccess de SPIP';

    protected function configure(): void
    {
        $this
            ->setName('core:maj:htaccess')
            ->setDescription('Mettre à jour le .htaccess de SPIP.')
            ->addOption(
                'rewrite-base',
                null,
                InputOption::VALUE_OPTIONAL,
                'Configuration du RewriteBase pour la réécriture des URLs',
                '',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $spip_racine;
        global $spip_loaded;

        $rewrite_base = $input->getOption('rewrite-base');

        // On ne prépare les fichiers que si SPIP est bien présent
        if ($spip_loaded) {
            // On revient à la racine
            chdir($spip_racine);

            // .htaccess
            if (is_file('.htaccess') && is_file('htaccess.txt')) {
                copy('htaccess.txt', '.htaccess');
                $this->io->success("Fichier .htaccess\nActivation du fichier depuis le modèle de SPIP : OK");
                // Si un RewriteBase est défini, on essaye de le changer
                if ($rewrite_base && include_spip('inc/flock') && lire_fichier('.htaccess', $htaccess)) {
                    $htaccess = str_replace('RewriteBase /', 'RewriteBase ' . $rewrite_base, $htaccess);
                    supprimer_fichier('.htaccess');
                    if (ecrire_fichier('.htaccess', $htaccess)) {
                        $this->io->success("\tRewriteBase définie à $rewrite_base");
                    }
                }
            }
        } else {
            $this->io->error('Vous devez télécharger SPIP avant de pouvoir préparer l’installation.');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
