<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltresFiltrerEntites extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('filtres:filtrer_entites')
            ->setDescription('Transforme tous les caractères spéciaux HTML dans le charset du site.')
            ->setAliases(['filtrer_entites'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $contenu = stream_get_contents(STDIN);

        include_spip('inc/filtres');
        $texte = filtrer_entites($contenu);
        $this->io->writeln($texte);

        return Command::SUCCESS;
    }
}
