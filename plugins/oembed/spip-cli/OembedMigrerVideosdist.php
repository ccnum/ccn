<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class OembedMigrerVideosdist extends Command {
	protected function configure() {
		$this
			->setName('oembed:migrer:videos-dist')
			->setDescription('Migrer les documents videos \'dist_*\' issus du plugin videos en documents oembed')
			->addOption(
				'id_document',
				null,
				InputOption::VALUE_OPTIONAL,
				'Traiter un document en particulier',
				null
			)
			->addOption(
				'nb_max',
				null,
				InputOption::VALUE_OPTIONAL,
				'Nombre maximum de documents à traiter',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/oembed');
		include_spip('inc/oembed_pipelines');
		include_spip('inc/autoriser');
		include_spip('inc/renseigner_document');

		$id_document = $input->getOption('id_document');
		$nb_max = $input->getOption('nb_max');
		$res = sql_select('*', 'spip_documents', "extension like 'dist_%'" . ($id_document ? " AND id_document=" . intval($id_document) : ''));
		$nb = sql_count($res);

		if ($nb > 0) {
			$cpt = 0;
			$this->io->care("$nb documents 'dist_%' à migrer");

			while ($row = sql_fetch($res) and ($nb_max ? $cpt++ < $nb_max : true)) {

				$id_document = $row['id_document'];
				$url = '';
				switch ($row['extension']) {
					case 'dist_vimeo':
						if ($id = intval($row['fichier'])) {
							$url = "https://vimeo.com/" . $id;
							if (strpos($row['fichier'], 'share=copy') !== false) {
								$url .= '?share=copy';
							}
						} elseif(strpos($row['fichier'], 'manage/videos/') === 0 and $id = intval(substr($row['fichier'], 14))) {
							$url = "https://vimeo.com/" . $id;
						} else {
							$url = "https://vimeo.com/" . $row['fichier'];
						}
						break;
					case 'dist_youtu':
						$url = "https://www.youtube.com/watch?v=" . $row['fichier'];
						break;
				}

				if ($url) {
					$doc = renseigner_source_distante($url);
					if (!empty($doc['oembed'])) {
						$id_vignette = false;
						sql_updateq("spip_documents", $doc, "id_document=".intval($row['id_document']));
						if ($data = json_decode($doc['oembed_data'], true)) {
							autoriser_exception('modifier', 'document', $id_document);
							$id_vignette = oembed_ajouter_vignette($id_document, $data);
							autoriser_exception('modifier', 'document', $id_document, false);
						}
						if ($id_vignette) {
							$this->io->success("#$id_document " . $row['extension'] . '/' . $row['fichier'] . " => $url");
						} else {
							$this->io->care("#$id_document " . $row['extension'] . '/' . $row['fichier'] . " => $url (pas de vignette)");
						}
					} else {
						$this->io->fail("#$id_document : pas de oembed dispo pour $url");
					}
				} else {
					$this->io->fail("#$id_document non pris en charge pour l'import oembed (" . $row['extension'].")");
				}
			}
		} else {
			$this->io->success($id_document ? "Ce document n'est pas un document 'dist_%' ou bien est déjà migré" : "Il n'y a plus de document 'dist_%' à migrer");
		}

		return Command::SUCCESS;
	}
}
