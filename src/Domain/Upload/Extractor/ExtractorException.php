<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Extractor;

final class ExtractorException extends \RuntimeException
{
    public static function forFailingToCreateTempDir(\SplFileInfo $file): self
    {
        return new self(
            sprintf(
                'Failed to create temporary directory for archive file "%s"',
                $file->getPathname(),
            ),
        );
    }

    public static function forFailingToExtractFiles(\SplFileInfo $file, string $targetDir, ?\Throwable $e = null): self
    {
        return new self(
            sprintf(
                'Failed to extract files from archive "%s" to target "%s"',
                $file->getPathname(),
                $targetDir,
            ),
            previous: $e,
        );
    }

    public static function forFailingToOpenArchive(\SplFileInfo $file, ?\Throwable $e = null): self
    {
        return new self(
            sprintf(
                'Failed to open archive file "%s"',
                $file->getPathname(),
            ),
            previous: $e,
        );
    }
}
