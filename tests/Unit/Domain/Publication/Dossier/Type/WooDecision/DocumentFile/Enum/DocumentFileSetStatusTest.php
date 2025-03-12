<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Tests\Unit\UnitTestCase;

class DocumentFileSetStatusTest extends UnitTestCase
{
    public function testIsOpenForUploads(): void
    {
        self::assertFalse(DocumentFileSetStatus::REJECTED->isOpenForUploads());
        self::assertTrue(DocumentFileSetStatus::OPEN_FOR_UPLOADS->isOpenForUploads());
    }

    public function testNeedsConfirmation(): void
    {
        self::assertFalse(DocumentFileSetStatus::REJECTED->needsConfirmation());
        self::assertTrue(DocumentFileSetStatus::NEEDS_CONFIRMATION->needsConfirmation());
    }

    public function testIsConfirmed(): void
    {
        self::assertFalse(DocumentFileSetStatus::REJECTED->isConfirmed());
        self::assertTrue(DocumentFileSetStatus::CONFIRMED->isConfirmed());
    }

    public function testIsProcessingUploads(): void
    {
        self::assertFalse(DocumentFileSetStatus::REJECTED->isProcessingUploads());
        self::assertTrue(DocumentFileSetStatus::PROCESSING_UPLOADS->isProcessingUploads());
    }
}
