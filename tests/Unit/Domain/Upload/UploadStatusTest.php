<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Shared\Domain\Upload\UploadStatus;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class UploadStatusTest extends UnitTestCase
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
