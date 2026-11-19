<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception\Import;

use PHPUnit\Framework\TestCase;
use Shared\Exception\Import\LinkTooLongException;

class LinkTooLongExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new LinkTooLongException(12, 'https://example.com', 2048);

        self::assertSame('Link is too long in row #12', $exception->getMessage());
        self::assertSame(12, $exception->rowIndex);
    }

    public function testGetMessageData(): void
    {
        $exception = new LinkTooLongException(12, 'https://example.com', 2048);

        self::assertSame(
            [
                '{max}' => '2048',
            ],
            $exception->getMessageData(),
        );
    }
}
