<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

final class DiWooRuntimeException extends \RuntimeException implements DiWooException
{
    public static function failedCreatingTempDir(): self
    {
        return new self('Could not create temporary DiWoo directory');
    }

    public static function failedGettingFileSize(string $path): self
    {
        return new self(sprintf('Could not get file size of file at path: "%s"', $path));
    }
}
