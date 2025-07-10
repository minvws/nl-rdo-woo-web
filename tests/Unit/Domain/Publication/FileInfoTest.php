<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication;

use App\Domain\Publication\FileInfo;
use App\Domain\Publication\SourceType;
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

    public function testSettingHash(): void
    {
        $fileInfo = new FileInfo();

        $fileInfo->setHash($hash = 'foo');
        $this->assertEquals($hash, $fileInfo->getHash());

        $fileInfo->setHash(null);
        $this->assertNull($fileInfo->getHash());
    }

    public function testGetNormalizedMimeType(): void
    {
        $fileInfo = new FileInfo();

        $fileInfo->setMimetype(' foo/bar  ');
        $this->assertEquals('foo/bar', $fileInfo->getNormalizedMimeType());

        $fileInfo->setMimetype(null);
        $this->assertEquals('', $fileInfo->getNormalizedMimeType());
    }

    public function testGetAndSetSourceType(): void
    {
        $fileInfo = new FileInfo();
        self::assertNull($fileInfo->getSourceType());

        $fileInfo->setSourceType(SourceType::PDF);
        self::assertEquals(SourceType::PDF, $fileInfo->getSourceType());
    }

    public function testHasPages(): void
    {
        $fileInfo = new FileInfo();
        self::assertFalse($fileInfo->hasPages());

        $fileInfo->setPageCount(10);
        self::assertTrue($fileInfo->hasPages());

        $fileInfo->setPageCount(0);
        self::assertFalse($fileInfo->hasPages());
    }
}
