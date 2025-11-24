<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use Shared\Tests\Unit\UnitTestCase;

class DocumentFileUpdateStatusTest extends UnitTestCase
{
    public function testIsPending(): void
    {
        self::assertFalse(DocumentFileUpdateStatus::COMPLETED->isPending());
        self::assertTrue(DocumentFileUpdateStatus::PENDING->isPending());
    }
}
