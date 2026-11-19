<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use RuntimeException;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\ValueObject\DocumentId;

use function sprintf;

class FileProcessException extends RuntimeException
{
    public static function forFailingToStoreDocument(EntityWithFileInfo $source, DocumentId $documentId): self
    {
        return new self(
            sprintf(
                'Failed to store document with id "%s" with file at path "%s"',
                $documentId->toString(),
                $source->getFileInfo()->getPath(),
            ),
        );
    }
}
