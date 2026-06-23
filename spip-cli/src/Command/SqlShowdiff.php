<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlShowdiff extends Command
{
    /**
     * Liste les tables et champs présents mais non déclarés
     */
    public function showdiff($ignorer_excedentaires = false, $check_default = false, $repair = false)
    {
        if ($ignorer_excedentaires) {
            $this->io->title('Liste des tables et champs déclarés mais manquants');
        } else {
            $this->io->title('Liste des tables et champs non déclarés ou déclarés mais manquants');
        }

        $tables = sql_alltable();
        $principales = lister_tables_principales();
        $auxiliaires = lister_tables_auxiliaires();
        $declarees = array_merge($principales, $auxiliaires);

        $this->io->text(count($declarees) . ' table·s déclarée·s');
        $this->io->text(count($tables) . ' table·s réelle·s');
        $this->io->text('');

        # Tables en trop
        if (!$ignorer_excedentaires) {
            $diff = array_diff($tables, array_keys($declarees));
            $this->printTables($diff, 'table·s non déclarée·s');
        }

        # Tables manquantes
        $diff = array_diff(array_keys($declarees), $tables);
        $this->printTables($diff, 'table·s déclarée·s mais absentes');

        $presentes = array_intersect_key($declarees, array_flip($tables));
        ksort($presentes);
        foreach ($presentes as $table => $desc) {
            $colonnes_declarees = $desc['field'];
            $colonnes = sql_showtable($table);
            $colonnes = $colonnes['field'];

            # Colonnes en trop
            if (!$ignorer_excedentaires) {
                $diff = array_diff_key($colonnes, $colonnes_declarees);
                $this->printColumns($diff, $table, 'colonne·s non déclarée·s');
            }

            #  Colonnes manquantes
            $diff = array_diff_key($colonnes_declarees, $colonnes);
            $this->printColumns($diff, $table, 'colonne·s déclarée·s mais absentes');
            if ($repair) {
                foreach ($diff as $champ => $type) {
                    $alter = "TABLE $table ADD $champ $type";
                    if ($this->io->confirm("ALTER $alter", false)) {
                        sql_alter($alter);
                        // vider le cache
                        $trouver_table = charger_fonction('trouver_table', 'base');
                        $trouver_table('');
                    }
                }
            }

            $struct_erreurs = [];
            if ($check_default) {
                // verifier les clauses default
                foreach ($colonnes as $champ => $type) {
                    if (!empty($colonnes_declarees[$champ])) {
                        if ((!!stripos($this->ignoreNotNullDefaultVide($type), 'DEFAULT')) !== (!!stripos(
                            $this->ignoreNotNullDefaultVide($colonnes_declarees[$champ]),
                            'DEFAULT',
                        ))) {
                            $struct_erreurs[] = ['column' => $champ, 'declaration' => $colonnes_declarees[$champ], 'base' => $type];
                        }
                    }
                }
            }
            if (!empty($struct_erreurs)) {
                $this->io->section(
                    'Table ' . $table . ' : ' . count(
                        $struct_erreurs,
                    ) . " champs dont la structure n'est pas celle attendue",
                );
                $this->io->atable($struct_erreurs);
                if ($repair) {
                    foreach ($struct_erreurs as $struct) {
                        $champ = $struct['column'];
                        $type = $struct['declaration'];
                        $alter = "TABLE $table CHANGE $champ $champ $type";
                        $this->io->care("$champ: " . $struct['base']);
                        if ($this->io->confirm("ALTER $alter", false)) {
                            sql_alter($alter);
                            // vider le cache
                            $trouver_table = charger_fonction('trouver_table', 'base');
                            $trouver_table('');
                        }
                    }
                }
            }
        }
    }

    public function printTables(array $diff, $texte)
    {
        if ($diff) {
            sort($diff);
            $this->io->section(count($diff) . " $texte");
            $this->io->listing($diff);
        }
    }

    public function printColumns(array $diff, $table, $texte)
    {
        if ($diff) {
            $this->io->section('Table ' . $table . ' : ' . count($diff) . " $texte");
            $rows = array_map(fn($k, $v) => ['column' => $k, 'description' => $v], array_keys($diff), $diff);
            $this->io->atable($rows);
        }
    }

    protected function configure(): void
    {
        $this->setName('sql:show:diff')
            ->setDescription('Liste les tables et champs présents mais non déclarés à SPIP, ou inversement.')
            ->addOption(
                'manquants',
                null,
                InputOption::VALUE_NONE,
                'Uniquement les tables et champs déclarés mais manquants',
            )
            ->addOption('default', null, InputOption::VALUE_NONE, 'Vérifier les clauses DEFAULT des champs communs')
            ->addOption(
                'repair',
                null,
                InputOption::VALUE_NONE,
                'Proposer de créer les champs manquants/repair les champs incorrects',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demarrerSpip();
        $ignorer_excedentaires = $input->getOption('manquants');
        $check_default = $input->getOption('default');
        $repair = $input->getOption('repair');
        $this->showdiff($ignorer_excedentaires, $check_default, $repair);
        return Command::SUCCESS;
    }

    protected function ignoreNotNullDefaultVide($type)
    {
        if (stripos($type, 'NOT NULL') === false) {
            $defaultvide = 'DEFAULT NULL';
            if (stripos($type, $defaultvide) !== false) {
                $type = str_ireplace($defaultvide, '', $type);
            }
        } else {
            $defaultvide = "DEFAULT ''";
            if (stripos($type, 'int') !== false) {
                $defaultvide = "DEFAULT '0'";
                if (stripos($type, 'DEFAULT 0') !== false) {
                    $type = str_ireplace('DEFAULT 0', $defaultvide, $type);
                }
            } elseif (stripos($type, 'timestamp') !== false) {
                $defaultvide = 'DEFAULT CURRENT_TIMESTAMP';
            }
            if (stripos($type, "$defaultvide NOT NULL") !== false || stripos(
                $type,
                "NOT NULL $defaultvide",
            ) !== false) {
                $type = str_ireplace($defaultvide, '', $type);
            }
        }
        return $type;
    }
}
