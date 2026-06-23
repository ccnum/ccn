<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
// use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuteursMdp extends Command
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Change le mdp d'un auteur
     */
    public function mdpAuteur($mdp, $email = '', $id = 0, $login = '')
    {
        $criteres = [];
        if ($email != '') {
            $criteres[] = 'email=' . sql_quote("$email");
        }
        if ($id > 0) {
            $criteres[] = 'id_auteur=' . sql_quote($id, '', 'INT');
        }
        if ($login != '') {
            $criteres[] = 'login=' . sql_quote($login);
        }

        $auteurs = sql_allfetsel('id_auteur, login', 'spip_auteurs', $criteres);
        if (!$auteurs) {
            return false;
        }

        $ids = [];
        include_spip('auth/spip');
        foreach ($auteurs as $auteur) {
            if (auth_spip_modifier_pass($auteur['login'], $mdp, $auteur['id_auteur'])) {
                $ids[] = $auteur['id_auteur'];
            }
        }

        return $ids;
    }

    protected function configure(): void
    {
        $this->setName('auteurs:changer:mdp')
            ->setDescription("Changer le mot de passe d'un auteur")
            ->addOption('mdp', null, InputOption::VALUE_REQUIRED, 'Statut à positionner')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email de l\'auteur à modifier')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Identifiant de l\'auteur à modifier')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login de l\'auteur à modifier')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        $mdp = null;
        if ($input->getOption('mdp')) {
            $mdp = $input->getOption('mdp');
        }

        if ($mdp) {
            if ($input->getOption('email')) {
                $email = $input->getOption('email');
            } else {
                $email = false;
            }
            if ($input->getOption('id')) {
                $id = $input->getOption('id');
            } else {
                $id = false;
            }
            if ($input->getOption('login')) {
                $login = $input->getOption('login');
            } else {
                $login = false;
            }

            if ($id || $login || $email) {
                $r = $this->mdpAuteur($mdp, $email, $id, $login);
                if ($r) {
                    $this->io->check('nouveau mdp : ' . $mdp);
                    $this->io->success('maj du mdp pour : #' . implode(', #', $r) . ' réussie');
                } else {
                    $this->io->fail('maj du mdp impossible');

                }
                // $this->io->table(['id_auteur', 'login', 'nom', 'email', 'statut', 'webmestre'], $resultat);
            } else {
                $this->io->error("Il faut un id ou un login ou un email pour identifier l'auteur !");
                return Command::FAILURE;
            }
        } else {
            $this->io->error('Il faut un mot de passe !');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
