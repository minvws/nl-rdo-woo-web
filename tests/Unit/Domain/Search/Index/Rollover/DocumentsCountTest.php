<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\Rollover\DocumentCounts;
use PHPUnit\Framework\TestCase;

class DocumentsCountTest extends TestCase
{
    public function testConstructor(): void
    {
        $documentCounts = new DocumentCounts(10, 100);

        self::assertEquals(10, $documentCounts->documentCount);
        self::assertEquals(100, $documentCounts->totalPageCount);
    }
}
