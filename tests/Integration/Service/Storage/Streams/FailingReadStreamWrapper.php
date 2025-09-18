<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Storage\Streams;

/**
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 *
 * @phpcs:disabled PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class FailingReadStreamWrapper extends StreamWrapper
{
    public static function getName(): string
    {
        return 'failingReadStream';
    }

    public function stream_open(): true
    {
        return true; // Pretend the stream is opened successfully
    }

    public function stream_read(): false
    {
        return false; // Simulate a read failure
    }

    public function stream_eof(): false
    {
        return false; // Simulate that we're not at EOF yet
    }
}
