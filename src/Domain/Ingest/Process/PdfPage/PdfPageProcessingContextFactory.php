<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\PdfPage;

use Shared\Domain\Publication\EntityWithFileInfo;

readonly class PdfPageProcessingContextFactory
{
    public function createContext(EntityWithFileInfo $entity, int $pageNumber): ?PdfPageProcessingContext
    {
        if (! $entity->getFileInfo()->isUploaded()) {
            return null;
        }

        return new PdfPageProcessingContext(
            $entity,
            $pageNumber,
        );
    }
}
