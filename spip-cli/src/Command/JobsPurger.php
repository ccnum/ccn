<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobsPurger extends Command
{
    protected function configure(): void
    {
        $this->setName('jobs:purger')
            ->setDescription('Purger la liste les taches et reinitialiser les crons')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->demarrerSpip();
        include_spip('inc/queue');
        queue_purger();

        $this->io->check('Liste des tâches purgée');

        return Command::SUCCESS;
    }
}
