<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Tests\Unit\UnitTestCase;

class UploadEntityStatusTest extends UnitTestCase
{
    public function testEnum(): void
    {
        $this->assertMatchesSnapshot(UploadEntityStatus::cases());
    }
}
