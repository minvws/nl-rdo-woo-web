<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class UploadStatusTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testIncomplete(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::INCOMPLETE);
    }

    public function testValidationPassed(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::VALIDATION_PASSED);
    }

    public function testValidationFailed(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::INCOMPLETE);
    }

    public function testUploaded(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::UPLOADED);
    }

    public function testStored(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::STORED);
    }

    public function testAborted(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::ABORTED);
    }
}
