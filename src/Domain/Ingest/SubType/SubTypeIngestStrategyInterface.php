<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType;

use App\Domain\Ingest\IngestOptions;
use App\Entity\EntityWithFileInfo;

interface SubTypeIngestStrategyInterface
{
    public function handle(EntityWithFileInfo $entity, IngestOptions $options): void;

    public function canHandle(EntityWithFileInfo $entity): bool;
}
