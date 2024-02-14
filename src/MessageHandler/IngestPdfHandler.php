<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Message\IngestPdfMessage;
use App\Message\IngestPdfPageMessage;
use App\Service\Worker\Pdf\Extractor\PagecountExtractor;
use App\Service\Worker\PdfProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Ingest a PDF file into the system. It will extract all pages fromm the pdf and emits a message for each page.
 */
#[AsMessageHandler]
class IngestPdfHandler
{
    protected EntityManagerInterface $doctrine;
    protected PagecountExtractor $extractor;
    protected LoggerInterface $logger;
    protected MessageBusInterface $bus;
    protected PdfProcessor $processor;

    public function __construct(
        EntityManagerInterface $doctrine,
        PagecountExtractor $extractor,
        MessageBusInterface $bus,
        PdfProcessor $processor,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->extractor = $extractor;
        $this->bus = $bus;
        $this->logger = $logger;
        $this->processor = $processor;
    }

    public function __invoke(IngestPdfMessage $message): void
    {
        $document = $this->doctrine->getRepository(Document::class)->find($message->getUuid());
        if (! $document) {
            // No document found for this message
            $this->logger->warning('No document found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        // Create extractor or cached extractor based on the given options
        $this->extractor->extract($document, $message->getForceRefresh());
        $data = $this->extractor->getOutput($document, 0);
        $pageCount = $data['count'] ?? 0;

        // Update page count of document
        $document->setPageCount($pageCount);
        $this->doctrine->persist($document);
        $this->doctrine->flush();

        // Process document and store in elastic as a whole
        $this->processor->processDocument($document, $message->getForceRefresh());

        // Go and ingest all pages in the document
        for ($i = 1; $i <= $pageCount; $i++) {
            $message = new IngestPdfPageMessage($document->getId(), $i, $message->getForceRefresh());
            $this->bus->dispatch($message);
        }
    }
}
