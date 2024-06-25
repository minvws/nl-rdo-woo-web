<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Domain\Ingest\IngestMetadataOnlyMessage;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MetadataOnlyHandler implements Handler
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Document $document, Options $options): void
    {
        $this->logger->info('Dispatching ingest for metadata-only document', [
            'id' => $document->getId(),
            'class' => $document::class,
        ]);

        // Force refresh to true so any existing metadata or pages from an older document version is removed.
        $message = new IngestMetadataOnlyMessage($document->getId(), $document::class, true);
        $this->bus->dispatch($message);
    }

    public function canHandle(FileInfo $fileInfo): bool
    {
        return $fileInfo->isUploaded() === false;
    }
}
