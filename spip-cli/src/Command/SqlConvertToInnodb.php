<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SqlConvertToInnodb extends Command
{
    protected function configure(): void
    {
        $this->setName('sql:convert:toinnodb')
            ->setDescription('Change le Engine des tables de la base pour INNODB si besoin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $this->io->title('Convertir en InnoDB');

        $tables = sql_alltable('%'); // charger la connection mysql
        if ($GLOBALS['connexions'][0]['type'] !== 'mysql') {
            $this->io->error('Ce script est réservé aux installations utilisant mySQL');
            return Command::FAILURE;
        }

        // convertir l'engine en InnoDB
        $this->sqlConvertEngine();

        $this->io->success('Fini');
        return Command::SUCCESS;
    }

    protected function sqlConvertEngine()
    {

        $this->io->section('Vérification du Engine MySQL');

        $trouver_table = charger_fonction('trouver_table', 'base');
        $trouver_table('');

        $tables = sql_alltable('%');
        $this->io->text(count($tables) . ' tables');

        foreach ($tables as $table) {
            $ligne = "$table";
            $s = spip_mysql_query("SHOW CREATE TABLE `$table`");
            [, $a] = mysqli_fetch_array($s, MYSQLI_NUM);

            if (strpos($a, 'ENGINE=MyISAM') !== false) {
                sql_alter($q = "TABLE $table ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
                spip_log("ALTER $q", 'maj_innodb');
                $ligne .= " : ALTER $table ENGINE = InnoDB ";
                $this->io->text("$ligne");
            } else {
                $this->io->text("$ligne OK");
            }
        }

        $this->io->success('Engine InnoDB');
    }
}
