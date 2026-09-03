<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheVider extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:vider')
            ->setDescription('Vider le cache.')
            ->addOption(
                'squelettes',
                null,
                InputOption::VALUE_NONE,
                'Si défini, on ne vide que le cache des squelettes',
            )
            ->addOption('images', null, InputOption::VALUE_NONE, 'Si défini, on ne vide que le cache des images')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $purger = charger_fonction('purger', 'action');

        $squelettes = $input->getOption('squelettes');
        $images = $input->getOption('images');

        if (!($squelettes || $images)) {
            $purger('cache');
            $this->io->info('Cache entièrement vidé (sauf vignettes)');
        } else {
            if ($squelettes) {
                $purger('squelettes');
                $this->io->info('Cache de compilation des squelettes vidé');
            }
            if ($images) {
                $purger('vignettes');
                $this->io->info('Cache des vignettes vidé');
            }
        }

        return Command::SUCCESS;
    }
}
