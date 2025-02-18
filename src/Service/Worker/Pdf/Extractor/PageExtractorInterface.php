<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Publication\EntityWithFileInfo;

interface PageExtractorInterface
{
    public function extract(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void;
}
