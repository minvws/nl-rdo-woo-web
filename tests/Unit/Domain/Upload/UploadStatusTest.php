<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload;

use App\Domain\Upload\UploadStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class UploadStatusTest extends MockeryTestCase
{
    use MatchesSnapshots;

    public function testIncomplete(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::INCOMPLETE);
    }

    public function testValidationPassed(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::VALIDATION_PASSED);
    }

    public function testValidationFailed(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::INCOMPLETE);
    }

    public function testUploaded(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::UPLOADED);
    }

    public function testStored(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::STORED);
    }

    public function testAborted(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::ABORTED);
    }
}
