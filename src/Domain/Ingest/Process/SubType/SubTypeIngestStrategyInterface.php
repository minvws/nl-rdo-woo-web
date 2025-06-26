<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.ingest.subtype.strategy')]
interface SubTypeIngestStrategyInterface
{
    public function handle(EntityWithFileInfo $entity, IngestProcessOptions $options): void;

    public function canHandle(EntityWithFileInfo $entity): bool;
}
