<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoreMettreajour extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('core:mettreajour')
            ->setDescription('Mettre à jour la branche de SPIP qui est installée.')
            ->addOption(
                'branche',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Donner explicitement la branche à utiliser (par exemple "3.3")',
            )
            ->addOption(
                'dev',
                'D',
                InputOption::VALUE_OPTIONAL,
                'SPIP 5 : Composer : installation en mode dev (inclus les dépendance de require-dev)',
            )
            ->setAliases([
                'update',
                'up', // abbréviation commune pour "update"
            ])
            ->setHelp(
                'Quelques exemples :

<info>spip up</info> : met à jour les fichiers de SPIP (en restant sur la même version) puis effectue la mise à jour de la BDD de SPIP et des plugins.
<info>spip up -b 3.3</info> : passe les fichiers de SPIP sur la branche 3.3 puis maj la BDD du core et des plugins',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $spip_racine;
        global $spip_loaded;

        // On ne met à jour que si SPIP est bien présent
        if (!$spip_loaded) {
            $output->writeln('<error>Vous devez installer SPIP avant de pouvoir mettre à jour.</error>');
        }

        // On teste si on peut utiliser "passthru"
        if (!function_exists('passthru')) {
            $output->writeln(
                '<error>Votre installation de PHP doit pouvoir exécuter des commandes externes avec la fonction passthru().</error>',
            );
            exit(Command::FAILURE);
        }

        // Branche sélectionnée
        $branche = '';
        $branche = $input->getOption('branche');

        /*
         * lancer spip dl qui fera le job de maj / changement de version
         */
        $command = $this->getApplication()
            ->find('core:telecharger');
        $args = [];
        if ($branche !== '') {
            $args['--branche'] = $branche;
        }
        if ($input->hasParameterOption(['--dev', '-D'])) {
            $args['--dev'] = 1;
        }
        $argsInput = new ArrayInput($args);
        $command->run($argsInput, $output);

        /**
         * lancer spip core:maj:bdd pour maj de la BDD
         * (en passthru pour recharger la version SPIP à jour)
         * puis maj des plugins histoire d'être cohérent core/plugins-dist
         **/
        $cmd = 'spip core:maj:bdd';
        passthru($cmd);

        $cmd = 'spip plugins:maj:bdd';
        passthru($cmd);
        return Command::SUCCESS;
    }
}
