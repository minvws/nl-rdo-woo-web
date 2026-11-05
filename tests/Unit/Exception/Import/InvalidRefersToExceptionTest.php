<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception\Import;

use PHPUnit\Framework\TestCase;
use Shared\Exception\Import\InvalidRefersToException;

class InvalidRefersToExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new InvalidRefersToException('957078 | 956732', 12);

        self::assertSame("Invalid refers to value '957078 | 956732' in inventory row #12", $exception->getMessage());
    }

    public function testGetMessageKey(): void
    {
        $exception = new InvalidRefersToException('957078 | 956732', 12);

        self::assertSame('publication.dossier.error.invalid_refers_to', $exception->getMessageKey());
    }

    public function testGetMessageData(): void
    {
        $exception = new InvalidRefersToException('957078 | 956732', 12);

        self::assertSame(
            [
                '{rownumber}' => '12',
                '{refersTo}' => '957078 | 956732',
            ],
            $exception->getMessageData(),
        );
    }
}
