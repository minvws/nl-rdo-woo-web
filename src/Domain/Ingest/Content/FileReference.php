<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

readonly class FileReference implements FileReferenceInterface
{
    public function __construct(
        private string $path,
    ) {
    }

    public static function forContentExtractOptions(ContentExtractOptions $options): self
    {
        $localFile = $options->getLocalFile();
        if ($localFile === null) {
            throw ContentExtractException::forNoLocalFileInContentExtractOptions();
        }

        return new self($localFile);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function hasPath(): bool
    {
        return true;
    }
}
