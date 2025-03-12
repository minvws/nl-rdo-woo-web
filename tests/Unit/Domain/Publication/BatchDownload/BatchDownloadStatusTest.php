<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownloadStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class BatchDownloadStatusTest extends MockeryTestCase
{
    public function testIsDownloadable(): void
    {
        $this->assertTrue(BatchDownloadStatus::COMPLETED->isDownloadable());
        $this->assertTrue(BatchDownloadStatus::OUTDATED->isDownloadable());
        $this->assertFalse(BatchDownloadStatus::PENDING->isDownloadable());
        $this->assertFalse(BatchDownloadStatus::FAILED->isDownloadable());
    }
}
