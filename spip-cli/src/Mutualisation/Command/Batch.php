<?php

namespace Spip\Cli\Mutualisation\Command;

use Spip\Cli\Console\Style\SpipCliStyle;
use Spip\Cli\Loader\Spip;
use Spip\Cli\Loader\Sql;
use Spip\Cli\Mutualisation\Spl\SplFileInfoHost;
use Spip\Cli\Sql\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class Batch extends Command
{
    /**
     * @var SpipCliStyle
     */
    protected $io;

    protected $dir_sites = 'sites';

    protected $connect = 'config/connect.php';

    protected function configure()
    {
        $this
            ->setName('Batch')
            ->setDescription('Exécute une commande sur les sites d’une mutualisation')
            ->addArgument('glob', InputArgument::OPTIONAL, 'Filtrer les sites à appliquer (* pour tous)')
            ->addArgument('spip-cli-command', InputArgument::OPTIONAL, 'La commande SpipCli à exécuter')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->io = new SpipCliStyle($input, $output);
        $io->title('Spip Cli Mutualisation');

        $spip = new Spip($this->getApplication()->getService('spip.directory'));
        if (!$spip->exists()) {
            $io->fail('SPIP introuvable dans ' . $spip->getDirectory());
            return Command::FAILURE;
        }
        $io->check('SPIP trouvé.');

        $dir_sites = $spip->getDirectory() . DIRECTORY_SEPARATOR . $this->dir_sites;
        if (!is_dir($dir_sites)) {
            $io->fail('Pas de répertoire ' . $this->dir_sites . ' trouvé.');
            return Command::FAILURE;
        }
        $io->check("Répertoire <info>{$this->dir_sites}</info> trouvé.");

        $glob = $input->getArgument('glob') ?: '*';
        $command = $input->getArgument('spip-cli-command');

        $sites = $this->getSites($dir_sites, $glob);
        if ($sites) {
            $io->check(count($sites) . " sites correspondent au critère <info>$glob</info>");
        } else {
            $io->fail("Aucun site ne correspond au critère <info>$glob</info>");
            return Command::FAILURE;
        }

        // retrouver l’url de chaque site
        $hosts = $this->getHosts($sites);
        if ($hosts) {
            $this->presenterHosts($hosts, $command);
        } else {
            $io->fail('Aucun site valide');
            return Command::FAILURE;
        }

        if (!$command) {
            $io->note('Aucune commande à exécuter.');
            return Command::FAILURE;
        }

        if (!$this->getPhpBin()) {
            $io->fail('Executable PHP non trouvé');
            return Command::FAILURE;
        }

        foreach ($hosts as $site) {
            $this->executeCommandOnSite($command, $site);
        }
        return Command::SUCCESS;
    }

    protected function executeCommandOnSite($command, SplFileInfoHost $site)
    {
        $io = $this->io;
        $io->section($site->getHost());
        $spip_cli_path = $this->getApplication()
            ->getService('path.spip-cli');
        $php = $this->getPhpBin();
        if ('\\' === DIRECTORY_SEPARATOR) {
            $cmd = "set \"HTTP_HOST={$site->getHost()}\" && $php $spip_cli_path $command";
        } else {
            $cmd = "HTTP_HOST=\"{$site->getHost()}\" $php $spip_cli_path " . $command;
        }

        # echo $cmd . "\n";
        passthru($cmd);
        $io->text(['', '']);
    }

    protected function getPhpBin()
    {
        $executableFinder = new PhpExecutableFinder();
        return $executableFinder->find();
    }

    /**
     * Retourne une liste de répertoires correspondant au critère 'glob' transmis.
     * @param string $dir_sites
     * @param string $glob
     * @return \SplFileInfo[]
     */
    protected function getSites($dir_sites, $glob = '*')
    {
        $dirs = new \GlobIterator($dir_sites . DIRECTORY_SEPARATOR . $glob, \FilesystemIterator::SKIP_DOTS);
        $sites = [];
        foreach ($dirs as $dir) {
            if ($dir->isDir()) {
                $sites[] = $dir;
            }
        }
        return $sites;
    }

    /**
     * Retrouve le host des sites indiqués.
     *
     * Enlève les sites dont on ne sait pas retrouver le host.
     *
     * @param \SplFileInfo[] $sites
     * @return SplFileInfoHost[]
     */
    protected function getHosts(array $sites): array
    {
        $valides = [];
        /** @var \SplFileInfo $site */
        foreach ($sites as $site) {
            try {
                $host = $this->calculerHost($site);
            } catch (\Exception $e) {
                $this->io->fail(
                    'Site ignoré : <info>' . $site->getFilename() . '</info> (URL non calculable). ' . $e->getMessage(),
                );
                continue;
            }
            $sitehost = new SplFileInfoHost($site);
            $sitehost->setHost($host);
            $valides[] = $sitehost;
        }
        return $valides;
    }

    /**
     * Retrouve le host du site.
     * @return string
     */
    protected function calculerHost(\SplFileInfo $site)
    {
        $connect_file = $site->getPathname() . DIRECTORY_SEPARATOR . $this->connect;
        $query = new Query(new Sql($connect_file));
        $adresse = $query->getMeta('adresse_site');
        if ($adresse) {
            $host = parse_url($adresse, PHP_URL_HOST);
            if (!$this->is_valid_domain_name($host)) {
                throw new \Exception('Adresse du site incorrecte : ' . $host);
            }
            return $host;
        }
        throw new \Exception('Adresse du site non renseignée en base.');
    }

    /**
     * @link https://stackoverflow.com/a/4694816
     * @return bool
     */
    protected function is_valid_domain_name($domain_name)
    {
        return preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match('/^.{1,253}$/', $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name); //length of each label
    }

    /**
     * Présente la liste des sites trouvés… mais pas plus de 10…
     */
    protected function presenterHosts(array $hosts, $command = false)
    {
        $io = $this->io;
        $io->check(count($hosts) . ' sites valides');
        $io->text('');
        $_hosts = $affiches = array_map(fn($site) => $site->getHost(), $hosts);
        if (count($_hosts) > 10 && $command) {
            $affiches = array_slice($_hosts, 0, 5);
            $affiches[] = '[...]';
            $affiches = array_merge($affiches, array_slice($_hosts, -5, 5));
        }
        $io->listing($affiches);
    }
}
