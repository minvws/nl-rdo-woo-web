<?php

declare(strict_types=1);

namespace App\Command\Ingest;

use App\Entity\Document;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IngestDocument extends Command
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
        $this->setName('woopie:ingest:document')
            ->setDescription('Ingests a document into elasticsearch')
            ->setHelp('Ingests a document')
            ->setDefinition([
                new InputArgument('document', InputArgument::REQUIRED, 'The document to ingest'),
                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documentNr = strval($input->getArgument('document'));

        $options = new Options();
        $options->setForceRefresh($input->getOption('force-refresh') == true);

        $document = $this->doctrine->getRepository(Document::class)->findOneby(['documentNr' => $documentNr]);
        if ($document) {
            $this->ingester->ingest($document, $options);
            $output->writeln('Ingested document ' . $document->getDocumentNr());

            return 0;
        }

        $output->writeln('<error>Document ' . $documentNr . ' not found</error>');

        return 1;
    }
}
