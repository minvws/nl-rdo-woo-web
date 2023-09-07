<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Entity\Document;
use App\Entity\FileInfo;
use App\Message\IngestAudioMessage;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AudioHandler implements Handler
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Document $document, Options $options): void
    {
        $this->logger->info('Dispatching ingest for audio document', [
            'document' => $document->getId(),
        ]);

        $message = new IngestAudioMessage($document->getId());
        $this->bus->dispatch($message);
    }

    public function canHandle(FileInfo $fileInfo): bool
    {
        return $fileInfo->getMimetype() === 'audio/mpeg';
    }
}
