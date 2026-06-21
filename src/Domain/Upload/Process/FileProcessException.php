<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

    public static function forFailingToExtractDocumentId(string $originalFile, WooDecision $dossier): self
    {
        return new self(
            sprintf(
                'Cannot extract document id from file named "%s" associated with dossier "%s"',
                $originalFile,
                $dossier->getId(),
            ),
        );
    }
}
