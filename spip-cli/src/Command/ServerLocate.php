<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerLocate extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('server:locate')
            ->setDescription('Localiser les SPIP installés sur ce serveur')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $sites = $this->locate('inc_version.php');

        if (!count($sites)) {
            $this->io->error('Pas de site SPIP détecté.');
            exit(Command::FAILURE);
        }

        foreach ($sites as $site) {
            $msg = $this->analyser_site($site);
            if ($msg) {
                $this->io->writeln($msg);
            }
        }
        return Command::SUCCESS;
    }

    /**
     * Cherche les fichiers nommés xxxx
     *
     * @return array liste des fichiers nommés xxxx
     */
    protected function locate($filename)
    {
        $_filename = escapeshellarg($filename);
        $files = shell_exec("locate $_filename 2>/dev/null")
         . shell_exec("mdfind -name $_filename 2>/dev/null");
        $files = explode("\n", trim($files));
        $files = array_filter($files, fn($x) => preg_match(",/inc_version\.php[3]?$,", $x));

        return array_unique($files);
    }

    protected function analyser_site($filename)
    {
        $inc_version = basename($filename);
        $ecrire = basename(dirname($filename));
        $rep = dirname($filename, 2);
        $name = basename($rep);
        // si le nom est peu informatif, remonter encore d'un cran
        if (in_array(
            strtolower($name),
            ['www', 'dev', 'old', '_old', 'maj', '_maj', 'site', 'web', 'spip', 'public_html'],
        )) {
            $name = basename(dirname($rep)) . '/' . $name;
        }

        $a = @file_get_contents($filename);

        if (
            preg_match('/.*spip_version_branche\s*=\s*[\'"](.*)[\'"];/', $a, $r)
            || preg_match('/.*spip_version_affichee\s*=\s*[\'"](.*)[\'"];/', $a, $r)
        ) {
            $version_spip = $r[1];
        } else {
            $version_spip = '<error>version?</error>';
        }

        $report = [
            'Version' => $version_spip,
            'Répertoire' => $rep,
        ];

        // Regarder les bases de données connectées, etc, etc.
        // TODO

        // Données à afficher
        $aff = "<info>$name</info> ($version_spip)\n";
        foreach ($report as $key => $val) {
            $aff .= "  $key: $val\n";
        }

        return $aff;
    }
}
