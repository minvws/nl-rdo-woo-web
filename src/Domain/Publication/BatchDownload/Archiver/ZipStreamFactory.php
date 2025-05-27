<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Archiver;

use Psr\Http\Message\StreamInterface;
use ZipStream\ZipStream;

readonly class ZipStreamFactory
{
    public function create(StreamInterface $outputStream): ZipStream
    {
        return new ZipStream(
            outputStream: $outputStream,
            sendHttpHeaders: false,
            enableZip64: true,
        );
    }

    public function forStreamingArchive(string $filename): ZipStream
    {
        return new ZipStream(
            defaultEnableZeroHeader: true,
            outputName: $filename,
            contentType: 'application/octet-stream',
        );
    }
}
