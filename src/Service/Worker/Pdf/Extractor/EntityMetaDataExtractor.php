<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\Stats\WorkerStatsService;
use Shared\Service\Storage\EntityStorageService;
use Symfony\Contracts\Cache\CacheInterface;

use function sprintf;

/**
 * Extractor that will extract and store content from a multi-paged (PDF) entity.
 */
readonly class EntityMetaDataExtractor implements EntityExtractorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityStorageService $entityStorageService,
        private SubTypeIndexer $subTypeIndexer,
        private TikaService $tika,
        private WorkerStatsService $statsService,
        private CacheInterface $metadataExtractCache,
    ) {
    }

    public function extract(EntityWithFileInfo $entity): void
    {
        $cacheKey = sprintf('%s-tika-metadata', $entity->getFileInfo()->getHash());

        /** @var array<array-key,string> $metaData */
        $metaData = $this->metadataExtractCache->get(
            $cacheKey,
            fn () => $this->extractMetaDataFromPdf($entity),
        );

        $this->statsService->measure('index.entity', fn () => $this->indexEntity($entity, $metaData));
    }

    /**
     * @return array<array-key,string>
     */
    private function extractMetaDataFromPdf(EntityWithFileInfo $entity): array
    {
        /** @var string|false $localPdfPath */
        $localPdfPath = $this->statsService->measure(
            'download.entity',
            fn (): string|false => $this->entityStorageService->downloadEntity($entity),
        );

        if ($localPdfPath === false) {
            $this->logger->error('Failed to save file to local storage', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return [];
        }

        /** @var array<array-key,string> $tikaData */
        $tikaData = $this->statsService->measure(
            'tika',
            fn (): array => $this->tika->extract(
                sourcePath: $localPdfPath,
                logContext: ContentExtractLogContext::forEntity($entity),
            ),
        );

        unset($tikaData['X-TIKA:content']);

        $this->entityStorageService->removeDownload($localPdfPath);

        return $tikaData;
    }

    /**
     * @param array<array-key,string> $tikaData
     */
    private function indexEntity(EntityWithFileInfo $entity, array $tikaData): void
    {
        try {
            $this->subTypeIndexer->index($entity, $tikaData);
        } catch (Exception $e) {
            $this->logger->error('Failed to create document', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
