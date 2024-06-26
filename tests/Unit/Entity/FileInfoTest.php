<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileInfo;
use App\Tests\Unit\UnitTestCase;

final class FileInfoTest extends UnitTestCase
{
    public function testDefaultValues(): void
    {
        $fileInfo = new FileInfo();

        $this->assertFalse($fileInfo->isPaginatable());
        $this->assertNull($fileInfo->getPageCount());
    }

    public function testSettingPageCount(): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setPageCount(10);

        $this->assertSame(10, $fileInfo->getPageCount());
    }

    public function testSettingPaginatable(): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setPaginatable(true);

        $this->assertTrue($fileInfo->isPaginatable());
    }

    public function testRemoveFileProperties(): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setPageCount(10);

        $fileInfo->removeFileProperties();

        $this->assertNull($fileInfo->getPageCount());
    }
}
