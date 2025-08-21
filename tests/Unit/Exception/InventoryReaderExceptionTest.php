<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\InventoryReaderException;
use PHPUnit\Framework\TestCase;

final class InventoryReaderExceptionTest extends TestCase
{
    public function testForInventoryCannotBeStored(): void
    {
        self::assertStringContainsString(
            '123',
            InventoryReaderException::forMissingDocumentIdInRow(123)->getMessage(),
        );
    }

    public function testForInvalidDocumentId(): void
    {
        self::assertStringContainsString(
            '123',
            InventoryReaderException::forInvalidDocumentId(123)->getMessage(),
        );
    }

    public function testForMissingMatterInRow(): void
    {
        self::assertStringContainsString(
            '123',
            InventoryReaderException::forInvalidMatterInRow(123, 2, 50)->getMessage(),
        );
    }

    public function testForLinkTooLong(): void
    {
        $message = InventoryReaderException::forLinkTooLong('foo-bar', 123)->getMessage();

        self::assertStringContainsString('foo-bar', $message);
        self::assertStringContainsString('123', $message);
    }

    public function testForFileTooLong(): void
    {
        $message = InventoryReaderException::forFileTooLong('foo-bar', 123)->getMessage();

        self::assertStringContainsString('foo-bar', $message);
        self::assertStringContainsString('123', $message);
    }
}
