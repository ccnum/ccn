<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SqlRepair extends Command
{
    protected function configure(): void
    {
        $this->setName('sql:repair')
            ->setDescription(
                'Crée les tables et champs manquants et tente de réparer chaque table de la base de données.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $this->io->title('Réparer la base de données');
        include_spip('base/repair');
        $html = admin_repair_tables();
        $this->presenterHTML($html);
        return Command::SUCCESS;
    }

    protected function presenterHTML($html)
    {
        include_spip('inc/filtres');
        $html = explode('</div><div', $html);
        foreach ($html as $ligne) {
            $details = explode("\n", $ligne, 2);
            $table = array_shift($details);
            $table = textebrut('<div' . $table);
            $table = str_replace(['(', ')'], ['<comment>(', ')</comment>'], $table);
            if (stripos($ligne, "'notice'") === false || stripos($ligne, 'OK')) {
                $this->io->check($table);
            } else {
                $this->io->fail($table);
            }
            if (stripos($ligne, "'notice'")) {
                $aff = textebrut(implode(' ', $details));
                $aff = preg_replace(",\s+,", ' ', $aff);
                $this->io->care($aff);
            }
        }
        $this->io->text('');
    }
}
