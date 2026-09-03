<?php

namespace Spip\Cli;

use Psr\Container\ContainerInterface;
use Spip\Cli\Console\Style\SpipCliStyle;
use Spip\Cli\Container\Container;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Application extends ConsoleApplication
{
    public const NAME = 'Spip Cli';

    public const VERSION = '2.0.1';

    /**
     * @var Container
     */
    protected $container;

    /**
     * Charge automatiquement les commandes présentes
     * dans le répertoire Command de ce projet.
     */
    public function __construct(array $options = [])
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->container = new Container([], $options + [
            'debug' => false,
            'spip.directory' => null,
            'spip.webmestre.email' => '',
            'spip.webmestre.login' => '',
            'spip.webmestre.nom' => '',
            'spip.webmestre.login.prefixe' => 'SpipCli-',
            'cwd' => getcwd(),
        ]);

        $this->setTimezone();
        $this->registerServices();
        $this->registerCommandsInProject(self::class);
        $this->loadSpip(); // hum…
        $this->registerCommandsInSpip();
    }

    /**
     * @note
     * Alors ça, il faudrait plutôt que ça soit les commandes
     * qui demandent le chargement de SPIP si elles en ont besoin,
     * afin de faire des commandes où SPIP n’est pas chargé encore…
     */
    public function loadSpip(): void
    {
        try {
            $spip = $this->container->get('loader.spip');
            $spip->load();
        } catch (\Exception $e) {
            $io = $this->getIo();
            if (!in_array($io->getVerbosity(), [OutputInterface::VERBOSITY_QUIET, OutputInterface::VERBOSITY_NORMAL])) {
                $io->note($e->getMessage());
            }
        }
    }

    /**
     * Ajoute les commandes contenues dans le répertoire Command du même chemin que la classe transmise
     */
    public function registerCommandsInProject($class): void
    {
        foreach ($this->findCommandsInProject($class) as $command) {
            if (class_exists($command)) {
                $this->add(new $command());
            }
        }
    }

    /**
     * Retourne la liste des commandes au même niveau que ce projet
     */
    public function findCommandsInProject($class): array
    {
        $class = new \ReflectionClass($class);
        $commandDir = dirname($class->getFilename()) . '/Command'; // .../spip-cli/src/Command
        $list = [];
        if (is_dir($commandDir)) {
            $namespace = $class->getNamespaceName(); // Spip\Cli
            $finder = new Finder();
            $finder->files()
                ->in($commandDir)
                ->name('*.php');
            foreach ($finder as $file) {
                // Spip\Cli\Command\Name
                $list[] = $namespace . '\\Command\\' . str_replace(
                    '/',
                    '\\',
                    substr($file->getRelativePathname(), 0, -4),
                );
            }
        }
        return $list;
    }

    /**
     * Chargement automatique des commandes spip-cli/ présentes dans les répertoires spip-cli/
     * des plugins SPIP
     */
    public function registerCommandsInSpip(): bool
    {
        // les commandes dans spip-cli/ des plugins SPIP actifs
        $this->registerSpipCliPluginsCommands();
        // charger les vilaines globales utilisées dans les commandes spip-cli
        try {
            global $spip_racine, $spip_loaded, $cwd;
            $cwd = getcwd();
            $spip = $this->container->get('loader.spip');
            $spip_racine = $spip->getDirectory();
            $spip_loaded = $spip->load();
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Chargement des commandes présentes dans 'spip-cli/' des plugins SPIP actifs.
     *
     * Nécessite de démarrer SPIP (et donc d’être dans un SPIP)
     */
    public function registerSpipCliPluginsCommands(): bool
    {
        try {
            $spip = $this->container->get('loader.spip');
            if (!$spip->load()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        $cwd = getcwd();
        $spip->chdir();
        $commandes = find_all_in_path('spip-cli/', '.*[.]php$');
        // pas le spip.php à la racine de ce plugin (l’ancien exécutable pour démarrer spip-cli);
        $commandes = array_filter($commandes, fn($commande) => strpos($commande, 'spip-cli/spip.php') === false);
        foreach ($commandes as $path) {
            $this->registerSpipCliCommand($path);
        }
        chdir($cwd);
        return true;
    }

    /**
     * Déclare une commande "Spip Cli"
     */
    public function registerSpipCliCommand($path): void
    {
        include_once($path);
        $command = '\\' . basename($path, '.php');
        if ($namespace = Tools\Files::getNamespace($path)) {
            $command = '\\' . $namespace . $command;
        }
        if (class_exists($command)) {
            $this->add(new $command());
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @return mixed
     */
    public function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * Replace sur le répertoire de démarrage de l’application
     */
    public function restoreCwd(): void
    {
        $cwd = $this->container->get('cwd');
        chdir($cwd);
    }

    /**
     * Retourne un écriveur pour console (étend SymfonyStyle)
     */
    public function getIo(?InputInterface $input = null, ?OutputInterface $output = null): SpipCliStyle
    {
        $io = $this->container->get('console.io');
        return $io($input, $output);
    }

    /**
     * Affiche l’aide d’une commande donnée
     * @param string $commandName
     */
    public function showHelp($commandName, ?OutputInterface $output = null): void
    {
        if ($output === null) {
            $output = new ConsoleOutput();
        }
        $command = $this->find('help');
        $arguments = [
            'command' => 'help',
            'command_name' => $commandName,
        ];
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }

    protected function registerServices(): void
    {
        $app = $this;
        $container = $this->container;
        $container->set(
            'console.io',
            fn() => function (?InputInterface $input = null, ?OutputInterface $output = null) use ($app) {
                if ($input === null) {
                    $input = new ArgvInput();
                }
                if ($output === null) {
                    $output = new ConsoleOutput();
                    $app->configureIO($input, $output);
                }
                return new Console\Style\SpipCliStyle($input, $output);
            },
        );
        $container->set('loader.spip', function (ContainerInterface $container) {
            $spip = new Loader\Spip($container->get('spip.directory'));
            $spip->setContainer($container);
            return $spip;
        });
        $container->set('sql.query', function (ContainerInterface $container) {
            $connect = $container->get('loader.spip')
                ->getPathConnect();
            if (!is_file($connect)) {
                throw new \Exception('SPIP database is not configured');
            }
            $sql = new Loader\Sql($connect);
            return new Sql\Query($sql);
        });
    }

    /**
     * If the timezone is not set anywhere, set it to UTC.
     */
    protected function setTimezone(): void
    {
        if (ini_get('date.timezone') == false) {
            // on se synchro sur la date system si possible
            $date = trim(shell_exec('date -R'));
            $date = explode(' ', $date);
            if (
                preg_match(',^[+-][0-9][0-9][0-9][0-9]$,', end($date))
                && ($offset_target = intval(end($date)) / 100)
                && ($zone = timezone_name_from_abbr('', 3600 * $offset_target, 0))
            ) {
                date_default_timezone_set($zone);
                // se recaler en cas de daylight saving time
                $offset_real = intval(date_offset_get(new \DateTime()) / 3600);
                if ($offset_real > $offset_target) {
                    $zone = timezone_name_from_abbr('', 3600 * $offset_target, 1);
                    date_default_timezone_set($zone);
                }
            } else {
                date_default_timezone_set('UTC');
            }
        }
    }
}
