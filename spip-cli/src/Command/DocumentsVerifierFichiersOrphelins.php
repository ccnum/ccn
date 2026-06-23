<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentsVerifierFichiersOrphelins extends Command
{
    public const DISPLAY_FILES_LIMIT = 20;

    protected $dataDirectory;

    protected $exclude_directories = ['cache-centre-image', 'distant', 'pdf_version'];

    protected $exclude_files = ['.ok', '.htaccess', '.DS_Store', 'remove.txt'];

    protected function configure(): void
    {
        $this
            ->setName('documents:verifier:fichiers_orphelins')
            ->setDescription('Lister les fichiers non connus de spip_documents')
            ->addOption('export', null, InputOption::VALUE_OPTIONAL, 'Exporter la liste dans un fichier (txt ou php)')
            ->addOption(
                'exclude_directories',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Compléter des noms de répertoires à ignorer',
                [],
            )
            ->addOption(
                'exclude_files',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Compléter des noms de fichiers à ignorer',
                [],
            )
            ->addUsage('')
            ->addUsage('--export=missings.php')
            ->addUsage('--export=missings.txt')
            ->addUsage('--exclude_directories=attestations')
            ->addUsage('--exclude_files=.thumbnails --exclude_files=.htpasswd')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->demarrerSpip();
        $this->io->title('Analyse des fichiers orphelins du répertoire IMG');
        $this->setDataDirectory(_DIR_IMG);

        $this->addExcludeDirectories($input->getOption('exclude_directories'));
        $this->addExcludeFiles($input->getOption('exclude_files'));

        $missings = $this->analyser();
        if ($export = $input->getOption('export')) {
            $this->io->section(sprintf('Export vers <info>%s</info>', $export));
            $this->exportTo($missings, $export);
        }

        return Command::SUCCESS;
    }

    private function setDataDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            throw new \Exception("Répertoire $dir inexistant.");
        }
        $this->dataDirectory = $dir;
    }

    private function getDataDirectory()
    {
        return $this->dataDirectory;
    }

    private function addExcludeDirectories(array $directories): array
    {
        #$this->exclude_directories = [...$this->exclude_directories, ...$directories];
        $this->exclude_directories = array_merge($this->exclude_directories, $directories);
        return $this->exclude_directories;
    }

    private function getExcludeDirectories(): array
    {
        return $this->exclude_directories;
    }

    private function addExcludeFiles(array $files): array
    {
        #$this->exclude_files = [...$this->exclude_files, ...$files];
        $this->exclude_files = array_merge($this->exclude_files, $files);
        return $this->exclude_files;
    }

    private function getExcludeFiles(): array
    {
        return $this->exclude_files;
    }

    private function analyser(): array
    {
        $dirs = $this->directories();
        $all_missings = [];
        foreach ($dirs as $path => $dir) {
            $progressIndicator = new ProgressIndicator($this->io);
            $progressIndicator->start(sprintf('<info>%s</info> ', $this->getDataDirectory() . $path));
            $found = $this->checkDirectory($path);
            $progressIndicator->advance();
            $missings = $this->compareDirectory($path, $progressIndicator);
            $progressIndicator->advance();
            $progressIndicator->finish(sprintf(
                '%s <info>%s</info> <comment>%s</comment>',
                $missings ? '❌' : '✅',
                $this->getDataDirectory() . $path,
                $found ? '' : '(Aucuns en BDD)',
            ));
            if ($missings) {
                foreach (array_slice($missings, 0, static::DISPLAY_FILES_LIMIT) as $missing) {
                    $this->io->writeln(sprintf('      %s <info>%s</info> ', '❌', $missing));
                }
                if (count($missings) > static::DISPLAY_FILES_LIMIT) {
                    $this->io->writeln(
                        sprintf(
                            '      <comment>... et %s autres fichiers</comment> ',
                            count($missings) - static::DISPLAY_FILES_LIMIT,
                        ),
                    );
                }
                #$all_missings = [...$all_missings, ...$missings];
                $all_missings = array_merge($all_missings, $missings);
            }
        }
        return $all_missings;
    }

    private function directories(): array
    {

        $dir = new \RecursiveDirectoryIterator($this->getDataDirectory(), \RecursiveDirectoryIterator::SKIP_DOTS);
        $cut_length = strlen($this->getDataDirectory());

        $filterIterator = new \RecursiveCallbackFilterIterator($dir, function ($current, $key, $iterator) use (
            $cut_length
        ) {
            if (!$current->isDir()) {
                return false;
            }
            $path = substr($current->getPathname(), $cut_length);
            if (in_array($path, $this->getExcludeDirectories())) {
                return false;
            }

            return $this->checkDirectoryHasDocumentFiles($current->getPathname());
        });

        $dirs = [];
        if ($this->checkDirectoryHasDocumentFiles($this->getDataDirectory())) {
            $dirs[''] = new \SplFileInfo($this->getDataDirectory());
        }

        $dirIterator = new \RecursiveIteratorIterator($filterIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($dirIterator as $file) {
            $path = substr($file->getPathname(), $cut_length);
            $dirs[$path] = $file;
        }

        return $dirs;
    }

    /**
     * Vérifier s’il y a des **fichiers** intéressants dans un répertoire
     */
    private function checkDirectoryHasDocumentFiles(string $directory): bool
    {
        $files = new \FilesystemIterator($directory);
        foreach ($files as $file) {
            if ($file->isFile() && !in_array($file->getFilename(), $this->exclude_files)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Y a t’il un document en base pour ce répertoire
     */
    private function checkDirectory(string $path): bool
    {
        $exp = sprintf('fichier REGEXP "^%s/[^/]+"', $path);
        $doc = sql_getfetsel('id_document', 'spip_documents', $exp, '', '', '0,1');
        return (bool) $doc;
    }

    private function compareDirectory(string $path, ?ProgressIndicator $progressIndicator = null): array
    {
        $inDir = $this->listDirectoryFiles($path);
        if ($progressIndicator) {
            $progressIndicator->advance();
        }

        $inBdd = $this->listBddFiles($path);
        if ($progressIndicator) {
            $progressIndicator->advance();
        }

        return array_diff($inDir, $inBdd);
    }

    private function listDirectoryFiles(string $path): array
    {
        $list = [];
        $files = new \FilesystemIterator($this->getDataDirectory() . $path);
        foreach ($files as $file) {
            if ($file->isFile() && !in_array($file->getFilename(), $this->exclude_files)) {
                $list[] = $file->getPathname();
            }
        }
        return $list;
    }

    private function listBddFiles(string $path): array
    {
        $exp = sprintf('fichier REGEXP "^%s/[^/]+"', $path);
        $docs = sql_allfetsel('fichier', 'spip_documents', $exp);
        $docs = array_column($docs, 'fichier');
        $docs = array_map(fn($i) => $this->getDataDirectory() . $i, $docs);
        return $docs;
    }

    private function exportTo(array $missings, string $file)
    {
        if (!$file || !is_writable(dirname($file))) {
            throw new \RuntimeException(sprintf("Can’t write '%s' file", $file));
        }
        if (file_exists($file)) {
            if (is_writable($file)) {
                unlink($file);
            } else {
                throw new \RuntimeException(sprintf("Can’t remove '%s' existant file", $file));
            }
        }
        #if (str_ends_with($file, '.php')) {
        if (substr($file, -4) === '.php') {
            $list = array_map(fn($item) => "\t'$item',\n", $missings);
            $list = implode('', $list);
            $export = <<<EOS
				<?php
				return [
				$list];
				EOS;
        } else {
            $export = implode("\n", $missings);
        }
        file_put_contents($file, $export);
    }
}
