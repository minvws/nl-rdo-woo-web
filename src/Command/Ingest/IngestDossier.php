<?php

declare(strict_types=1);

namespace App\Command\Ingest;

use App\Entity\Dossier;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IngestDossier extends Command
{
    protected IngestService $ingester;
    protected EntityManagerInterface $doctrine;

    public function __construct(IngestService $ingester, EntityManagerInterface $doctrine)
    {
        parent::__construct();

        $this->ingester = $ingester;
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setName('woopie:ingest:dossier')
            ->setDescription('Ingests a complete dossier into elasticsearch')
            ->setHelp('Ingests a complete dossier')
            ->setDefinition([
                new InputArgument('prefix', InputArgument::REQUIRED, 'The prefix of the dossier to ingest'),
                new InputArgument('dossierNr', InputArgument::REQUIRED, 'The dossiernr of the dossier to ingest'),
                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = strval($input->getArgument('prefix'));
        $dossierNr = strval($input->getArgument('dossierNr'));

        $options = new Options();
        $options->setForceRefresh($input->getOption('force-refresh') == true);

        $dossier = $this->doctrine->getRepository(Dossier::class)->findOneBy(['documentPrefix' => $prefix, 'dossierNr' => $dossierNr]);
        if ($dossier) {
            foreach ($dossier->getDocuments() as $document) {
                $this->ingester->ingest($document, $options);
                $output->writeln('Ingesting document ' . $document->getDocumentNr());
            }

            return 0;
        }

        $output->writeln('<error>Dossier with prefix ' . $prefix . ' and dossierNr ' . $dossierNr . ' not found</error>');

        return 1;
    }
}
