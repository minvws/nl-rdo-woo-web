<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Domain\Ingest\IngestPdfMessage;
use App\Entity\Document;
use App\Entity\FileInfo;
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
            'id' => $document->getId(),
            'class' => $document::class,
        ]);

        $message = new IngestPdfMessage($document->getId(), $document::class, $options->forceRefresh());
        $this->bus->dispatch($message);
    }

    public function canHandle(FileInfo $fileInfo): bool
    {
        return $fileInfo->getMimetype() === 'application/pdf';
    }
}
