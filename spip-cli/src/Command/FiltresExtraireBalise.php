<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FiltresExtraireBalise extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('filtres:extraire_balise')
            ->setDescription(
                'Extrait la première balise du type fourni avec l\'option -b. Exemple `spip extraire_balise  -b title` pour extraire le titre de la page',
            )
            ->setAliases(['extraire_balise'])
            ->addOption('balise', 'b', InputOption::VALUE_OPTIONAL, 'Type de balise HTML à extraire', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $type_balise = $input->getOption('balise');
        if ($type_balise) {
            $contenu = stream_get_contents(STDIN);

            include_spip('inc/filtres');
            $balise = extraire_balise($contenu, $type_balise);
            $this->io->writeln($balise);
        } else {
            $this->io->fail("Préciser le type de balise HTML à extraire avec l'option -a.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
