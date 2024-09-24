<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload;

use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class UploadedFileTest extends UnitTestCase
{
    public function testInstanceIsInstantiable(): void
    {
        $uploadedFile = new UploadedFile('filename', 'originalFilename');

        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(\SplFileInfo::class, $uploadedFile);
    }

    public function testFromSplFile(): void
    {
        /** @var \SplFileInfo&MockInterface $splFileInfo */
        $splFileInfo = \Mockery::mock(\SplFileInfo::class);
        $splFileInfo->shouldReceive('getPathname')->andReturn('filename');

        $uploadedFile = UploadedFile::fromSplFile($splFileInfo, 'originalFilename.txt');

        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(\SplFileInfo::class, $uploadedFile);
    }

    public function testGetOriginalFilename(): void
    {
        $uploadedFile = new UploadedFile('filename', $expected = 'originalFilename');

        $this->assertSame($expected, $uploadedFile->getOriginalFilename());
    }

    public function testGetOriginalFilenameFallsbackOnActualFilename(): void
    {
        $uploadedFile = new UploadedFile('filename');

        $this->assertSame('filename', $uploadedFile->getOriginalFilename());
    }

    public function testGetOriginalFileExtension(): void
    {
        $uploadedFile = new UploadedFile('filename', 'originalFilename.txt');

        $this->assertSame('txt', $uploadedFile->getOriginalFileExtension());
    }

    public function testGetOriginalFilenameFallsbackOnActualFileExtension(): void
    {
        $uploadedFile = new UploadedFile('filename.txt');

        $this->assertSame('txt', $uploadedFile->getOriginalFileExtension());
    }

    public function testGetOriginalFileExtensionWithTheOriginalFilenameNotHavingAnExtension(): void
    {
        $uploadedFile = new UploadedFile('filename');

        $this->assertSame('', $uploadedFile->getOriginalFileExtension());
    }
}
