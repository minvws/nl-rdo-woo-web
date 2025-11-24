<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Shared\Domain\Publication\BatchDownload\BatchDownloadStatus;
use Shared\Tests\Unit\UnitTestCase;

class BatchDownloadStatusTest extends UnitTestCase
{
    public function testIsDownloadable(): void
    {
        $this->assertTrue(BatchDownloadStatus::COMPLETED->isDownloadable());
        $this->assertTrue(BatchDownloadStatus::OUTDATED->isDownloadable());
        $this->assertFalse(BatchDownloadStatus::PENDING->isDownloadable());
        $this->assertFalse(BatchDownloadStatus::FAILED->isDownloadable());
    }
}
