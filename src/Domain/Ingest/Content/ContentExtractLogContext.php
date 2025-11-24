<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Shared\Domain\Publication\EntityWithFileInfo;

readonly class ContentExtractLogContext
{
    public function __construct(
        public string $class,
        public string $id,
        public ?int $page,
    ) {
    }

    public static function forEntity(EntityWithFileInfo $entity, ?int $page = null): self
    {
        return new self(
            $entity::class,
            $entity->getId()->toRfc4122(),
            $page,
        );
    }
}
