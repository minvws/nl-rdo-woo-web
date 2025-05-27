<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;

final class UploadGroupIdTest extends UnitTestCase
{
    public function testUploadGroupId(): void
    {
        $this->assertMatchesObjectSnapshot(UploadGroupId::cases());
    }
}
