<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Spip\Cli\Console\Style\SpipCliStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobsLister extends Command
{
    public static function lister_id_jobs(InputInterface $input, $past = false)
    {
        $options = [];

        $ids = [];

        if ($filter = $input->getOption('filter')) {
            $options['filter'] = '/' . preg_quote($filter) . '/ims';
        }

        if ($fonction = $input->getOption('fonction')) {
            $options['fonction'] = $fonction;
        }

        $where = '';
        if ($past || $input->getOption('past')) {
            $where = 'date < ' . sql_quote(date('Y-m-d H:i:s'));
        }

        $res = sql_select('id_job, descriptif, fonction', 'spip_jobs', $where, '', 'date');
        while ($row = sql_fetch($res)) {
            if (!empty($options['filter']) && !preg_match($options['filter'], $row['descriptif']) && !preg_match(
                $options['filter'],
                $row['fonction'],
            )) {
                continue;
            }
            if (!empty($options['fonction']) && stripos($row['fonction'], (string) $options['fonction']) === false) {
                continue;
            }
            $ids[] = $row['id_job'];
        }

        return $ids;
    }

    public static function afficher_jobs(SpipCliStyle $io, $ids)
    {
        include_spip('inc/filtres');
        include_spip('inc/queue');

        if (!count($ids)) {
            $io->care('Aucun job dans la file');
        } else {

            $liste = [];
            $res = sql_select('*', 'spip_jobs', sql_in('id_job', $ids), '', 'date');
            while ($row = sql_fetch($res)) {
                $liste[] = [
                    'id_job' => $row['id_job'],
                    'fonction' => $row['fonction'],
                    'descriptif' => $row['descriptif'],
                    'inclure' => $row['inclure'],
                    'priorite' => $row['priorite'],
                    'date' => date_relative($row['date']),
                    'status' => self::afficher_status($row['status']),
                ];
            }

            $io->atable($liste);
        }
    }

    public static function afficher_status(int $status)
    {
        switch ($status) {
            case _JQ_PENDING:
                return 'En cours';
            case _JQ_SCHEDULED:
                return 'Programmé';
            default:
                return "$status ??";
        }
    }

    protected function configure(): void
    {
        $this->setName('jobs:lister')
            ->setDescription('Liste les taches en attente')
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
        $this->io->title('Liste des tâches');

        $ids = self::lister_id_jobs($input);
        self::afficher_jobs($this->io, $ids);

        return Command::SUCCESS;
    }
}
