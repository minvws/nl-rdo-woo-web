<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\TikaService;
use Psr\Log\LoggerInterface;

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
        private WorkerStatsService $statsService
    ) {
    }

    public function extract(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        // TODO: Cache is removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);

        $metaData = $this->extractMetaDataFromPdf($entity);

        $this->statsService->measure('index.entity', fn () => $this->indexEntity($entity, $metaData));
    }

    /**
     * @return array<array-key,string>
     */
    protected function extractMetaDataFromPdf(EntityWithFileInfo $entity): array
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
            fn (): array => $this->tika->extract($localPdfPath),
        );

        unset($tikaData['X-TIKA:content']);

        $this->entityStorageService->removeDownload($localPdfPath);

        return $tikaData;
    }

    /**
     * @param array<array-key,string> $tikaData
     */
    protected function indexEntity(EntityWithFileInfo $entity, array $tikaData): void
    {
        try {
            $this->subTypeIndexer->index($entity, $tikaData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create document', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
