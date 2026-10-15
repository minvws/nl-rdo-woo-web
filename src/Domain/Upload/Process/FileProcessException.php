<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use RuntimeException;
use Shared\ValueObject\DocumentId;
use SplFileInfo;

use function sprintf;

class FileProcessException extends RuntimeException
{
    public static function forFailingToStoreDocument(SplFileInfo $file, DocumentId $documentId): self
    {
        return new self(
            sprintf(
                'Failed to store document with id "%s" with file at local path "%s"',
                $documentId->toString(),
                $file->getPathname(),
            ),
        );
    }
}
