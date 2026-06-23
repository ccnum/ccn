<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlOptimize extends Command
{
    protected function configure(): void
    {
        $this->setName('sql:optimize')
            ->setDescription('Optimize les tables SQL')
            ->addOption(
                'reorder',
                null,
                InputOption::VALUE_NONE,
                'Reordonner les tables selons leur clé primaire id_xx',
            )
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Nettoyer les objets a la poubelles et les liens morts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $this->io->title('Optimiser la base de données');

        $clean = $input->getOption('clean');
        $reorder = $input->getOption('reorder');

        $tables = sql_alltable();
        asort($tables);
        foreach ($tables as $table) {
            $this->optimizeTable($table, $reorder);
        }

        if ($clean) {
            include_spip('genie/optimiser');
            optimiser_base_disparus();
            $this->io->check('Nettoyer les tables des objets et liens disparus');
        }
        return Command::SUCCESS;
    }

    protected function optimizeTable($table, $reorder = false)
    {

        if (sql_optimize($table)) {
            $this->io->check("$table : Optimize");
        } else {
            $this->io->care("$table : Rien à Optimize");
        }

        if ($reorder) {
            $trouver_table = charger_fonction('trouver_table', 'base');
            $desc = $trouver_table($table);
            if (
                isset($desc['key']['PRIMARY KEY'])
                && ($primary = $desc['key']['PRIMARY KEY'])
                && strpos($primary, ',') === false
                && strpos($primary, 'id_') === 0
            ) {
                sql_alter("$table ORDER BY $primary");
                $this->io->check("$table : ORDER BY $primary");
            }
        }

    }
}
