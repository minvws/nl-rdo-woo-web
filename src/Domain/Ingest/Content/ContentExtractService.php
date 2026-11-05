<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ContentExtractService
{
    /**
     * @param iterable<ContentExtractorInterface> $extractors
     */
    public function __construct(
        private EntityStorageService $entityStorage,
        private LoggerInterface $logger,
        #[AutowireIterator('woo_platform.ingest.content_extractor')]
        private iterable $extractors,
    ) {
    }

    public function getExtracts(
        EntityWithFileInfo $entity,
        ContentExtractOptions $options,
    ): ContentExtractCollection {
        $extracts = new ContentExtractCollection();

        if (! $entity->getFileInfo()->isUploaded()) {
            $this->logWithContext('ContentExtract skipped because file was not uploaded', $entity, LogLevel::WARNING);

            return $extracts->markAsFailure();
        }

        $fileReference = $this->getFileReference($options, $entity);

        try {
            foreach ($this->extractors as $extractor) {
                if (! $options->isExtractorEnabled($extractor)) {
                    continue;
                }

                if (! $extractor->supports($entity)) {
                    continue;
                }

                $extracts->append(
                    new ContentExtract(
                        $extractor->getKey(),
                        $extractor->getContent(
                            $entity,
                            $fileReference,
                        ),
                    ),
                );
            }

            if ($extracts->isEmpty()) {
                $this->logWithContext('No content could be extracted', $entity, LogLevel::WARNING);
            }
        } catch (Exception $exception) {
            $this->logWithContext('Content extract error: ' . $exception->getMessage(), $entity, LogLevel::ERROR);
            $extracts->markAsFailure();
        }

        return $extracts;
    }

    private function logWithContext(string $warning, EntityWithFileInfo $entity, string $level): void
    {
        $this->logger->log(
            $level,
            $warning,
            [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ],
        );
    }

    private function getFileReference(ContentExtractOptions $options, EntityWithFileInfo $entity): FileReferenceInterface
    {
        if ($options->hasFileContents()) {
            $fileReference = FileReference::forContentExtractOptions($options);
        } else {
            $fileReference = new LazyFileReference($entity, $options, $this->entityStorage);
        }

        return $fileReference;
    }
}
