<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Domain\Publication\EntityWithFileInfo;

class ContentExtractException extends \RuntimeException
{
    public static function forCannotCreateLazyFileReference(EntityWithFileInfo $entity): self
    {
        return new self(sprintf(
            'Cannot create lazy file reference for entity %s with id %s',
            $entity::class,
            $entity->getId()->toRfc4122(),
        ));
    }
}
