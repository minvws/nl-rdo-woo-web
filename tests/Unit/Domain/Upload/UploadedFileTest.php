<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Mockery;
use Shared\Domain\Upload\UploadedFile;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

final class UploadedFileTest extends UnitTestCase
{
    public function testInstanceIsInstantiable(): void
    {
        $uploadedFile = new UploadedFile($expectedFilename = 'filename', $expectedOriginalFilename = 'originalFilename');

        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(SplFileInfo::class, $uploadedFile);
        $this->assertSame($expectedFilename, $uploadedFile->getPathname());
        $this->assertSame($expectedOriginalFilename, $uploadedFile->getOriginalFilename());
    }

    public function testFromFileWithSplFile(): void
    {
        $splFileInfo = Mockery::mock(SplFileInfo::class);
        $splFileInfo->expects('getPathname')->andReturn($expectedFilename = 'filename');

        $uploadedFile = UploadedFile::fromFile($splFileInfo, $expectedOriginalFilename = 'originalFilename.txt');

        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(SplFileInfo::class, $uploadedFile);
        $this->assertSame($expectedFilename, $uploadedFile->getPathname());
        $this->assertSame($expectedOriginalFilename, $uploadedFile->getOriginalFilename());
    }

    public function testFromFileWithSymfonyFile(): void
    {
        $symfonyFile = Mockery::mock(File::class);
        $symfonyFile->expects('getPathname')->andReturn($expectedFilename = 'filename');

        $uploadedFile = UploadedFile::fromFile($symfonyFile, $expectedOriginalFilename = 'originalFilename.txt');

        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(SplFileInfo::class, $uploadedFile);
        $this->assertSame($expectedFilename, $uploadedFile->getPathname());
        $this->assertSame($expectedOriginalFilename, $uploadedFile->getOriginalFilename());
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
