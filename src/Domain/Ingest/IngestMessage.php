<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

abstract readonly class IngestMessage
{
    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    public function __construct(
        protected Uuid $entityId,
        protected string $entityClass,
        protected bool $forceRefresh = false
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
}
