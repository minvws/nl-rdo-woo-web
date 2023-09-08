<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Entity\Document;
use App\Entity\FileInfo;
use App\Message\IngestMetadataOnlyMessage;
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
            'document' => $document->getId(),
        ]);

        $message = new IngestMetadataOnlyMessage($document->getId(), $options->forceRefresh());
        $this->bus->dispatch($message);
    }

    public function canHandle(FileInfo $fileInfo): bool
    {
        return $fileInfo->isUploaded() === false;
    }
}
