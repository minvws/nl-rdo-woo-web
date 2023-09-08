<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Message\IngestMetadataOnlyMessage;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ingest a metadata-only document into the system.
 */
#[AsMessageHandler]
class IngestMetadataOnlyHandler
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger,
        private readonly ElasticService $elasticService,
    ) {
    }

    public function __invoke(IngestMetadataOnlyMessage $message): void
    {
        $document = $this->doctrine->getRepository(Document::class)->find($message->getUuid());
        if (! $document) {
            // No document found for this message
            $this->logger->warning('No document found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        try {
            // The third argument is very important: this will remove any existing page content.
            $this->elasticService->updateDocument($document, [], []);
        } catch (\Exception $e) {
            $this->logger->error('Failed to ingest metadata-only document into ES', [
                'document' => $document->getDocumentNr(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
