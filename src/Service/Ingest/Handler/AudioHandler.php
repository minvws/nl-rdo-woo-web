<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use App\Entity\Document;
use App\Message\IngestAudioMessage;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Options;

class AudioHandler extends BaseHandler implements Handler
{
    public function handle(Document $document, Options $options): void
    {
        $this->logger->info('Ingesting AUDIO into document', [
            'document' => $document->getId(),
        ]);

        $message = new IngestAudioMessage($document->getId());
        $this->bus->dispatch($message);
    }

    public function canHandle(string $mimeType): bool
    {
        return $mimeType === 'audio/mpeg';
    }
}
