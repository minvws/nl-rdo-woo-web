<?php

declare(strict_types=1);

namespace Shared\Domain\ArchiveExtractor\Exception;

use RuntimeException;
use SplFileInfo;
use Throwable;

use function sprintf;

final class ArchiveRuntimeException extends RuntimeException implements ArchiveExceptionInterface
{
    public static function forExtractionFailure(?Throwable $e = null): self
    {
        $previousMessagePart = $e === null
            ? ''
            : sprintf(': %s', $e->getMessage());

        return new self(
            sprintf('Failed to extract archive%s', $previousMessagePart),
            previous: $e,
        );
    }

    public static function forFailedToOpenArchive(SplFileInfo $file): self
    {
        return new self(sprintf('Failed to open archive at "%s".', $file->getPathname()));
    }

    public static function forFailedToCloseArchive(): self
    {
        return new self('Failed to close archive.');
    }
}
