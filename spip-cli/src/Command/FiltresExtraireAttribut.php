<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FiltresExtraireAttribut extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('filtres:extraire_attribut')
            ->setDescription(
                'Permet de récupérer un attribut d’une balise HTML avec l’option -a. Exemple `spip extraire_attribut  -a title` pour extraire l\'attribut title',
            )
            ->setAliases(['extraire_attribut'])
            ->addOption('attribut', 'a', InputOption::VALUE_OPTIONAL, 'Type d\'attribut HTML à extraire', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $type_attribut = $input->getOption('attribut');
        if ($type_attribut) {
            $contenu = stream_get_contents(STDIN);

            include_spip('inc/filtres');
            $attribut = extraire_attribut($contenu, $type_attribut);
            $this->io->writeln($attribut);
        } else {
            $this->io->fail("Préciser le type d’attribut HTML à extraire avec l'option -a.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
