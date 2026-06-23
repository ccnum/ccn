<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltresTexteBrut extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('filtres:textebrut')
            ->setDescription('Convertit un texte HTML en texte brut.')
            ->setAliases(['textebrut'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $contenu = stream_get_contents(STDIN);

        include_spip('inc/filtres');
        $texte = textebrut($contenu);
        $this->io->writeln($texte);

        return Command::SUCCESS;
    }
}
