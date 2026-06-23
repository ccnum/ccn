<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PluginsDesactiver extends PluginsLister
{
    /* Si pas de plugin(s) spécifiés, on demande */
    public function getPrefixesFromQuestion()
    {
        $inactifs = array_column($this->getPluginsActifs(['dist' => false]), 'prefixe');
        $question = new Question("Quel plugin faut-il désactiver ?\n", 'help');
        $question->setAutoCompleterValues($inactifs);
        $reponse = trim($this->io->askQuestion($question));
        if ($reponse === 'help') {
            return false;
        }
        return explode(' ', $reponse);
    }

    public function desactiverPlugins($prefixes)
    {
        if (!count($prefixes)) {
            $this->io->care('Aucun prefixe à désactiver');
            return true;
        }

        $desactiver = [];
        foreach ($this->getPluginsActifs() as $plugin) {
            $prefixe = $plugin['prefixe'];
            if (in_array($prefixe, $prefixes)) {
                $desactiver[] = $plugin['dir'];
                $prefixes = array_diff($prefixes, [$prefixe]);
            }
        }

        if ($desactiver) {
            ecrire_plugin_actifs($desactiver, false, 'enleve');
            $this->actualiserSVP();
        }
    }

    protected function configure(): void
    {
        $this->setName('plugins:desactiver')
            ->setDescription('Désactive un ou plusieurs plugins')
            ->addArgument(
                'prefixes',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Liste des préfixes à désactiver',
            )
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Désactive tous les plugins actifs')
            ->addOption(
                'short',
                null,
                InputOption::VALUE_NONE,
                'Affiche simplement le préfixe sur la liste des plugins actifs',
            )
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Désctiver les plugins sans poser de question')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $this->io->title('Désactiver des plugins');

        $prefixes = $input->getArgument('prefixes');

        if ($input->getOption('all')) {
            $prefixes = array_column(
                $this->getPluginsActifs(['procure' => false, 'php' => false, 'dist' => false]),
                'prefixe',
            );
        }

        if (!$prefixes) {
            $prefixes = $this->getPrefixesFromQuestion();
            if (!$prefixes) {
                $this->getApplication()
                    ->showHelp('plugins:desactiver', $output);
                return Command::FAILURE;
            }
        }
        $prefixes = implode(' ', $prefixes);
        $prefixes = str_replace(',', ' ', $prefixes);
        $prefixes = explode(' ', $prefixes);
        $prefixes = array_filter($prefixes);

        $liste_complete = $prefixes;

        // regardons ce qui est deja actif pour presenter une liste humaine et utile en affichant que ce qui sera active en plus
        $actifs = $this->getPluginsActifs(['procure' => false, 'php' => false]);
        $prefixes_actifs = array_column($actifs, 'prefixe');

        $this->io->care(count($prefixes_actifs) . ' plugins actifs');
        if ($liste_todo = array_intersect($prefixes_actifs, $liste_complete)) {
            if ($deja = array_diff($liste_complete, $prefixes_actifs)) {
                $this->io->text('Ne sont pas actifs :');
                $this->presenterListe($deja);
            }
            $this->io->text('Liste des plugins à desactiver :');
            $this->presenterListe($liste_todo);
        } else {
            $this->io->check('Tous les préfixes demandés sont déjà inactifs');
            $this->presenterListe($liste_complete);
        }

        if (
            $liste_todo
            && !$input->getOption('yes')
            && !$this->io->confirm('Les plugins ci-dessus seront désactivés. Confirmez-vous ?', false)
        ) {
            $this->io->care('Action annulée');
            return Command::FAILURE;
        }

        $this->actualiserPlugins();
        $this->desactiverPlugins($liste_complete);

        $actifs_new = $this->getPluginsActifs(['procure' => false, 'php' => false]);
        if ($actifs !== $actifs_new) {
            $this->io->text('Résultat :');
            $this->showDiffPlugins($actifs, $actifs_new, $input->getOption('short'));
            $this->io->care(count($actifs_new) . ' plugins actifs');
        } else {
            $this->io->care('Aucune modification des plugins actifs');
        }
        $this->showPluginsErrors();
        return Command::SUCCESS;
    }
}
