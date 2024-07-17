<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\EntityWithFileInfo;

interface EntityExtractorInterface
{
    public function extract(EntityWithFileInfo $entity, bool $forceRefresh): void;
}
