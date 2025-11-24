<?php

declare(strict_types=1);

namespace Shared\Service\Worker;

use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;

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
