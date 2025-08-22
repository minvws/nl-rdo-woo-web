<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Storage\Streams;

/**
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
class FailingWriteStreamWrapper extends StreamWrapper
{
    public static function getName(): string
    {
        return 'failingWriteStream';
    }

    public function stream_open(): bool
    {
        return true; // Pretend the stream is opened successfully
    }

    public function stream_write(): false
    {
        return false; // Simulate a write failure
    }
}
