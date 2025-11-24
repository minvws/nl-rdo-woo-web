<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

use Shared\Domain\Publication\EntityWithFileInfo;

interface EntityExtractorInterface
{
    public function extract(EntityWithFileInfo $entity): void;
}
