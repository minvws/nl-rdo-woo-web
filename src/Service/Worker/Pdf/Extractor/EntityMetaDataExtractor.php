<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

use Exception;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\Storage\EntityStorageService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Webmozart\Assert\Assert;

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
        #[Target('metadataExtractCache')]
        private CacheInterface $metadataExtractCache,
    ) {
    }

    public function extract(EntityWithFileInfo $entity): void
    {
        $hash = $entity->getFileInfo()->getHash();
        Assert::notNull($hash, 'Cannot generate cache key for entity without hash in FileInfo');

        $cacheKey = sprintf('%s-tika-metadata', $hash);

        /** @var array<array-key,string> $metaData */
        $metaData = $this->metadataExtractCache->get(
            $cacheKey,
            fn () => $this->extractMetaDataFromPdf($entity),
        );

        $this->indexEntity($entity, $metaData);
    }

    /**
     * @return array<array-key,string>
     */
    private function extractMetaDataFromPdf(EntityWithFileInfo $entity): array
    {
        $source = $this->entityStorageService->retrieveResourceEntity($entity);
        if ($source === null) {
            $this->logger->error('Failed to read file from storage', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return [];
        }

        $tikaData = $this->tika->extract(
            source: Utils::streamFor($source),
            logContext: ContentExtractLogContext::forEntity($entity),
        );

        unset($tikaData['X-TIKA:content']);

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
