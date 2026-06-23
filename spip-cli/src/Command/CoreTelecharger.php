<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoreTelecharger extends Command
{
    public const _MAX_LOG_LENGTH = 100;

    public const _BRANCHE_STABLE = '4.4';

    // Méthode de téléchargement, par défaut SPIP entier
    protected $methode = 'spip';

    // La source de ce qu'on veut télécharger
    protected $source = '?';

    // Le dossier où télécharger, par défaut le dossier courant
    protected $dest = '.';

    // Branche ou tag demandé
    protected $branche = '';

    // Révision
    protected $revision = '';

    // Le type de version qui sera téléchargé, par défaut une release fixe (tag)
    protected $version_type = 'release';

    // La version demandée au départ, précise ou flou suivant une branche
    protected $version_demandee = '';

    // La version précise qui sera téléchargée
    protected $version_precise = '';

    // L'URL SVN où télécharger la version
    protected $url = '';

    // Le tableau des options
    protected $options = [];

    // Procéder silencieusement : quiet
    protected $quiet = '';

    // mode dev pour composer
    protected $mode_dev = false;

    // Repo
    protected $url_repo_base = '';

    // json plugins-dist/composer
    protected $json = false;

    // mirroirs git
    protected $git_mirrors = [
        // 'https://www.git_origin.org/' => [ 'https://www.git_mirror1.org/', 'https://www.git_mirror2.org/',... ]
        // 'https://git.spip.net/' => [ 'https://git-mirror.spip.net/' ],
    ];

    /**
     * Logs des commits plus recents disponibles pour une mise a jour
     *
     * @return string
     */
    public function logupdate(&$input, &$output)
    {
        $methodes = ['git', 'svn'];

        foreach ($methodes as $methode) {
            if (
                method_exists($this, $fonction_info = $methode . '_info')
                && ($infos = $this->{$fonction_info}($this->dest, ['format' => 'assoc']))
                && method_exists($this, $fonction_log = $methode . '_log')
            ) {
                $options = [
                    'from' => $infos['revision'],
                ];

                if ($branche = $input->getOption('branche')) {
                    $options['branche'] = $branche;
                }

                $log = $this->{$fonction_log}($options);

                if (strlen($log)) {
                    $this->io->info(["Mise à jour disponible pour {$infos['source']} :", $log]);
                } else {
                    $this->io->info('Aucune maj pour ' . $infos['source']);
                }
                // On s'arrête
                return;
            }
        }
    }

    /**
     * Message d'erreur si un repertoire existe
     * (ou suppression si variable environnement posee ET chemin safe)
     */
    public function erreur_repertoire_existant(&$input, &$output)
    {
        $this->io->error($this->erreur);
        $dir = trim($this->dir);
        if (strpos($dir, '/') !== 0 && strpos($dir, '.') !== 0 && strpos($dir, '..') === false) {
            $question = "Voulez vous supprimer $this->dest et tout son contenu ?  (sinon il sera archivé en {$this->dest}_OLD) ";
            if (isset($this->options['force_rm']) || $this->io->confirm($question, false)) {
                $this->io->comment("...SUPPRESSION $dir");
                exec("rm -fR $dir");
            }
            return;
        }
        $this->io->comment("Supprimez le repertoire $this->dir ou choisissez une autre destination");
        exit(1);
    }

    /**
     * Lancer un checkout
     *
     * @return string
     */
    public function checkout(&$input, &$output)
    {
        if (!$checkout = $this->get_checkout_function($this->methode)) {
            $this->io->error(
                "Méthode {$this->methode} inconnue pour télécharger {$this->source} vers {$this->dest}",
            );
        } else {
            $this->{$checkout}($input, $output);
        }
    }

    /**
     * Recuperer la fonction checkout
     * @param string $methode
     * @return string
     */
    public function get_checkout_function($methode)
    {
        $checkout = $methode . '_checkout';
        if (method_exists($this, $checkout)) {
            return $checkout;
        }
        return '';
    }

    /**
     * Fausse méthode raccourcie pour checkout SPIP complet
     */
    public function spip_checkout(&$input, &$output)
    {
        $ssh = false;
        if ($this->source && strpos($this->source, 'git@git.spip.net') !== false) {
            $ssh = true;
        }
        //$branche = $this->branche ? $this->branche : 'master';
        $branche = $this->spip_branche_or_tag_name($this->branche);

        if ($this->source && strpos($this->source, 'git@git.spip.net') !== false) {
            $ssh = true;
        }
        $url_repo_base = 'https://git.spip.net/spip/';
        if ($ssh) {
            $url_repo_base = 'git@git.spip.net:spip/';
        }
        $this->url_repo_base = $url_repo_base;

        // On checkout SPIP sur la bonne branche ou tag, une première fois
        $this->options['branche'] = $branche;
        $this->methode = 'git';
        $this->source = $url_repo_base . 'spip.git';
        $this->io->info("spip dl git -b {$branche} $this->source -d $this->dest");

        $this->checkout($input, $output);

        $file_plugins_dist = 'plugins-dist.json';
        $file_composer = 'composer.json';
        if (!file_exists("$this->dest/$file_plugins_dist")) {
            // pas de plugins-dist.json : SPIP 2 ou SPIP 5 ?
            if (!file_exists("$this->dest/$file_composer")) {
                // pas de composer.json : on est en SPIP 2
                $this->spip_checkout_plugins_old_version($input, $output);
            } else {
                // on est en SPIP 5 : install via composer
                $no_dev = $this->mode_dev ? [] : ['composer-no-dev' => 1];
                $this->spip_run_composer_install($this->dest, $no_dev);
            }
        } else {
            $json = file_get_contents("$this->dest/$file_plugins_dist");
            $json = json_decode($json, true);
            $this->json = $json;
            $this->spip_checkout_plugins_json($input, $output);
        }
    }

    /**
     * formater correctement branche / version
     *
     * @param string $branche
     * @return string branche
     **/
    public function spip_branche_or_tag_name($branche)
    {
        // Historique avant le 27 09 2020, les branches SPIP étaient 'spip-3.2'
        if (strpos($branche, 'spip-') === 0) {
            $branche = substr($branche, 5);
        }
        // Tag sans le 'v' ?
        if (
            $branche[0] !== 'v'
            && count(explode('.', $branche)) > 2
            // branches anciennes assez spécifiques...
            && !in_array($branche, ['1.9.1', '1.9.2'])
        ) {
            $branche = 'v' . $branche;
        }
        return $branche;
    }

    /**
     * Retrouve la branche ou le tag à utiliser pour un plugin du core
     *
     * - Historique avant le 27 09 2020, les branches SPIP des plugins dist étaient '3.2', après 'spip-3.2'
     * - Historique avant le 08 07 2021, les branches SPIP des plugins dist étaient 'spip-3.2', après 'X' ou 'X.Y' du plugin en question (plugins-dist.json déclare la branche)
     * - Historique avant le 01 02 2022, Les tags étaient tel que 'spip/v3.2.11' (+ tag de version du plugin) dans les plugins (en plus du tag de version du plugin)
     * - On utilise la clé 'tag' (indiquant le tag du plugin) dans plugins-dist.json
     *
     * Si on demande un tag (vX.Y.Z) de SPIP,
     *    - on retourne la clé 'tag' dans la déclaration plugins-dist.json
     *    - sinon on retourne un nom de tag spip/vX.Y.Z
     * Sinon, on regarde la déclaration du plugins-dist.json s'il a une déclaration 'branch'
     * Sinon on calcule "à peu près" en supposant un SPIP plus ancien
     *
     * @param string $spip_branch Nom de la branche ou tag demandé pour le SPIP
     * @param array $external Déclaration de plugin-dist.json pour ce plugin
     * @return string
     */
    public function spip_checkout_plugins_core_branch($spip_branch, $external)
    {
        $branch = $external['branch'] ?? null;
        $tag = $external['tag'] ?? null;

        // Si tag
        if ($spip_branch[0] === 'v') {
            return $tag ?: 'spip/' . substr($spip_branch, 1);
        } elseif ($branch) {
            return $branch;
        } elseif ($spip_branch === 'master') {
            return 'master';
        }
        // branches < spip 4.0
        return 'spip-' . $spip_branch;

    }

    /**
     * checkout git des plugins-dist
     *
     **/
    public function spip_checkout_plugins_json(&$input, &$output)
    {
        $https_repo_base = 'https://git.spip.net/spip/';

        $dest_racine = $this->dest;

        foreach ($this->json as $external) {
            $e_dest = $dest_racine . '/' . $external['path'];
            $e_source = $external['source'];
            $e_source = str_replace($https_repo_base, $this->url_repo_base, $e_source);
            $e_branche = $this->spip_checkout_plugins_core_branch($this->branche, $external);
            $d = dirname($e_dest);
            if (!is_dir($d)) {
                mkdir($d);
            }
            $this->io->info("spip dl git -b {$e_branche} $e_source -d $e_dest");
            $this->source = $e_source;
            $this->methode = 'git';
            $this->dest = $e_dest;
            $this->options['branche'] = $e_branche;
            $this->checkout($input, $output);
        }
        $this->io->info('fin spip_checkout_plugins_json');
    }

    /**
     * récupération des plugins-dist si ancienne version (antérieure 3.2)
     *
     **/
    public function spip_checkout_plugins_old_version(&$input, &$output)
    {

        $file_externals = '.gitsvnextmodules';
        $file_plugins_dist = 'plugins-dist.json';
        $file_externals_master = "$this->dest/$file_externals";
        $file_plugins_dist_master = "$this->dest/$file_plugins_dist";

        if (
            !file_exists($file_plugins_dist)
            && !file_exists($file_plugins_dist_master)
            && !file_exists($file_externals)
            && !file_exists($file_externals_master)
        ) {
            // on commence par checkout SPIP en master pour recuperer le plugins-dist.json (ou anciennement file externals)
            $this->options['branche'] = 'master';
            $this->methode = 'git';
            $this->url_repo_base = $this->url_repo_base . 'spip.git';
            $this->checkout($input, $output);

            if (file_exists($file_plugins_dist_master)) {
                @copy($file_plugins_dist_master, $file_plugins_dist);
            } elseif (file_exists($file_externals_master)) {
                @copy($file_externals_master, $file_externals);
            }
            // on checkout SPIP... une 2è ou 3è fois sur la bonne branche
            $this->options['branche'] = $this->branche;
            $this->checkout($input, $output);
        }

        // version moderne :)
        if (file_exists($f = $file_plugins_dist) || file_exists($f = $file_plugins_dist_master)) {
            $this->io->info("Obtention des plugins-dist via $file_plugins_dist (master) ...");
            $json = file_get_contents("$this->dest/$file_plugins_dist");
            $json = json_decode($json, true);
            if (in_array($this->branche, ['3.2', '3.1', '3.0'])) {
                unset($json['bigup']);
            }
            spip_checkout_plugins_json($json, $this->url_repo_base, $this->dest, $this->branche);
            $this->io->info('fin spip_checkout_plugins_old_version');
            return;
        }

        // old school
        $this->io->info("Obtention des plugins-dist via $file_externals (master) ...");
        if (file_exists($f = $file_externals_master) || file_exists($f = $file_externals)) {
            $externals = parse_ini_file($f, true);

            $base_dest = $this->dest;
            foreach ($externals as $external) {

                $e_methode = $external['remote'];
                $e_source = $external['url'];
                $e_dest = $this->dest . '/' . $external['path'];
                // Historique avant le 27 09 2020, les branches SPIP des plugins dist étaient '3.2'
                if ($this->branche === 'master') {
                    $e_branche = $this->branche;
                } else {
                    $e_branche = 'spip-' . $this->branche;
                }

                // remplacer les sources SVN _core_ par le git.spip.net si possible
                if ($e_methode == 'svn') {
                    if (strpos($e_source, 'svn://zone.spip.org/spip-zone/_core_/plugins/') === 0) {
                        $e_source = explode('_core_/plugins/', $e_source);
                        $e_source = $this->url_repo_base . end($e_source) . '.git';
                        $e_methode = 'git';
                    } elseif (strpos($e_source, 'svn://zone.spip.org/spip-zone/_core_/tags/') === 0) {
                        // zone.spip.org/spip-zone/_core_/tags/spip-3.2.7/plugins/aide
                        $e_source = explode('_core_/tags/', $e_source);
                        $e_source = explode('/', end($e_source));
                        $e_branche = array_shift($e_source);
                        $e_branche = str_replace('-', '/', $e_branche);
                        array_shift($e_source);
                        $e_source = $this->url_repo_base . implode('/', $e_source) . '.git';
                        $e_methode = 'git';
                    } elseif (strpos($e_source, 'https://github.com/') === 0) {
                        if (in_array($this->branche, ['3.2', '3.1', '3.0'])) {
                            continue;
                        }
                        $e_source = explode('//github.com/', $e_source);
                        $e_source = explode('/', end($e_source));
                        $user = array_shift($e_source);
                        $repo = array_shift($e_source);
                        $what = array_shift($e_source);
                        switch ($what) {
                            case 'branches':
                                array_shift($e_source);
                                $e_branche = reset($e_source);
                                break;
                            case 'trunk':
                            default:
                                $e_branche = 'master';
                                break;
                        }
                        $e_source = "https://github.com/$user/$repo.git";
                        // renommage a la volee
                        $e_source = str_replace(
                            ['https://github.com/marcimat/bigup'],
                            [$this->url_repo_base . 'bigup'],
                            $e_source,
                        );
                        $e_methode = 'git';
                    }
                }
                $d = dirname($e_dest);
                if (!is_dir($d)) {
                    mkdir($d);
                }

                $this->io->info("spip dl $e_methode -b {$e_branche} $e_source -d $e_dest");
                $this->options['branche'] = $e_branche;
                $this->methode = $e_methode;
                $this->source = $e_source;
                $this->dest = $e_dest;
                $this->checkout($input, $output);

                $this->dest = $base_dest;
            }
        }
    }

    /**
     * SVN
     */

    /**
     * Déployer un dépôt SVN depuis source et révision donnees
     *
     * @return string
     */
    public function svn_checkout(&$input, &$output)
    {
        $user = $pass = '';

        $revision = (isset($this->options['revision']) && $this->options['revision']) ? $this->options['revision'] : $this->revision;
        $source = $this->source;
        // la source est obligatoire en SVN (pas de par défaut)
        if ($source === '?') {
            $this->io->error('Méthode SVN : le paramètre source est obligatoire');
            exit(1);
        }

        $parts = parse_url($source);
        if (!empty($parts['user']) && !empty($parts['pass'])) {
            $user = $parts['user'];
            $pass = $parts['pass'];
            $source = str_replace("://$user:$pass@", '://', $this->source);
        }

        $checkout_needed = false;

        if (is_dir($this->dest)) {
            $infos = $this->svn_info($this->dest, ['format' => 'assoc']);
            if (!$infos) {
                $this->erreur = "$this->dest n'est pas au format SVN";
                $this->dir = $this->dest;
                $this->erreur_repertoire_existant($input, $output);
                $checkout_needed = true;
            } elseif ($infos['source'] !== $source) {
                // gerer le cas particulier ou le repo a mv dans un sous dossier trunk ou branches/.. mais on pointe sur une revision anterieure
                // du coup le svn info renvoi toujours l'ancien dossier :(
                $checkout_needed = true;
                if (strpos($source, (string) $infos['source']) === 0) {
                    $subfolder = ltrim(substr($source, strlen($infos['source'])), DIRECTORY_SEPARATOR);
                    if (strpos($subfolder, 'branches/') !== false || $subfolder === 'trunk') {
                        if (!file_exists($this->dest . DIRECTORY_SEPARATOR . $subfolder)) {
                            if ($revision == $infos['revision']) {
                                $checkout_needed = false;
                                $command = "$this->dest sur $source Revision " . $revision . " (avant passage en $subfolder)";
                            }
                        }
                    }
                }
                if ($checkout_needed) {
                    $this->erreur = "$this->dest n'est pas sur le bon repository SVN";
                    $this->dir = $this->dest;
                    $this->erreur_repertoire_existant($input, $output);
                }
            } elseif (!$revision || $revision != $infos['revision']) {
                $command = 'svn up ';
                if ($revision) {
                    $command .= '-r' . $revision . ' ';
                }
                if (isset($this->options['literal'])) {
                    $command .= $this->options['literal'] . ' ';
                }

                $command .= "$this->dest";
                $this->io->info($command);
                passthru($command);
            } else {
                $command = "$this->dest deja sur $source Revision " . $revision;
            }
        } else {
            $checkout_needed = true;
        }
        clearstatcache();

        if ($checkout_needed) {
            $dest_co = $this->dest;
            while (is_dir($dest_co) && $dest_co !== '.') {
                $dest_co .= '_';
            }
            $command = 'svn co ';
            if ($revision) {
                $command .= '-r' . $revision . ' ';
            }
            if (isset($this->options['literal'])) {
                $command .= $this->options['literal'] . ' ';
            }
            if ($user && $pass) {
                $command .= "--username $user --password $pass ";
            }

            $command .= "$source $dest_co";
            $this->io->info($command);
            passthru($command);
            if ($dest_co !== $this->dest) {
                $command = "mv $this->dest {$dest_co}_OLD && mv $dest_co $this->dest";
                // faut il effacer le répertoire original ?
                $question = "Le répertoire existant $this->dest a été renommé en {$dest_co}_OLD. Souhaitez vous le supprimer ? ";
                if (isset($this->options['force_rm']) || $this->io->confirm($question, false)) {
                    $command .= " && rm -fR {$dest_co}_OLD";
                }
                $this->io->info($command);
                passthru($command);
            }
        }

        $this->io->info("OK $command");
    }

    /**
     * Loger les modifs d'une source, optionnellement entre 2 revisions
     *
     * @param array $options
     *   from : revision de depart, non inclue
     *   to : revision de fin
     * @return string
     */
    public function svn_log($options)
    {
        $r = '';
        if (isset($options['from']) || isset($options['to'])) {
            $from = 0;
            $to = 'HEAD';
            if (isset($options['from'])) {
                $from = ($options['from'] + 1);
            }
            if (isset($options['to'])) {
                $to = $options['to'];
            }
            $r = " -r $from:$to";
        }
        exec("svn log$r {$this->dest}", $res);

        $output = '';
        $comm = '';
        foreach ($res as $line) {
            if (preg_match(',^r\d+,i', $line) && count(explode('|', $line)) > 3) {
                if (strlen($comm) > self::_MAX_LOG_LENGTH) {
                    $comm = substr($comm, 0, self::_MAX_LOG_LENGTH) . '...';
                }

                $line = explode('|', $line);
                $date = explode('(', $line[2]);
                $date = reset($date);
                $date = strtotime($date);
                $output .=
                    $comm
                    . "\n"
                    . $line[0]
                    . '|'
                    . $line[1]
                    . '| '
                    . date('Y-m-d H:i:s', $date)
                    . ' |';
                $comm = '';
            } else {
                $comm .= " $line";
            }
        }

        if (strlen($comm) > self::_MAX_LOG_LENGTH) {
            $comm = substr($comm, 0, self::_MAX_LOG_LENGTH) . '...';
        }
        $output .= $comm;

        // reclasser le commit le plus recent en premier, git-style
        $output = explode("\n", $output);
        $output = array_reverse($output);
        $output = implode("\n", $output);

        return trim($output);
    }

    /**
     * GIT
     */

    /**
     * Deployer un repo GIT depuis source et revision donnees
     *
     * @return string|array
     */
    public function git_checkout(&$input, &$output)
    {
        $checkout_needed = false;

        // gérer la source par défaut si appel direct de la méthode git
        $source_init = $this->source;
        if ($this->source === '?') {
            $url_repo_base = 'https://git.spip.net/spip/';
            $this->url_repo_base = $url_repo_base;
            $this->source = $url_repo_base . 'spip.git';
        }

        $curdir = getcwd();
        $branche = (isset($this->options['branche']) && $this->options['branche']) ? $this->options['branche'] : $this->branche;
        $revision = (isset($this->options['revision']) && $this->options['revision']) ? $this->options['revision'] : $this->revision;

        // Si le dossier voulu existe déjà ET qu'il est déjà rempli
        if (is_dir($this->dest) && count(scandir($this->dest)) > 2) {
            $infos = $this->git_info($this->dest, ['format' => 'assoc']);

            // patch pour les installs obsolètes faites avec un checkout.php qui indiquait des URLs de repo de la forme git.spip.net/SPIP
            if (
                isset($infos['source'])
                && strstr($infos['source'], 'git.spip.net/SPIP')
                && strtolower($infos['source']) === $this->source
            ) {
                $this->io->comment(
                    'URL de repo SPIP obsolète (' . $infos['source'] . '): passage sur ' . strtolower(
                        $infos['source'],
                    ),
                );
                $infos['source'] = strtolower($infos['source']);
                $cmd_maj = 'git remote set-url origin ' . $infos['source'];
                chdir($this->dest);
                passthru($cmd_maj);
                chdir($curdir);
            }

            if (!$infos) {
                $this->erreur = "{$this->dest} n’est ni un dépôt Git ni un répertoire vide.";
                $this->dir = $this->dest;
                $this->erreur_repertoire_existant($input, $output);
                $checkout_needed = true;
            } elseif ($infos['source'] !== $this->source && $source_init !== '?') {
                $this->io->error("{$this->dest} n’est est pas sur le bon dépôt Git.");
                $checkout_needed = true;
            } elseif (
                !$revision || !isset($infos['revision']) || $this->git_compare_revisions(
                    $revision,
                    $infos['revision'],
                ) !== 0
            ) {
                $this->git_check_mirrors($this->dest, $this->source);
                chdir($this->dest);
                $command = 'git fetch --all --prune --prune-tags';
                passthru($command);

                if ($revision) {
                    $command = "git checkout --detach $revision";
                    $this->io->info($command);
                    passthru($command);
                    chdir($curdir);
                } else {
                    $command = 'git pull --rebase';
                    if ($infos['modified']) {
                        $command = "git stash && $command && git stash pop";
                    }
                    if (!isset($infos['branche']) || $infos['branche'] !== $branche) {
                        $command = "git checkout $branche && $command";
                    }
                    $this->io->info($command);
                    passthru($command);
                }

                chdir($curdir);
            } else {
                $this->io->info("{$this->dest} est déja sur {$this->source} avec la révision $revision");
            }
        } else {
            $checkout_needed = true;
        }
        clearstatcache();

        if ($checkout_needed) {
            $dest_co = $this->dest;
            while (is_dir($dest_co) && $dest_co !== '.') {
                $dest_co .= '_';
            }
            $command = 'git clone ';
            $command .= "$this->source $dest_co";
            $this->io->info($command);
            passthru($command, $error);

            if (!is_dir($dest_co) || $error) {
                if ($urls_alt = $this->git_get_urls_mirrors($this->source)) {
                    foreach ($urls_alt as $source_alt) {
                        $command = 'git clone ';
                        $command .= "$source_alt $dest_co";
                        $this->io->info($command);
                        passthru($command, $error);

                        if (is_dir($dest_co) && !$error) {
                            break;
                        }
                    }
                    if (is_dir($dest_co)) {
                        $command = 'git remote rename origin mirror';
                        $this->io->info($command);
                        passthru("cd $dest_co && $command");
                        $command = "git remote add origin $this->source";
                        $this->io->info($command);
                        passthru("cd $dest_co && $command");
                    }
                }
            }
            if (is_dir($dest_co)) {
                $this->git_check_mirrors($dest_co, $this->source);
                if (isset($options['revision'])) {
                    chdir($dest_co);
                    $command = 'git checkout --detach ' . $options['revision'];
                    $this->io->info($command);
                    passthru($command);
                    chdir($curdir);
                } elseif ($branche !== 'master') {
                    chdir($dest_co);
                    $command = "git checkout $branche";
                    $this->io->info($command);
                    passthru($command);
                    chdir($curdir);
                }
                if ($dest_co !== $this->dest) {
                    $command = "mv $this->dest {$dest_co}_OLD && mv $dest_co $this->dest";
                    // faut il effacer le répertoire original ?
                    $question = "Le répertoire existant $this->dest a été renommé en {$dest_co}_OLD. Souhaitez vous le supprimer ?";
                    if (isset($this->options['force_rm']) || $this->io->confirm($question, true)) {
                        $command .= " && rm -fR {$dest_co}_OLD";
                    }
                    $this->io->info($command);
                    passthru($command);
                }
            }
        }
    }

    /**
     * @param string $rev1
     * @param string $rev2
     * @return int
     */
    public function git_compare_revisions($rev1, $rev2)
    {
        //		exec("git log |grep $revision", $sortie);
        $len = min(strlen($rev1), strlen($rev2));
        $len = max($len, 7);

        return strncmp($rev1, $rev2, $len);
    }

    /**
     * Lire source et révision d'un répertoire Git et reconstruire la ligne de commande
     * @param array $options
     * @return string|array
     * 		Retourne la ligne de commande ou un tableau des informations
     */
    public function git_info($dest, $options)
    {
        if (!is_dir("{$dest}/.git")) {
            return '';
        }

        $remotes = $this->git_get_remotes($dest);
        if (!$remotes) {
            return '';
        }

        $curdir = getcwd();
        chdir($dest);

        if (isset($remotes['origin'])) {
            $source = $remotes['origin'];
        } else {
            $source = reset($remotes);
        }

        $modified = false;
        $branche = false;
        exec('git status -b -s', $output);
        if (count($output) > 1) {
            $full = implode("|\n", $output);
            if (
                strpos($full, "|\n M") !== false
                || strpos($full, "|\nM") !== false
                || strpos($full, "|\n D") !== false
                || strpos($full, "|\nD") !== false
            ) {
                $modified = true;
            }
        }
        // ## master...origin/master
        $output = reset($output);
        if (strpos($output, '...') !== false) {
            $branche = trim(substr($output, 2));
            $branche = explode('...', $branche);
            $branche = reset($branche);
        }

        // qu'on soit sur une branche ou non, on veut la revision courante
        exec('git log -1', $output);
        $hash = explode(' ', reset($output));
        $hash = end($hash);

        chdir($curdir);

        if (isset($options['format']) && $options['format'] == 'assoc') {
            $res = [
                'source' => $source,
                'dest' => $dest,
                'modified' => $modified,
            ];
            if ($branche) {
                $res['branche'] = $branche;
            }
            if ($hash) {
                $res['revision'] = $hash;
            }

            return $res;
        }

        $opt = '';
        if ($hash) {
            $opt .= ' -r ' . substr($hash, 0, 7);
        }
        if ($branche) {
            $opt .= " -b {$branche}";
        }
        if ($this->dest != '.') {
            $opt .= " -d {$dest}";
        }

        return "spip dl git{$opt} $source";
    }

    /**
     * @return array
     */
    public function git_get_remotes($dir_repo)
    {
        // recuperer les remote (fetch) du dossier
        $ouput = [];
        exec("cd $dir_repo && git remote -v", $output);
        $remotes = [];
        foreach ($output as $o) {
            if (preg_match(",(\w+://.*|\w+@[\w\.-]+:.*)\s+\(fetch\)$,Uis", $o, $m)) {
                $o = preg_replace(",\s+,", ' ', $o);
                $o = explode(' ', $o);
                $remote_name = array_shift($o);
                $remote_url = array_shift($o);

                $remotes[$remote_name] = $remote_url;
            }
        }
        return $remotes;
    }

    /**
     * récupérer les URLs des mirroirs
     *
     * @param string $url_source
     **/
    public function git_get_urls_mirrors($url_source)
    {
        $url_mirrors = [];
        foreach ($this->git_mirrors as $url_git => $mirrors) {
            // si on a un mirroir connu pour cette source, on verifie les remotes
            if (strpos($url_source, (string) $url_git) === 0) {
                foreach ($mirrors as $mirror) {
                    $url_mirrors[] = $mirror . substr($url_source, strlen($url_git));
                }
            }
        }
        return $url_mirrors;
    }

    /**
     * ajouter les mirroirs en remote
     *
     **/
    public function git_check_mirrors(&$input, &$output)
    {
        if ($url_mirrors = $this->git_get_urls_mirrors($this->source)) {
            $remotes = $this->git_get_remotes($this->dir_repo);
            $remote_name = 'mirror';
            $remote_cpt = '';
            foreach ($url_mirrors as $url_mirror) {
                if (!in_array($url_mirror, $remotes)) {
                    // on ajoute le mirroir en remote
                    while (!empty($remotes[$remote_name . $remote_cpt])) {
                        $remote_cpt = intval($remote_cpt) + 1;
                    }
                    $command = "git remote add {$remote_name}{$remote_cpt} $url_mirror";
                    $this->io->info($command);
                    passthru("cd $this->dir_repo && $command");
                }
            }
        }
    }

    /**
     * Loger les modifs d'une source, optionnellement entre 2 revisions
     *
     * @param array $options
     *   from : revision de depart
     *   to : revision de fin
     * @return string
     */
    public function git_log($options)
    {
        if (!is_dir("$this->dest/.git")) {
            return '';
        }

        $curdir = getcwd();
        chdir($this->dest);

        $r = '';
        if (isset($options['from']) || isset($options['to'])) {
            $from = '';
            $to = '';
            if (isset($options['from'])) {
                $from = $options['from'];
                $output = [];
                exec("git log -1 -c $from --pretty=tformat:'%ct'", $output);
                $t = intval(reset($output));
                if ($t) {
                    $from = "--since=$t $from";
                }
            }
            if (isset($options['to'])) {
                $to = $options['to'];
            }

            $r = " $from..$to";
        }

        $output = [];
        exec('git fetch --all 2>&1', $output);
        $output = [];
        $branche = '--all';
        if (isset($options['branche'])) {
            $branche = $options['branche'];
            if (strpos($branche, 'origin/') !== 0) {
                $branche = 'origin/' . $branche;
            }
        }
        $formatl = 'tformat:"%h | %an | %ae | %ct | %d %s "';
        $cmde = 'git log' . $r . ' --pretty=' . $formatl . ' ' . $branche;
        // windows ne supporte pas les ', " ou % dans les paramètres de exec() alors on utilise shell_exec
        //exec($cmde,$output);
        $output = shell_exec($cmde);
        $output = explode("\n", $output);
        $output = array_filter($output);
        foreach ($output as $k => $line) {
            $line = explode('|', ltrim($line, '*'));
            $revision = trim(array_shift($line));
            $comitter_name = trim(array_shift($line));
            $comitter_email = trim(array_shift($line));
            $comitter = ($comitter_email ?: $comitter_name);
            $date = trim(array_shift($line));
            $date = date('Y-m-d H:i:s', intval($date));
            $comm = trim(implode('|', $line));
            if (strlen($comm) > self::_MAX_LOG_LENGTH) {
                $comm = substr($comm, 0, self::_MAX_LOG_LENGTH) . '...';
            }
            $output[$k] = "$revision | $comitter | $date | $comm";
        }
        $output = implode("\n", $output);

        chdir($curdir);

        return trim($output);
    }

    /**
     * FTP
     */

    /**
     * Pas de notion de revision en FTP, donc c'est l'url qui fait foi
     * si on a la bonne URL source, on ne met pas a jour
     * et on met a jour quand l'url change
     * @return string
     */
    public function ftp_checkout(&$input, &$output)
    {
        $checkout_needed = false;

        // gérer la source par défaut de la méthode ftp
        if ($this->source === '?') {
            $url_depot_base = 'https://files.spip.net/spip/stable/';
            $zip_stable = 'spip-' . $this->branche . '.zip';
            $this->source = $url_depot_base . $zip_stable;
        }
        if (is_dir($this->dest) && count(scandir($this->dest)) > 2) {
            $infos = $this->ftp_info($this->dest, ['format' => 'assoc']);
            if (!$infos) {
                $this->erreur = "$this->dest n'est pas un download FTP et n\'est pas vide";
                $this->dir = $this->dest;
                $this->erreur_repertoire_existant($input, $output);
                $checkout_needed = true;
            } elseif ($infos['source'] !== $this->source) {
                $this->erreur = "$this->dest n'est pas un download de $this->source et n\'est pas vide";
                $this->dir = $this->dest;
                $this->erreur_repertoire_existant($input, $output);
                $checkout_needed = true;
            }
        } else {
            $checkout_needed = true;
        }
        clearstatcache();

        if ($checkout_needed) {
            $dest_co = $this->dest;

            //$dest_co = rtrim($dest_co, '/');
            if ($dest_co !== '.') {
                while (is_dir($dest_co)) {
                    $dest_co .= '_';
                }
                mkdir($dest_co, '0777', true);
            }
            $d = $dest_co . '/' . md5(basename($dest_co)) . '.tmp';

            // recuperer le fichier
            $command = "curl --silent -L \"$this->source\" > $d";
            $this->io->info($command);
            passthru($command);

            if (!file_exists($d) || !filesize($d)) {
                // essayer wget si curl foire
                $command = "wget $this->source -O $d";
                $this->io->info($command);
                passthru($command);
            }
            if (!file_exists($d)) {
                return "Echec $command";
            }

            // installer le zip
            $md5 = md5_file($d);
            if (!isset($options['format'])) {
                $options['format'] = 'zip';
            }
            switch ($options['format']) {
                case 'zip':
                default:
                    $tempdir = "{$d}d";
                    $command = "unzip -o $d -d $tempdir";
                    $this->io->info($command);
                    passthru($command);
                    $deplace = $tempdir . '/*';
                    $sous = glob($deplace);
                    if (
                        count($sous) == 1
                        && ($sd = reset($sous))
                        && is_dir($sd)
                    ) {
                        $deplace = $sd;
                    }
                    $command = "mv $deplace $dest_co";
                    $this->io->info($command);
                    passthru($command);
                    if (is_dir($tempdir)) {
                        $command = "rm -rf $tempdir";
                        $this->io->info($command);
                        passthru($command);
                    }
                    passthru("rm $d");

                    break;
            }

            // faire le ménage
            if ($dest_co !== $this->dest) {
                $command = "mv $this->dest {$dest_co}_OLD && mv $dest_co $this->dest";
                // faut il effacer le répertoire original ?
                $question = "Le répertoire existant $this->dest a été renommé en {$dest_co}_OLD. Souhaitez vous le supprimer ? (y / n)";
                if (isset($this->options['force_rm']) || $this->io->confirm($question, false)) {
                    $command .= " && rm -fR {$dest_co}_OLD";
                }
                $this->io->info($command);
                passthru($command);
            }
        }

        if (is_dir($this->dest) && isset($md5)) {
            file_put_contents("$this->dest/.ftpsource", "$this->source\n$md5");
        }

        $command = $this->ftp_info($this->dest, []);
        $this->io->info("OK $command");
    }

    /**
     * Lire source et révision d'un répertoire FTP et reconstruire la ligne de commande
     *
     * @param string $dest
     * @param array $options
     * 		format : texte|assoc : format du retour, par défaut la ligne de commande mais on peut mettre "assoc" pour avoir un tableau associatif des informations
     *
     * @return string|array
     * 		Retourne la ligne de commande ou un tableau des informations
     */
    public function ftp_info($dest, $options)
    {
        if (!file_exists($f = "{$dest}/.ftpsource")) {
            return '';
        }

        $source = file_get_contents($f);
        $source = explode("\n", $source);

        $md5 = end($source);
        $source = reset($source);

        if (isset($options['format']) && $options['format'] == 'assoc') {
            return [
                'source' => $source,
                'revision' => $md5,
                'dest' => $dest,
            ];
        }

        return "spip dl ftp -d {$dest} $source";
    }

    /**
     * S’il y a un require dans le composer.json, on fait une installation composer
     * permet de cibler SPIP 4.2+
     *
     * @param string $source
     * @param array $options
     */
    public function spip_run_composer_install($source, $options)
    {
        if (file_exists("$source/composer.json")) {
            $json = file_get_contents($source . '/composer.json');
            $json = json_decode($json, true);
            $requires = $json['require'] ?? [];
            $requires = array_filter(
                $requires,
                fn($req) => $req !== 'php' && substr($req, 0, 4) !== 'ext-',
                ARRAY_FILTER_USE_KEY,
            );
            if ($requires) {
                $composer = $this->spip_find_composer($source);
                if ($composer) {
                    $nodev = (isset($options['composer-no-dev']) && $options['composer-no-dev']) ? '--no-dev' : '';
                    $command = "cd $source && $composer install  $nodev"; //
                    passthru($command, $retour);
                    if ($retour === 1) {
                        // le lancement de composer a planté : on tente de le relancer sans le chemin complet en comptant sur le $PATH
                        // (cf Windows qui s'emmèle les pinceaux entre les \ et / dans les chemins lorsque spip-cli est exécuté en Gitbash...)
                        $command = "cd $source && composer install  $nodev"; //
                        passthru($command, $retour);
                    }
                } else {
                    $this->io->error("Composer n'a pas été trouvé : impossible de faire l'installation.");
                }
            }
        } else {
            $this->io->error(
                "Erreur : pas de fichier composer.json dans $source : vérifiez que le téléchargement des fichiers est OK et terminez votre installation en exécutant 'composer install'",
            );
        }
    }

    public function spip_find_composer($source)
    {
        if (file_exists($source . '/composer.phar')) {
            return PHP_BINARY . ' composer.phar';
        }
        exec('which composer', $output, $res);
        if ($res === 0) {
            return trim(implode($output));
        }
        return null;
    }

    public function cli_supprimer_repertoire($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir) || is_link($dir)) {
            return @unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->cli_supprimer_repertoire($dir . '/' . $item)) {
                @chmod($dir . '/' . $item, 0777);
                if (!$this->cli_supprimer_repertoire($dir . '/' . $item)) {
                    return false;
                }
            }
        }
        return @rmdir($dir);
    }

    protected function configure(): void
    {
        $this
            ->setName('core:telecharger')
            ->setDescription('Télécharger SPIP dans un dossier (par défaut, la dernière version stable)')
            ->setHelp(
                'Quelques exemples :

<info>spip dl</info> : télécharge (ou met à jour) SPIP branche stable en Git via HTTP (uniquement lecture) dans le dossier courant
<info>spip dl -b 3.1 -d autre/chemin</info> : télécharge SPIP branche 3.1 en Git via HTTP dans le dossier autre/chemin

<info>spip dl spip git@git.spip.net -d autre/chemin</info> : télécharge (ou met à jour) SPIP branche stable en Git via SSH (pour les devs) dans le dossier autre/chemin
<info>spip dl spip git@git.spip.net -b 3.1</info> : télécharge SPIP branche 3.1 en Git via SSH dans le dossier courant

<info>spip dl git https://url_depot</info> : télécharge (ou met à jour) n’importe quel dépôt Git dans le dossier courant
<info>spip dl git -r abcd1234 https://url_depot autre/chemin</info> : télécharge un dépôt Git à une révision précise

<info>spip dl svn -r 1234 svn://url_depot</info> : télécharge un dépôt SVN à une révision précise dans le dossier courant

<info>spip dl ftp</info> : télécharge en mode FTP et décompacte la version stable dans le dossier courant
<info>spip dl ftp -b 3.1 -d autre/chemin</info> : télécharge en mode FTP et décompacte la version 3.1 dans le dossier autre/chemin
<info>spip dl ftp https://url_depot/url_fichier.zip</info> : télécharge en mode FTP et décompacte n’importe quel fichier zip dans le dossier courant

<info>spip dl -i</info> : récuperer la commande "spip dl ..." correspondant a une installation deja en place dans le dossier courant
<info>spip dl -l dest</info> : voir les logs des mises a jour disponibles pour le repertoire dest',
            )
            ->addArgument(
                'methode',
                InputArgument::OPTIONAL,
                'Méthode de téléchargement, pouvant être "spip" pour avoir la distribution, sinon "git", "svn", ou "ftp".',
                'spip',
            )
            ->addArgument('source', InputArgument::OPTIONAL, 'URL du dépôt à télécharger', '?')
            ->addOption('dest', 'd', InputOption::VALUE_OPTIONAL, 'Répertoire où télécharger', '.')
            ->addOption(
                'branche',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Donner explicitement la branche ou le tag à télécharger',
                // par défaut = branche stable en cours
            )
            ->addOption(
                'release',
                'R',
                InputOption::VALUE_OPTIONAL,
                'Donner explicitement la release à télécharger ou bien la branche où chercher la dernière release (X.Y.Z ou X.Y ou X)',
            )
            ->addOption('revision', 'r', InputOption::VALUE_OPTIONAL, 'Pointer sur une révision donnée')
            ->addOption('info', 'i', InputOption::VALUE_OPTIONAL, 'Lire les informations du répertoire')
            ->addOption('logupdate', 'l', InputOption::VALUE_OPTIONAL, 'Afficher le log des commits à mettre à jour')
            ->addOption(
                'options',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Ajouter des options supplémentaires à la commande finale (si plusieurs : entre quotes, séparées par un espace)
				Options disponibles :
					. force_rm = autorise la suppression du répertoire dest si l\'installation en place ne correspond pas à la source demandée.
					(ne fonctionne pas si la destination est le répertoire en cours)',
            )
            ->addOption(
                'dev',
                'D',
                InputOption::VALUE_OPTIONAL,
                'SPIP 5 : Composer : installation en mode dev (inclus les dépendance de require-dev)',
            )
            ->setAliases([
                'dl', // abbréviation commune pour "download"
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // On teste si on peut utiliser "passthru"
        if (!function_exists('passthru')) {
            $output->writeln(
                '<error>Votre installation de PHP doit pouvoir exécuter des commandes externes avec la fonction passthru().</error>',
            );
            exit(Command::FAILURE);
        }

        // Quelle méthode ?
        if ($methode = $input->getArgument('methode')) {
            // si le seul argument est git@git.spip.net considérer qu'on est en méthode spip et le passer en source
            if ($methode === 'git@git.spip.net') {
                $methode = 'spip';
                $source = 'git@git.spip.net';
            }
            if (in_array($methode, ['spip', 'git', 'svn', 'ftp'])) {
                $this->methode = $methode;
            } else {
                $output->writeln(
                    "<error>La méthode de téléchargement $methode n’est pas reconnue : spip | git | svn | ftp</error>",
                );
                exit(Command::FAILURE);
            }
        }

        // Quel dossier final ? Par défaut le dossier courant .
        if ($dest = $input->getOption('dest')) {
            $this->dest = rtrim($dest, '/');
            $racine = $this->dest;
        }

        //La source
        $this->source = $input->getArgument('source');
        if (isset($source) && $this->source === '?') {
            $this->source = $source;
        }
        $source_rep = $this->recup_info($input, $output, 'source');
        $domaine_source_rep = explode(':', $source_rep)[0];
        if ($source_rep) {
            if (in_array($this->source, ['?', '', null])) {
                $this->source = $source_rep;
            } elseif ($this->source !== $source_rep && $domaine_source_rep !== $this->source) {
                $question = "La source du répertoire de destination ($source_rep) est différente de la source choisie ($this->source) : souhaitez vous continuer ? ";
                if (!isset($this->options['force_rm']) && !$this->io->confirm($question, false)) {
                    exit(Command::FAILURE);
                }
            }
        }

        // La branche
        $this->branche = $input->getOption('branche');
        $branche_rep = $this->recup_info($input, $output, 'branche');
        if ($branche_rep) {
            if (in_array($this->branche, ['', null])) {
                $this->branche = $branche_rep;
            } elseif ($this->branche !== $branche_rep) {
                $question = "La branche du répertoire de destination ($branche_rep) est différente de la branche choisie ($this->branche) : souhaitez vous continuer ?";
                if (!isset($this->options['force_rm']) && !$this->io->confirm($question, false)) {
                    $this->io->info('FAIL');
                    exit(Command::FAILURE);
                }
            }
        }
        if (in_array($this->branche, ['', null])) {
            $this->branche = self::_BRANCHE_STABLE;
        }

        // La révision (uniquement pour les méthodes git et svn)
        if (in_array($methode, ['git', 'svn'])) {
            $this->revision = $input->getOption('revision');
        }

        // Quelles options ?
        if ($options = $input->getOption('options')) {
            $options = explode(' ', $options);
            foreach ($options as $option) {
                $this->options[$option] = true;
            }
        }

        // Quiet ?
        $this->quiet = $input->getOption('quiet');

        // mode dev ?
        if ($input->hasParameterOption(['--dev', '-D'])) {
            $this->mode_dev = true;
        }

        // Si on cherche juste à lire les infos
        if ($input->hasParameterOption(['--info', '-i'])) {
            $this->lire_info($input, $output);
        }
        // Si on cherche juste à avoir les logs des choses à mettre à jour
        elseif ($input->hasParameterOption(['--logupdate', '-l'])) {
            $this->logupdate($input, $output);
        }

        // Sinon c'est pour un téléchargmement ou une mise à jour
        else {
            $this->io->info("Depart checkout pour la branche $this->branche");
            $this->checkout($input, $output);
        }

        // en spip 4.2+ il faut faire un composer install
        $file_composer = 'composer.json';
        if ($this->branche !== 'master') {
            [$x_version, $y_version] = explode('.', $this->branche);
        }
        if (($this->branche === 'master' || ($x_version > 3 && $y_version > 1))
            && file_exists("$racine/$file_composer")) {
            $this->io->info("Depart composer install pour $this->branche");

            $no_dev = $this->mode_dev ? [] : ['composer-no-dev' => 1];
            $this->spip_run_composer_install($racine, $no_dev);
        }

        // en spip 5+ supprimer tous les éventuels plugins-dist qui resteraient à la racine de /plugins-dist
        // ...et qui existent aussi dans plugins-dist/spip
        if ($this->branche === 'master' || version_compare($this->branche, '4.2.99') > 0) {
            $this->io->info('Départ nettoyage plugins-dist de la 4.2');
            if (file_exists("$racine/plugins-dist") && is_dir("$racine/plugins-dist")
                && file_exists("$racine/plugins-dist/spip") && is_dir("$racine/plugins-dist/spip")) {
                $corresp_reps = [
                    'filtres_images' => 'images',
                    'porte_plume' => 'porte-plume',
                    'statistiques' => 'stats',
                    'textwheel' => 'tw',
                    'urls_etendues' => 'urls',
                ];
                $dir = opendir("$racine/plugins-dist");
                while (false !== ($file = readdir($dir))) {
                    if (($file != 'spip' && $file != '.') && ($file != '..')) {
                        $chem_old = "$racine/plugins-dist/$file";
                        $file_composer = array_key_exists($file, $corresp_reps) ? $corresp_reps[$file] : $file;
                        $chem_composer = "$racine/plugins-dist/spip/$file_composer";
                        if (is_dir($chem_old) && file_exists($chem_composer) && is_dir($chem_composer)) {
                            $this->io->info("Supprimer $chem_old");
                            $this->cli_supprimer_repertoire($chem_old);
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * récupérer les infos du répertoire choisi
     *
     * @param string $option : l'info à retourner (tout l'array si *)
     * @return string|array|false
     */
    protected function recup_info(&$input, &$output, $option = '*')
    {
        $type_info = '*';
        if (in_array($option, ['source', 'dest', 'modified', 'branche', 'revision'])) {
            $type_info = $option;
        }
        $methodes = ['git', 'svn', 'ftp'];
        foreach ($methodes as $methode) {
            if (
                method_exists($this, $fonction_info = $methode . '_info')
                && ($info = $this->{$fonction_info}($this->dest, ['format' => 'assoc']))
            ) {
                return $type_info === '*' ? $info : $info[$type_info];
            }
        }

        return false;
    }

    /**
     * Lire les infos du répertoire choisi
     */
    protected function lire_info(&$input, &$output)
    {
        $methodes = ['git', 'svn', 'ftp'];
        foreach ($methodes as $methode) {
            if (
                method_exists($this, $fonction_info = $methode . '_info')
                && ($info = $this->{$fonction_info}($this->dest, ['format' => 'texte']))
            ) {
                $this->io->info($info);
                // On s'arrête
                return;
            }
        }
        $this->io->error("Impossible de trouver la source du répertoire {$this->dest}");
    }

    /**
     * Lire source et révision d'un répertoire SVN et reconstruire la ligne de commande
     *
     * @param array $options
     * @return string|array
     * 		Retourne la ligne de commande ou un tableau des informations
     */
    protected function svn_info($dest, $options)
    {
        if (!is_dir("{$dest}/.svn")) {
            return '';
        }

        // on veut lire ce qui est actuellement déployé
        // et reconstituer la ligne de commande pour le déployer
        exec("svn info {$dest}", $output);
        $output = implode("\n", $output);

        // URL
        // URL: svn://trac.rezo.net/spip/spip
        if (!preg_match(',^URL[^:\w]*:\s+(.*)$,Uims', $output, $match)) {
            return '';
        }
        $source = $match[1];

        // Revision
        // Revision: 18763
        if (!preg_match(',^R..?vision[^:\w]*:\s+(\d+)$,Uims', $output, $match)) {
            return '';
        }
        $revision = $match[1];

        if ($options['format'] == 'assoc') {
            return [
                'source' => $source,
                'revision' => $revision,
                'dest' => $dest,
            ];
        }
        return "spip dl svn -r $revision -d {$dest} $source";
    }
}
