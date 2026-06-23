<?php

namespace Spip\Cli\Command;

use Spip\Cli\Application;
use Spip\Cli\Console\Command;
use Spip\Cli\Loader\Spip;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigLire extends Command
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Lire les données depuis spip_meta *
     */
    public function lireConfig(array $options = [], bool $format_json = false, ?string $fichier = null)
    {
        include_spip('inc/config');

        $Tsortie = [];
        foreach ($options as $intitule) {
            $Tsortie[$intitule] = lire_config($intitule);
        }

        if ($format_json) {
            $sortie = json_encode($Tsortie);
        } else {
            $sortie = self::readable_print($Tsortie);
        }

        if ($fichier) {
            ecrire_fichier($fichier, $sortie);
        } else {
            $this->io->writeln($sortie);
        }
    }

    public static function readable_print($u, $join = "\n", $indent = 0)
    {
        if (is_string($u)) {
            if (!$a = @unserialize($u)) {
                return $u;
            }
            $u = $a;
        }

        // caster $u en array si besoin
        if (is_object($u)) {
            $u = (array) $u;
        }

        if (is_array($u)) {
            $out = '';
            // toutes les cles sont numeriques ?
            // et aucun enfant n'est un tableau
            // liste simple separee par des virgules
            $numeric_keys = array_map('is_numeric', array_keys($u));
            $array_values = array_map('is_array', $u);
            $object_values = array_map('is_object', $u);
            if (
                array_sum($numeric_keys) == count($numeric_keys)
                && !array_sum($array_values)
                && !array_sum($object_values)
            ) {
                return '[' . implode(', ', array_map('Spip\Cli\Command\ConfigLire::readable_print', $u)) . ']';
            }

            // sinon on passe a la ligne et on indente
            $i_str = str_pad('', $indent, ' ');
            foreach ($u as $k => $v) {
                $out .= $join . $i_str . "$k: " . self::readable_print($v, $join, $indent + 2);
            }

            return $out;
        }

        // on ne sait pas quoi faire...
        return $u;
    }

    protected function configure(): void
    {
        $this->setName('config:lire')
            ->setDescription('Lire une option de configuration depuis spip_meta')
            ->addArgument(
                'option',
                InputArgument::OPTIONAL,
                'Intitulé de l\'option ou d\'une liste d\'options et de préfixes de plugins séparés par une virgule',
            )
            ->addOption('json', '', InputOption::VALUE_NONE, 'Sortie au format json')
            ->addOption(
                'fichier',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Chemin du fichier dans lequel écrire le retour de la commande',
            )
            ->setHelp(
                'Lit la ou les options passées en argument et affiche leur valeur sous forme lisible, y compris si ce sont des tableaux imbriqués.
Avec l\'option `--json` le retour est toujours au format json

<info>spip config:lire taille_preview</info> : lit la méta qui défini la taille des vignette. Retour : <comment>taille_preview: 150</comment>
<info>spip config:lire facteur/adresse_envoi_email</info> : lit la méta qui défini le mail expéditeur du plugin facteur.
	Retour : <comment>facteur/adresse_envoi_email: toto@mon-spip.tld</comment>

<info>spip config:lire creer_preview,taille_preview,image_process</info> : lit 3 les métas demandées et retourne un affichage lisible.
	Retour : <comment>creer_preview: oui
	taille_preview: 250
	image_process: imagick</comment>

<info>spip config:lire creer_preview,taille_preview,image_process --json</info> : lit 3 les métas demandées et retourne un affichage au format json.
	Retour : <comment>{"creer_preview":"oui","taille_preview":"250","image_process":"imagick"}</comment>

<info>spip config:ecrire facteur,oembed --json -f tmp/retour_config.json</info> : retourne la configuration au format json des plugins facteur et oembed dans le fichier <comment>tmp/retour_config.json</comment>

Quand on utilise l\'option `--json` le retour peut-être utilisé directement en entrée de la commande <comment>spip config:ecrire ...</comment>
	',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Spip $spip */
        $this->demarrerSpip();
        $Toptions = [];
        $fichier = false;

        if ($options = $input->getArgument('option')) {
            $Toptions = array_map('trim', explode(',', $options));
        }
        if (!count($Toptions)) {
            $output->writeln("<error>Il faut au moins un argument : nom de l\'intitule à lire dans spip_meta</error>");
            return Command::FAILURE;
        }

        if ($fichier = $input->getOption('fichier')) {
            if (!$handle = spip_fopen_lock($fichier, 'w', _SPIP_LOCK_MODE)) {
                $output->writeln("<error>$fichier n\'est pas accessible en écriture.</error>");
                return Command::FAILURE;
            }
            spip_fclose_unlock($handle);
        }

        $format_json = !!$input->getOption('json');

        $this->lireConfig($Toptions, $format_json, $fichier);
        return Command::SUCCESS;
    }
}
