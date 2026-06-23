<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Spip\Cli\Console\Style\SpipCliStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobsSupprimer extends Command
{
    public static function remove_jobs(SpipCliStyle $io, $ids)
    {
        include_spip('inc/filtres');
        include_spip('inc/queue');

        if (!count($ids)) {
            $io->care('Aucun job à supprimer');
        } else {
            JobsLister::afficher_jobs($io, $ids);

            foreach ($ids as $id_job) {
                queue_remove_job($id_job);
            }

            $nb = count($ids);
            $io->care("$nb jobs supprimés");
        }
    }

    protected function configure(): void
    {
        $this->setName('jobs:supprimer')
            ->setDescription('Supprimer des taches en attente')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'filtrer les taches par une regexp sur le descriptif ou sur la fonction',
            )
            ->addOption(
                'fonction',
                null,
                InputOption::VALUE_OPTIONAL,
                'uniquement les taches correspondant à cette fonction',
            )
            ->addOption('past', null, InputOption::VALUE_NONE, 'uniquement les taches dont la date est passée')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->demarrerSpip();
        $this->io->title('Supprimer des tâches');

        $ids = JobsLister::lister_id_jobs($input);
        self::remove_jobs($this->io, $ids);

        return Command::SUCCESS;
    }
}
