<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception\Import;

use PHPUnit\Framework\TestCase;
use Shared\Exception\Import\InvalidLinkException;

class InvalidLinkExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new InvalidLinkException(12, 'not-a-valid-url');

        self::assertSame("Invalid URL 'not-a-valid-url' in row #12", $exception->getMessage());
        self::assertSame(12, $exception->rowIndex);
    }

    public function testGetMessageData(): void
    {
        $exception = new InvalidLinkException(12, 'not-a-valid-url');

        self::assertSame(
            [
                '{link}' => 'not-a-valid-url',
            ],
            $exception->getMessageData(),
        );
    }
}
