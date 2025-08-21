<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileType;
use App\Tests\Unit\UnitTestCase;

final class FileTypeTest extends UnitTestCase
{
    public function testFileType(): void
    {
        $this->assertMatchesObjectSnapshot(FileType::cases());
    }
}
