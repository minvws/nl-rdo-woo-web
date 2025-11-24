<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use Shared\Domain\Upload\FileType\FileType;
use Shared\Tests\Unit\UnitTestCase;

final class FileTypeTest extends UnitTestCase
{
    public function testFileType(): void
    {
        $this->assertMatchesObjectSnapshot(FileType::cases());
    }
}
