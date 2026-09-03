<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigEcrire extends Command
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Ecrire les données dans spip_meta *
     */
    public function ecrireConfig(array $options = [])
    {
        include_spip('inc/config');

        foreach ($options as $intitule => $valeur) {
            $this->io->info("Enregistrement de $intitule => $valeur  dans spip_meta");
            ecrire_config($intitule, $valeur);
        }
    }

    protected function configure(): void
    {
        $this->setName('config:ecrire')
            ->setDescription('Ecrire une option de configuration dans spip_meta')
            ->addArgument(
                'option',
                InputArgument::OPTIONAL,
                'Intitulé et valeur de l\'option, de la forme <comment>intitule:valeur</comment> ou intitulé seul accompagné de --valeur pour fixer la valeur',
            )
            ->addOption('valeur', '', InputOption::VALUE_OPTIONAL, 'Valeur de l\'option')
            ->addOption(
                'plugin',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Préfixe d\'un plugin auquel sera attachée l\'option (ou le groupe d\'option si --json)',
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_OPTIONAL,
                'Passage de plusieurs couples <comment>\'intitule\':\'valeur\'</comment> au format JSon',
            )
            ->addOption(
                'fichier',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Chemin d\'un fichier contenant les couples <comment>\'intitule\':\'valeur\'</comment> (format json idem celui de l\'option --json)',
            )
            ->setHelp('Quelques exemples :

<info>spip config:ecrire creer_preview:oui</info> : écrit la meta qui permet de créer les vignettes de prévisualisation
<info>spip config:ecrire nom_site:\'Le site à Toto\'</info> : écrit la méta qui défini le nom du site

<info>spip config:ecrire -p facteur adresse_envoi_email:toto@mon-spip.tld</info> : écrit la méta qui défini le mail expéditeur du plugin facteur

<info>spip config:ecrire --json \'{"creer_preview":"oui", "taille_preview":"250", "image_process":"gd2"}\'</info> : écrit les métas pour avoir la création des vignettes, de 250px de côté, en utilisant GD2
<info>spip config:ecrire -f tmp/config.json</info> : récupère les métas à écrire (au format json) dans le fichier tmp/config.json

<info>Organisation des données json</info> :
 - soit sous forme de simples couples <comment>\'intitule\':\'valeur\'</comment>
 - soit sous forme de sous-array avec le préfixe d\'un plugin comme clé (option --plugin non prise en compte dans ce cas)
 - exemple avec les 2 formes:
<info>{
	"creer_preview":"oui",
	"taille_preview":"250",
	"image_process":"gd2",
	"facteur" : {
		"adresse_envoi_email":"toto@mon-spip.tld",
		"mailer":"mailjet",
		"mailjet_api_version":3
	}
}</info>

<info>spip config:ecrire -f tmp/config.json --json \'{"articles_redirection":"oui", "articles_ps":"oui", "oembed": {"embed_auto":"oui"}}\' articles_redac:oui</info> : cumul possible des 3 méthodes pour passer des options de configuration à écrire dans spip_meta
	');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();
        $Toptions = [];

        if ($input->getOption('plugin')) {
            $plugin = $input->getOption('plugin');
        } else {
            $plugin = false;
        }

        // configs passées en --fichier
        if ($fichier = $input->getOption('fichier')) {
            if (file_exists($fichier)) {
                $Tfichier = $this->recupConfig($fichier);
            } else {
                $output->writeln("<error>Le fichier $fichier n\'existe pas</error>");
                return Command::FAILURE;
            }

            $Tfichier = $this->normaliseJson($Tfichier, $plugin);
            $Toptions = array_merge($Toptions, $Tfichier);
        }

        // configs passées en --json
        if ($Tjson = $input->getOption('json')) {
            $Tjson = json_decode($Tjson, true);

            $Tjson = $this->normaliseJson($Tjson, $plugin);
            $Toptions = array_merge($Toptions, $Tjson);
        }

        // config passée en argument
        if ($option = $input->getArgument('option')) {
            $prefixe = $plugin ? $plugin . '/' : '';
            $valeur = $input->getOption('valeur');
            if ($valeur === null) {
                $valeur = explode(':', $option, 2);
                $option = array_shift($valeur);
                $valeur = (empty($valeur) ? '' : reset($valeur));
            }
            $Toptions[$prefixe . $option] = $valeur;
        }

        if (!count($Toptions)) {
            $output->writeln('<error>Il faut au moins un argument intitule:valeur à enregistrer comme config</error>');
            return Command::FAILURE;
        }

        $this->ecrireConfig($Toptions);
        return Command::SUCCESS;
    }

    /**
     * récupération d'un json sous forme d'un fichier externe *
     */
    protected function recupConfig($fichier)
    {
        $json = @file_get_contents($fichier);
        return json_decode($json, true);
    }

    /**
     * traitement des array imbriqués avec le préfixe d'un plugin comme clé *
     */
    protected function normaliseJson($Toptions, $plugin = false)
    {
        $Tretour = [];
        $prefixe = $plugin ? $plugin . '/' : '';
        foreach ($Toptions as $intitule => $valeur) {
            if (is_array($valeur)) {
                $Tretour = array_merge($Tretour, $this->normaliseJson($valeur, $prefixe . $intitule));
            } else {
                $Tretour[$prefixe . $intitule] = $valeur;
            }
        }
        return $Tretour;
    }
}
