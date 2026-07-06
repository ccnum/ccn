<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TexteTypo extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('texte:typo')
            ->setDescription('Convertit une phrase vers du HTML via la fonction "typo"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $contenu = stream_get_contents(STDIN);

        include_spip('inc/texte');
        $texte = trim(typo($contenu));
        $this->io->writeln($texte);

        return Command::SUCCESS;
    }
}
