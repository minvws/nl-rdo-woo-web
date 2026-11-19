<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception\Import;

use PHPUnit\Framework\TestCase;
use Shared\Exception\Import\InvalidInquiryNumbersException;

class InvalidInquiryNumbersExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new InvalidInquiryNumbersException(12, 'case 1, case#2');

        self::assertSame("Invalid inquiry numbers 'case 1, case#2' in row #12", $exception->getMessage());
        self::assertSame(12, $exception->rowIndex);
    }

    public function testGetMessageData(): void
    {
        $exception = new InvalidInquiryNumbersException(12, 'case 1, case#2');

        self::assertSame(
            [
                '{inquiryNumbers}' => 'case 1, case#2',
            ],
            $exception->getMessageData(),
        );
    }
}
