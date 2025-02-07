<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Event;

use App\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

readonly class EntityFileUpdateEvent
{
    final private function __construct(
        public Uuid $entityId,
    ) {
    }

    public static function forEntity(EntityWithFileInfo $entity): self
    {
        return new self($entity->getId());
    }
}
