<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Upload;

use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Tests\Unit\UnitTestCase;

class UploadStatusTest extends UnitTestCase
{
    public function testEnum(): void
    {
        $this->assertMatchesSnapshot(UploadStatus::cases());
    }
}
