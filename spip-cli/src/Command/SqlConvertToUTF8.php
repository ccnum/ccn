<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlConvertToUTF8 extends Command
{
    protected function configure(): void
    {
        $this->setName('sql:convert:toutf8')
            ->setDescription('Convertit une base en UTF8 (utile pour un site en mysql)')
            ->setHelp('
- on verifie que ENGINE=MYISAM et on corrige si besoin
- la collation est passee en utf8
- les champs iso sont modifies en utf8 en conservant leur contenu sans conversion (on suppose que les contenus sont en utf8 dans une base en iso, ce qui est le cas general dans les vieux SPIP)')
            ->addOption(
                'convert',
                null,
                InputOption::VALUE_NONE,
                'Pour forcer la conversion de charset des contenus (contenus encodes en iso dans une base iso)',
                null,
            )
            ->addOption(
                'exceptions',
                null,
                InputOption::VALUE_OPTIONAL,
                'Pour traiter certains cas particuliers de tables --exceptions=spip_forum ou de champs --exceptions=spip_forum.texte,spip_breves.texte
Pour ces champs on applique l\'inverse de l\'option convert',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $this->io->title('Convertir en UTF8');

        $tables = sql_alltable('%'); // charger la connection mysql
        if ($GLOBALS['connexions'][0]['type'] !== 'mysql') {
            $this->io->error('Ce script est réservé aux installations utilisant mySQL');
            return Command::FAILURE;
        }

        $convert = !!$input->getOption('convert');

        // le passage latin=>utf8 des champs se fait en general sans conversion du contenu
        // car SPIP stocke deja du contenu UTF dans des tables latin1
        // toutefois si certains champs ont besoin d'une conversion SQL on les passes dans
        // $exceptions['table'][] = 'champ' pour convertir un champ
        // $exceptions['table'][] = '*' pour convertir tous les champs de la table
        $exceptions = [];
        $exceptions_option = $input->getOption('exceptions');
        if ($exceptions_option) {
            $exceptions_option = explode(',', $exceptions_option);
            foreach ($exceptions_option as $e) {
                $e = explode('.', $e, 2);
                $table = reset($e);
                if (!isset($exceptions[$table])) {
                    $exceptions[$table] = [];
                }
                if (count($e) === 1) {
                    $exceptions[$table][] = '*';
                } else {
                    $champ = end($e);
                    $exceptions[$table][] = $champ;
                }
            }
        }

        // convertir l'engine en myisam d'abord
        $this->sqlConvertEngine();

        // puis le charset
        $this->sqlConvertCharset($convert, $exceptions);

        ecrire_meta('charset_sql_connexion', 'utf8');
        ecrire_meta('charset', 'utf-8');

        $this->io->success('Fini');
        return Command::SUCCESS;
    }

    protected function sqlConvertEngine()
    {

        $this->io->section('Vérification du Engine MySQL');

        $trouver_table = charger_fonction('trouver_table', 'base');
        $trouver_table('');

        $tables = sql_alltable('%');
        $this->io->text(count($tables) . ' tables');

        foreach ($tables as $table) {
            $ligne = "$table";
            $s = spip_mysql_query("SHOW CREATE TABLE `$table`");
            if (intval(10 * floatval($GLOBALS['spip_version_branche'])) > 30) {
                [, $a] = mysqli_fetch_array($s, MYSQLI_NUM);
            } else {
                [, $a] = mysql_fetch_array($s, MYSQLI_NUM);
            }
            if (strpos($a, 'ENGINE=MyISAM') === false || strpos($a, 'DEFAULT CHARSET=latin1') !== false) {
                sql_alter($q = "TABLE $table ENGINE = MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
                spip_log("ALTER $q", 'maj_utf');
                $ligne .= " : ALTER $table ENGINE = MYISAM ";
                $this->io->text("$ligne");
            } else {
                $this->io->text("$ligne OK");
            }
        }

        $this->io->success('Engine MYISAM + DEFAULT CHARACTER SET utf8 OK');

    }

    protected function sqlConvertCharset($convert, $exceptions = [])
    {
        ecrire_meta('charset_sql_connexion', 'utf8');

        $this->io->section('Charset des champs de chaque table');

        $trouver_table = charger_fonction('trouver_table', 'base');
        $trouver_table('');
        $tables = sql_alltable('%');

        $this->io->text(count($tables) . ' tables');
        foreach ($tables as $table) {
            $this->io->section("Table $table");

            $desc = $trouver_table($table);
            $exception_champs = ($exceptions[$table] ?? []);
            $tochange = [];
            foreach ($desc['field'] as $field => $d) {
                if (strpos($d, 'latin1') !== false && !in_array($field, ['login', 'spip_listes_format'])) {
                    $tochange[$field] = $d;
                }
            }

            if ($tochange) {
                $nbtochange = count($tochange);
                $this->io->text("$nbtochange à modifier : " . implode(', ', array_keys($tochange)));

                $fulltext_indexes = $this->sqlConvertGetFulltextIndex($desc);
                // supprimer les index fulltext
                if ($fulltext_indexes) {
                    spip_log(
                        $s = 'Suppression des index Fulltext : ' . implode(', ', array_keys($fulltext_indexes)),
                        'maj_utf',
                    );
                    $this->io->text($s);
                    foreach ($fulltext_indexes as $key => $alter) {
                        sql_alter("TABLE $table DROP INDEX $key");
                    }
                }

                // changer les champs
                foreach ($tochange as $field => $d) {
                    $this->sqlConvertCharsetField($convert, $table, $field, $d, $exception_champs);
                }

                // remettre les index fulltext
                if ($fulltext_indexes) {
                    spip_log(
                        $s = 'Remettre les index Fulltext : ' . implode(', ', array_keys($fulltext_indexes)),
                        'maj_utf',
                    );
                    $this->io->text($s);
                    foreach ($fulltext_indexes as $key => $alter) {
                        sql_alter("$alter");
                    }
                }
            } else {
                $this->io->text('OK, Rien à faire');
            }

        }
    }

    protected function sqlConvertCharsetField($convert, $table, $field, $d, $exception_champs)
    {

        $converted = true;
        if (strpos($d, 'COLLATE') !== false && strpos($d, 'latin1_bin') !== false) {
            $dutf = str_replace('latin1', 'utf8', $d);
            sql_alter($q = "TABLE $table change $field $field $dutf");
            spip_log("ALTER $q", 'maj_utf');
        } else {
            if (strpos($d, 'COLLATE') !== false) {
                $d = preg_replace(",COLLATE\s+\w+\s,i", '', $d);
            }

            #if (strpos($d,"NOT NULL")!==false and strpos($d,"DEFAULT") === false) {
            #	$d .= ' DEFAULT \'\'';
            #}

            // on passe par un format binaire pour empecher toute conversion de conversion par mysql
            // sinon le passage direct latin1=>utf8 entreune une conversion de contenu
            // si certains champs ont besoin d'une conversion SQL on les passes dans
            // $exceptions['table']['champ'] pour convertir un champ
            // $exceptions['table']['*'] pour convertir tous les champs de la table
            if (
                $convert && (in_array($field, $exception_champs) || in_array('*', $exception_champs))
                || !$convert && !in_array($field, $exception_champs) && !in_array('*', $exception_champs)
            ) {
                $dbin = str_replace('latin1', 'binary', $d);
                sql_alter($q = "TABLE $table change $field $field $dbin");
                #echo $q . "\n";
                spip_log("ALTER $q", 'maj_utf');
                $converted = false;
            }
            $dutf = str_replace('latin1', 'utf8 COLLATE utf8_general_ci', $d);
            sql_alter($q = "TABLE $table change $field $field $dutf");
            #echo $q . "\n";
            spip_log("ALTER $q", 'maj_utf');
        }

        $this->io->text("$field : $d  =>" . ($converted ? ' (CONVERTED) ' : '') . " $dutf");
    }

    protected function sqlConvertGetFulltextIndex($desc)
    {
        $indexes = [];
        foreach ($desc['key'] as $key => $d) {
            if (strpos($key, 'FULLTEXT ') === 0) {
                $key = explode(' ', trim(substr($key, strlen('FULLTEXT '))));
                $key = end($key);
                $indexes[$key] = 'TABLE ' . $desc['table'] . " ADD FULLTEXT $key ($d)";
            }
        }
        return $indexes;
    }
}
