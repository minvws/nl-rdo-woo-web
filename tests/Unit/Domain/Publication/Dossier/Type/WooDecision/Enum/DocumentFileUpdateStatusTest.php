<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Enum;

use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateStatus;
use App\Tests\Unit\UnitTestCase;

class DocumentFileUpdateStatusTest extends UnitTestCase
{
    public function testIsPending(): void
    {
        self::assertFalse(DocumentFileUpdateStatus::COMPLETED->isPending());
        self::assertTrue(DocumentFileUpdateStatus::PENDING->isPending());
    }
}
