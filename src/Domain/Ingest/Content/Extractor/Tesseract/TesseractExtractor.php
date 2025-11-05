<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tesseract;

use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\FileReferenceInterface;
use App\Domain\Publication\EntityWithFileInfo;

readonly class TesseractExtractor implements ContentExtractorInterface
{
    public function __construct(
        private TesseractService $tesseractService,
    ) {
    }

    /**
     * Important: tesseract will only extract content for the first page of the file!
     * Multipage files must be split up into single page files first, executing this extractor on each individual page.
     */
    public function getContent(EntityWithFileInfo $entity, FileReferenceInterface $fileReference): string
    {
        $content = $this->tesseractService->extract(
            $fileReference->getPath(),
        );

        return trim($content);
    }

    public function supports(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->getNormalizedMimeType() === 'application/pdf';
    }

    public function getKey(): ContentExtractorKey
    {
        return ContentExtractorKey::TESSERACT;
    }
}
