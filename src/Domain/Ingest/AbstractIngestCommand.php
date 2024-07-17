<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractIngestCommand
{
    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    final public function __construct(
        protected Uuid $entityId,
        protected string $entityClass,
        protected bool $forceRefresh,
    ) {
    }

    public function getEntityId(): Uuid
    {
        return $this->entityId;
    }

    /**
     * @return class-string<EntityWithFileInfo>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getForceRefresh(): bool
    {
        return $this->forceRefresh;
    }

    public static function forEntity(EntityWithFileInfo $entity, bool $refresh = false): static
    {
        return new static($entity->getId(), $entity::class, $refresh);
    }
}
