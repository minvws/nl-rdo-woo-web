<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\TikaOnly;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Shared\Doctrine\LegacyNamespaceHelper;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

use function is_null;

#[AsMessageHandler]
final readonly class IngestTikaOnlyHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private ContentExtractCache $contentExtractCache,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function __invoke(IngestTikaOnlyCommand $message): void
    {
        $entityClass = LegacyNamespaceHelper::normalizeClassName($message->getEntityClass());
        $entity = $this->doctrine->getRepository($entityClass)->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestTikaOnlyHandler', [
                'id' => $message->getEntityId()->toRfc4122(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        /** @var EntityWithFileInfo $entity */
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);

        $extracts = $this->contentExtractCache->getCombinedExtracts(
            $entity,
            ContentExtractOptions::create()->withExtractor(ContentExtractorKey::TIKA),
        );

        try {
            $this->subTypeIndexer->index(
                $entity,
                [],
                [
                    [
                        'page_nr' => 0,
                        'content' => $extracts,
                    ],
                ]
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to index tika content as page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
