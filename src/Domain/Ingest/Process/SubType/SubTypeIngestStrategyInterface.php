<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\SubType;

use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.ingest.subtype.strategy')]
interface SubTypeIngestStrategyInterface
{
    public function handle(EntityWithFileInfo $entity, IngestProcessOptions $options): void;

    public function canHandle(EntityWithFileInfo $entity): bool;
}
