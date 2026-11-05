<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use GuzzleHttp\Psr7\Utils;

use function fwrite;
use function rewind;

readonly class FileReference implements FileReferenceInterface
{
    public function __construct(
        private string $contents,
    ) {
    }

    public static function forContentExtractOptions(ContentExtractOptions $options): self
    {
        $fileContents = $options->getFileContents();
        if ($fileContents === null) {
            throw ContentExtractException::forNoFileContentsInContentExtractOptions();
        }

        return new self($fileContents);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $stream = Utils::tryFopen('php://memory', 'r+');
        fwrite($stream, $this->contents);
        rewind($stream);

        return $stream;
    }
}
