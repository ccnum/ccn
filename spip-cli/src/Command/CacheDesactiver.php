<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheDesactiver extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:desactiver')
            ->setDescription('Désactive le cache de spip pendant 24h.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $purger = charger_fonction('purger', 'action');
        $purger('inhibe_cache');

        $this->io->info('Cache désactivé');
        return Command::SUCCESS;
    }
}
