<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobsExecuter extends Command
{
    protected function configure(): void
    {
        $this->setName('jobs:executer')
            ->setDescription('Executer des taches en attente')
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

        define('_JQ_MAX_JOBS_TIME_TO_EXECUTE', 24 * 3600); // maxi 24h
        define('_JQ_MAX_JOBS_EXECUTE', 10000); // maxi 10000 jobs

        $this->demarrerSpip();
        $this->io->title('Executer des tâches');

        if (
            $input->getOption('filter') || $input->getOption('fonction') || $input->getOption('past')
        ) {
            $ids = JobsLister::lister_id_jobs($input);
        } else {
            $ids = JobsLister::lister_id_jobs($input, true);
        }

        if (!count($ids)) {
            $this->io->care('Rien à faire !');
        } else {
            include_spip('inc/queue');
            include_spip('inc/genie');

            $res = sql_select('*', 'spip_jobs', sql_in('id_job', $ids), '', 'date');
            while ($row = sql_fetch($res)) {

                if ($row['status'] == _JQ_SCHEDULED) {
                    $this->io->care('#' . $row['id_job'] . ': ' . $row['fonction'] . ' | ' . $row['descriptif']);
                    queue_schedule([$row['id_job']]);
                    $this->io->check('done');
                } else {
                    $this->io->fail(
                        'EN COURS #' . $row['id_job'] . ': ' . $row['fonction'] . ' | ' . $row['descriptif'],
                    );
                }

            }
        }

        return Command::SUCCESS;
    }
}
