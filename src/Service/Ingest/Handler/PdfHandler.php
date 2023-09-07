<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Entity\Document;
use App\Entity\FileInfo;
use App\Message\IngestPdfMessage;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PdfHandler implements Handler
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Document $document, Options $options): void
    {
        $this->logger->info('Dispatching ingest for PDF document', [
            'document' => $document->getId(),
        ]);

        $message = new IngestPdfMessage($document->getId(), $options->forceRefresh());
        $this->bus->dispatch($message);
    }

    public function canHandle(FileInfo $fileInfo): bool
    {
        return $fileInfo->getMimetype() === 'application/pdf';
    }
}
