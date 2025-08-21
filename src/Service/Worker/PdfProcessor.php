<?php

declare(strict_types=1);

namespace App\Service\Worker;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;

readonly class PdfProcessor
{
    public function __construct(
        private EntityMetaDataExtractor $docContentExtractor,
    ) {
    }

    /**
     * Processes a multi-page document
     *    - extracts the content of the PDF.
     */
    public function processEntity(EntityWithFileInfo $entity): void
    {
        $this->docContentExtractor->extract($entity);
    }
}
