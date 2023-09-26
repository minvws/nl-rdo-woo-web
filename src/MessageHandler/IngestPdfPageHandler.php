<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Message\IngestPdfPageMessage;
use App\Service\Worker\PdfProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ingest a single PDF page into the system.
 */
#[AsMessageHandler]
class IngestPdfPageHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected PdfProcessor $processor;

    public function __construct(
        PdfProcessor $processor,
        EntityManagerInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->processor = $processor;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public function __invoke(IngestPdfPageMessage $message): void
    {
        try {
            $document = $this->doctrine->getRepository(Document::class)->find($message->getUuid());
            if (! $document) {
                // No document found for this message
                $this->logger->warning('No document found for this message', [
                    'uuid' => $message->getUuid(),
                    'pageNr' => $message->getPageNr(),
                ]);

                return;
            }

            $this->processor->processDocumentPage($document, $message->getPageNr(), $message->getForceRefresh());
        } catch (\Exception $e) {
            $this->logger->error('Error processing document', [
                'uuid' => $message->getUuid(),
                'pageNr' => $message->getPageNr(),
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
