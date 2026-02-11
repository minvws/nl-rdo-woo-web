<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use RuntimeException;
use Shared\Domain\Publication\EntityWithFileInfo;

use function sprintf;

class ContentExtractException extends RuntimeException
{
    public static function forCannotCreateLazyFileReference(EntityWithFileInfo $entity): self
    {
        return new self(sprintf(
            'Cannot create lazy file reference for entity %s with id %s',
            $entity::class,
            $entity->getId()->toRfc4122(),
        ));
    }

    public static function forCannotCreateLazyFileReferenceForPage(): self
    {
        return new self('Cannot use a lazy file reference for a page');
    }

    public static function forNoLocalFileInContentExtractOptions(): self
    {
        return new self('No local file in content extract options');
    }
}
