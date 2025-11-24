<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Shared\Exception\ProductionReportUpdaterException;

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
