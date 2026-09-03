<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuteursLister extends Command
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Cherche l’auteur SPIP -1
     */
    public function listeAuteurs($statut = '', $email = '', $webmestres = false)
    {
        $criteres = [];
        if ($statut != '') {
            $criteres[] = 'statut = ' . sql_quote($statut);
        }
        if ($email != '') {
            $criteres[] = 'email LIKE ' . sql_quote("%$email%");
        }
        if ($webmestres) {
            $criteres[] = "webmestre = 'oui'";
        }

        $auteurs = sql_allfetsel(
            ['id_auteur', 'login', 'nom', 'email', 'statut', 'webmestre'],
            'spip_auteurs',
            $criteres,
        );

        return $auteurs;
    }

    protected function configure(): void
    {
        $this->setName('auteurs:lister')
            ->setDescription("Liste les auteurs d'un site")
            ->addOption('statut', null, InputOption::VALUE_REQUIRED, 'Statut spécifique')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'email (fait un LIKE %email%')
            ->addOption('webmestres', null, InputOption::VALUE_NONE, 'Ne chercher que les webmestres')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $statut = '';
        if ($input->getOption('statut')) {
            $statut = $input->getOption('statut');
            if (!in_array($statut, ['0minirezo', '1comite', '5poubelle', '6forum'])) {
                $statut = '';
            }
        }

        $email = '';
        if ($input->getOption('email')) {
            $email = $input->getOption('email');
        }

        $webmestres = false;
        if ($input->getOption('webmestres')) {
            $statut = '0minirezo';
            $webmestres = true;
        }

        $auteurs = $this->listeAuteurs($statut, $email, $webmestres);

        $this->io->table(['id_auteur', 'login', 'nom', 'email', 'statut', 'webmestre'], $auteurs);
        return Command::SUCCESS;
    }
}
