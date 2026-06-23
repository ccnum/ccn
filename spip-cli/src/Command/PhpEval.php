<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PhpEval extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('php:eval')
            ->setDescription('Évaluer du code PHP dans un contexte SPIPien.')
            ->addArgument('code', InputArgument::REQUIRED, 'Le code PHP à évaluer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        // on charge l'environnement fonctions
        include_spip('public/parametrer');

        eval($input->getArgument('code'));
        return Command::SUCCESS;
    }
}
