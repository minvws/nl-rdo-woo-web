<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\MetadataOnly;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shared\Doctrine\LegacyNamespaceHelper;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IngestMetadataOnlyHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function __invoke(IngestMetadataOnlyCommand $message): void
    {
        $entityClass = LegacyNamespaceHelper::normalizeClassName($message->getEntityClass());
        $entity = $this->doctrine->getRepository($entityClass)->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestMetadataOnlyHandler', [
                'id' => $message->getEntityId()->toRfc4122(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        try {
            // The second and third argument are important: removes existing metadata and pages if refresh=true
            $this->subTypeIndexer->index(
                $entity,
                $message->getForceRefresh() ? [] : null,
                $message->getForceRefresh() ? [] : null
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to update ES document in IngestMetadataOnlyHandler', [
                'id' => $message->getEntityId()->toRfc4122(),
                'class' => $message->getEntityClass(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
