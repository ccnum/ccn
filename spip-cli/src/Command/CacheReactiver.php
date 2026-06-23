<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheReactiver extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:reactiver')
            ->setDescription('Réactive le cache de spip.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $purger = charger_fonction('purger', 'action');
        $purger('reactive_cache');

        $this->io->info('Cache réactivé');
        return Command::SUCCESS;
    }
}
