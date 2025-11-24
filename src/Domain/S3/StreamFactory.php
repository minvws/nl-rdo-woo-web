<?php

declare(strict_types=1);

namespace Shared\Domain\S3;

use Psr\Http\Message\StreamInterface;

interface StreamFactory
{
    public function createReadOnlyStream(string $bucketName, string $key): StreamInterface;

    public function createWriteOnlyStream(string $bucketName, string $key): StreamInterface;
}
