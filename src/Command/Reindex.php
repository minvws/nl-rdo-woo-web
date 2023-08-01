<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Dossier;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Logging\LoggingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class Reindex extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly IngestService $ingester,
        private readonly LoggingHelper $loggingHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:index:regenerate')
            ->setDescription('Regenerates the whole elasticsearch index')
            ->setHelp('Regenerates the whole elasticsearch index based on the dossiers and documents in the database')
            ->setDefinition([
                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loggingHelper->disableAll();

        $stopwatch = new Stopwatch();
        $stopwatch->start('reindex-documents');

        $output->writeln('Triggering reindex for all documents...');

        $ingestOptions = new Options();
        $ingestOptions->setForceRefresh($input->getOption('force-refresh') == true);

        $dossierRepository = $this->doctrine->getRepository(Dossier::class);
        $progressBar = new ProgressBar($output, $dossierRepository->count([]));
        $progressBar->start();

        $dossierCount = 0;
        $documentCount = 0;
        $query = $dossierRepository->createQueryBuilder('d')->getQuery();
        foreach ($query->toIterable() as $dossier) {
            foreach ($dossier->getDocuments() as $document) {
                $this->ingester->ingest($document, $ingestOptions);
                $documentCount++;
            }

            $dossierCount++;
            $progressBar->advance();

            $this->doctrine->clear();
        }

        $progressBar->finish();

        $output->writeln(" Reindexed $dossierCount dossiers with $documentCount total documents.");

        $stopwatch->stop('reindex-documents');
        $output->writeln($stopwatch->getEvent('reindex-documents')->__toString());

        $this->loggingHelper->restoreAll();

        return 0;
    }
}
