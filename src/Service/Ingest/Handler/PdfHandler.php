<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Entity\Document;
use App\Message\IngestPdfMessage;
use App\Service\DocumentService;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Extractor\PagecountExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PdfHandler extends BaseHandler implements Handler
{
    protected DocumentStorageService $storageService;
    protected DocumentService $documentService;
    protected PagecountExtractor $extractor;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $doctrine,
        LoggerInterface $logger,
        DocumentStorageService $storageService,
        DocumentService $documentService,
        PagecountExtractor $extractor,
    ) {
        parent::__construct($bus, $doctrine, $logger);

        $this->storageService = $storageService;
        $this->documentService = $documentService;
        $this->extractor = $extractor;
    }

    public function handle(Document $document, Options $options): void
    {
        $this->logger->info('Ingesting PDF for document', [
            'document' => $document->getId(),
        ]);

        $message = new IngestPdfMessage($document->getId(), $options->forceRefresh());
        $this->bus->dispatch($message);
    }

    public function canHandle(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }
}
