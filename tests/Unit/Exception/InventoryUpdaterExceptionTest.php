<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\InventoryUpdaterException;
use PHPUnit\Framework\TestCase;

class InventoryUpdaterExceptionTest extends TestCase
{
    public function testForStateMismatch(): void
    {
        self::assertStringContainsString(
            'State mismatch between database and changeset',
            InventoryUpdaterException::forStateMismatch()->getMessage(),
        );
    }
}
