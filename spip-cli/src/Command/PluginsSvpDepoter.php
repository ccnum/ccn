<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsSvpDepoter extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('plugins:svp:depoter')
            ->setDescription('Ajouter un depot')
            ->addArgument('url', InputArgument::REQUIRED, 'URL du dépot')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        include_spip('inc/filtres');
        include_spip('inc/svp_depoter_distant');
        $url = $input->getArgument('url');

        $ajouter = svp_ajouter_depot($url);

        if (!$ajouter) {
            $this->io->error("Impossible d'ajouter le dépot $url");
            return Command::FAILURE;
        }

        $this->io->info("Le dépot $url a été ajouté");
        $this->getApplication()
            ->restoreCwd();

        return Command::SUCCESS;
    }
}
