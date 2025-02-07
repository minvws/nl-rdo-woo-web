<?php

declare(strict_types=1);

namespace App\Domain\ArchiveExtractor\Exception;

final class ArchiveLogicException extends \LogicException implements ArchiveExceptionInterface
{
    public static function forArchiveIsAlreadyOpen(\SplFileInfo $file): self
    {
        return new self(
            sprintf(
                'Failed to open archive: "%s". An archive is already opened.',
                $file->getPathname(),
            ),
        );
    }

    public static function forNoOpenArchive(): self
    {
        return new self('No open archive.');
    }
}
