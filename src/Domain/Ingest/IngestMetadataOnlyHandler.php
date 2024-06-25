<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\Document;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

/**
 * Ingest a metadata-only document into the system.
 */
#[AsMessageHandler]
final readonly class IngestMetadataOnlyHandler
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger,
        private readonly ElasticService $elasticService,
    ) {
    }

    public function __invoke(IngestMetadataOnlyMessage $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No document found for this message', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        try {
            // TODO This assert is temp! Will be removed after below services are more generic.
            Assert::isInstanceOf($entity, Document::class);

            // The second and third argument are important: removes existing metadata and pages if refresh=true
            $this->elasticService->updateDocument(
                $entity,
                $message->getForceRefresh() ? [] : null,
                $message->getForceRefresh() ? [] : null
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to ingest metadata-only document into ES', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
