<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use App\Tests\Unit\UnitTestCase;

class DocumentFileUploadStatusTest extends UnitTestCase
{
    public function testIsOpenForUploads(): void
    {
        self::assertFalse(DocumentFileUploadStatus::FAILED->isPending());
        self::assertTrue(DocumentFileUploadStatus::PENDING->isPending());
    }

    public function testIsUploaded(): void
    {
        self::assertFalse(DocumentFileUploadStatus::FAILED->isUploaded());
        self::assertTrue(DocumentFileUploadStatus::UPLOADED->isUploaded());
    }
}
