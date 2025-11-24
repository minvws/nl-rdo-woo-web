<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Uploader;

use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;

final class UploadGroupIdTest extends UnitTestCase
{
    public function testUploadGroupId(): void
    {
        $this->assertMatchesObjectSnapshot(UploadGroupId::cases());
    }
}
