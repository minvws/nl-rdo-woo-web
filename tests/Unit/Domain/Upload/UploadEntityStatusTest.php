<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class UploadEntityStatusTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testEnum(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::cases());
    }
}
