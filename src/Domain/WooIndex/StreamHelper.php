<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use Webmozart\Assert\Assert;

readonly class StreamHelper
{
    /**
     * @return resource
     */
    public function createTempStream()
    {
        $stream = fopen('php://temp', 'wb+');
        Assert::notFalse($stream);

        return $stream;
    }

    /**
     * @param resource $stream
     */
    public function size($stream): int
    {
        Assert::resource($stream);

        $stat = fstat($stream);
        Assert::notFalse($stat);

        $fileSize = $stat['size'];
        Assert::integer($fileSize);

        return $fileSize;
    }
}
