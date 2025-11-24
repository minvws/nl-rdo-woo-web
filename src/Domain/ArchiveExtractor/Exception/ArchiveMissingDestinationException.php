<?php

declare(strict_types=1);

namespace Shared\Domain\ArchiveExtractor\Exception;

final class ArchiveMissingDestinationException extends \RuntimeException implements ArchiveExceptionInterface
{
    public static function create(string $destination): self
    {
        return new self(
            sprintf('Failed to extract archive: "%s". Destination is missing or not writable.', $destination),
        );
    }
}
