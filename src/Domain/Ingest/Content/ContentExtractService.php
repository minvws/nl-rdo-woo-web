<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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

        $this->ensureEntityHashIsSet($entity, $options, $fileReference);

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
                        )
                    ),
                );
            }

            if ($extracts->isEmpty()) {
                $this->logWithContext('No content could be extracted', $entity, LogLevel::WARNING);
            }
        } catch (\Exception $exception) {
            $this->logWithContext('Content extract error: ' . $exception->getMessage(), $entity, LogLevel::ERROR);
            $extracts->markAsFailure();
        } finally {
            $this->cleanupFileReferenceDownload($fileReference);
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
            ]
        );
    }

    /**
     * This method exist for backwards compatibility: any entities created before hashing will get the hash added on
     * the fly (once).
     */
    private function ensureEntityHashIsSet(
        EntityWithFileInfo $entity,
        ContentExtractOptions $options,
        FileReferenceInterface $fileReference,
    ): void {
        if ($entity->getFileInfo()->getHash() !== null) {
            return;
        }

        // When a page number is set the path will be page specific, but for hashing we need the complete document.
        if ($options->hasPageNumber()) {
            $documentFileReference = LazyFileReference::createForEntityWithFileInfo(
                $entity,
                $options->withoutPageNumber(),
                $this->entityStorage,
            );

            $path = $documentFileReference->getPath();
            $this->entityStorage->setHash($entity, $path);
            $this->entityStorage->removeDownload($documentFileReference->getPath());

            return;
        }

        // In other cases the existing file reference can be used. No removal of the download, as this will be used for
        // further processing.
        $path = $fileReference->getPath();
        $this->entityStorage->setHash($entity, $path);
    }

    private function getFileReference(ContentExtractOptions $options, EntityWithFileInfo $entity): FileReferenceInterface
    {
        if ($options->hasLocalFile()) {
            $fileReference = FileReference::forContentExtractOptions($options);
        } else {
            $fileReference = LazyFileReference::createForEntityWithFileInfo($entity, $options, $this->entityStorage);
        }

        return $fileReference;
    }

    private function cleanupFileReferenceDownload(FileReferenceInterface $fileReference): void
    {
        if ($fileReference instanceof LazyFileReference && $fileReference->hasPath()) {
            $this->entityStorage->removeDownload($fileReference->getPath());
        }
    }
}
