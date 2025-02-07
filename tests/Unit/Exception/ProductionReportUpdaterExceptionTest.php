<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\ProductionReportUpdaterException;
use PHPUnit\Framework\TestCase;

class ProductionReportUpdaterExceptionTest extends TestCase
{
    public function testForStateMismatch(): void
    {
        self::assertStringContainsString(
            'State mismatch between database and changeset',
            ProductionReportUpdaterException::forStateMismatch()->getMessage(),
        );
    }
}
