<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuteursEnvoyerLienOubli extends Command
{
    public function listerAuteurs($email = '', $id = 0, $login = '')
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

        $auteurs = sql_allfetsel(
            ['id_auteur', 'login', 'nom', 'email', 'statut', 'webmestre'],
            'spip_auteurs',
            $criteres,
        );
        return $auteurs;
    }

    public function envoyerMail($auteur)
    {
        include_spip('formulaires/oubli');
        if (function_exists('message_oubli')) {
            # FIXME: message_oubli ne retourne pas d'info de succès ou non, mais juste un message
            return message_oubli($auteur['email'], 'p');
        }
        return false;

    }

    public function forceEnvoiMail()
    {
        include_spip('inc/queue');
        include_spip('inc/genie');
        $criteres = [];
        $criteres[] = 'fonction=' . sql_quote('envoyer_mail');
        // On évite les jobs planifiés dans le futur :
        $criteres[] = 'date<=' . sql_quote(date('Y-m-d H:i:s', time()));
        // NB: ne n'utilise pas le NOw(), au cas où la TZ n'est pas la même (le code de spip utilise time() php)
        // On trie par date, et on ne prend qu'une ligne. On va supposer que le job 'envoyer_mail' le plus récent est le bon :
        $jobs = sql_allfetsel(['id_job'], 'spip_jobs', $criteres, '', 'date DESC', '0,1');
        if ($jobs && count($jobs) > 0) {
            queue_schedule([$jobs[0]['id_job']]);
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('auteurs:envoyer:lien:oubli')
            ->setDescription(
                'Envoyer un mail d\'oubli de mot de passe à l\'auteur, avec un lien pour le réinitialiser.',
            )
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email de l\'auteur à modifier')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Identifiant de l\'auteur à modifier')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login de l\'auteur à modifier')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Envoyer le mail sans poser de question');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();

        if ($input->getOption('email')) {
            $email = $input->getOption('email');
        }
        if ($input->getOption('id')) {
            $id = $input->getOption('id');
        }
        if ($input->getOption('login')) {
            $login = $input->getOption('login');
        }

        if ($id || $login || $email) {
            $auteurs = $this->listerAuteurs($email, $id, $login);
            if ($auteurs) {
                $this->io->text('Auteurs trouvés :');
                $this->io->table(['id_auteur', 'login', 'nom', 'email', 'statut', 'webmestre'], $auteurs);
                if (count($auteurs) === 1) {
                    if (
                        !$input->getOption('yes') && !$this->io->confirm(
                            'Les auteurs listés au-dessus recevront un lien de changement de mot de passe. Confirmez-vous ?',
                            false,
                        )
                    ) {
                        $this->io->care('Action annulée');
                        return Command::SUCCESS;
                    }

                    $r = $this->envoyerMail($auteurs[0]);
                    if ($r === false) {
                        $this->io->error('Echec de l\'envoi de mail.');
                        return Command::FAILURE;
                    }

                    # Le mail est passe par un job. Il ne sera donc pas envoyé tant que personne ne visite le site.
                    # On va forcer l'envoi.
                    # NB: en cas d'échec, on va ignorer l'erreur, ça n'est pas bloquant.
                    $this->forceEnvoiMail();

                    $this->io->success($r);
                    return Command::SUCCESS;
                }
                $this->io->error(
                    'Il y a ' . count(
                        $auteurs,
                    ) . ' auteurs qui correspondent, on ne peut utiliser cette action que sur un seul à la fois.',
                );
                return Command::INVALID;

            }
            $this->io->error('Il n\'y a pas d\'auteur correspondant aux critères.');
            return Command::FAILURE;

        }
        $this->io->error("Il faut un id ou un login ou un email pour identifier l'auteur !");
        return Command::INVALID;

    }
}
