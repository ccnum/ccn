<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PhpRun extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('php:run')
            ->setDescription('Lancer un script php dans le contexte de SPIPien')
            ->addOption('include', null, InputOption::VALUE_OPTIONAL, 'nom du script PHP a inclure', null)
            ->addOption(
                'include_spip',
                null,
                InputOption::VALUE_OPTIONAL,
                'nom du script PHP a inclure en utilisant le path SPIP',
                null,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        if ($include = $input->getOption('include')) {
            $currentDir = getcwd();
            if (!file_exists($include)) {
                $this->io->error('Fichier ' . $currentDir . '/' . $include . ' introuvable');
                exit(Command::FAILURE);
            }
        } elseif ($include_spip = $input->getOption('include_spip')) {
            if (substr($include_spip, -4) !== '.php') {
                $include_spip .= '.php';
            }
            if (!$include = find_in_path($include_spip)) {
                $this->io->error('Fichier ' . $include_spip . ' introuvable dans le path SPIP');
                $this->io->care(_chemin());
                exit(Command::FAILURE);
            }
        } else {
            $this->io->error("Indiquez un fichier à inclure via l'option --include ou --include_spip");
            exit(Command::FAILURE);
        }

        // on charge l'environnement fonctions
        include_spip('public/parametrer');

        $this->io->care("Run $include");
        include $include;

        return Command::SUCCESS;
    }
}
