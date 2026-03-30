<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Preprocessor\Strategy;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\AntiVirus\FileScanResult;
use Shared\Domain\Upload\Extractor\Extractor;
use Shared\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;
use Symfony\Component\Mime\MimeTypesInterface;

use function iterator_to_array;

final class SevenZipFileStrategyTest extends UnitTestCase
{
    use IterableToGenerator;

    private Extractor&MockInterface $extractor;
    private MimeTypesInterface&MockInterface $mimeTypes;
    private ClamAvFileScanner&MockInterface $scanner;
    private LocalFilesystem&MockInterface $localFilesystem;
    private SevenZipFileStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = Mockery::mock(Extractor::class);
        $this->mimeTypes = Mockery::mock(MimeTypesInterface::class);
        $this->scanner = Mockery::mock(ClamAvFileScanner::class);
        $this->localFilesystem = Mockery::mock(LocalFilesystem::class);

        $this->strategy = new SevenZipFileStrategy(
            $this->extractor,
            $this->mimeTypes,
            $this->scanner,
            $this->localFilesystem,
        );
    }

    public function testProcess(): void
    {
        $file = Mockery::mock(UploadedFile::class);

        $extractedFileOne = Mockery::mock(SplFileInfo::class);
        $extractedFileOne->expects('getPathname')
            ->times(3)
            ->andReturn($pathOne = 'my/path/file_one.pdf');
        $extractedFileOne->expects('getBasename')->andReturn('file_one.pdf');

        $extractedFileTwo = Mockery::mock(SplFileInfo::class);
        $extractedFileTwo->expects('getPathname')
            ->times(3)
            ->andReturn($pathTwo = 'my/path/file_two.doc');
        $extractedFileTwo->expects('getBasename')->andReturn('file_two.doc');

        $this->extractor
            ->expects('getFiles')
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFileOne, $extractedFileTwo]));

        $this->localFilesystem->expects('isSymlink')
            ->times(2)
            ->andReturnFalse();

        $this->scanner->expects('scan')->with($pathOne)->andReturn(FileScanResult::SAFE);
        $this->scanner->expects('scan')->with($pathTwo)->andReturn(FileScanResult::SAFE);

        $result = iterator_to_array($this->strategy->process($file), false);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(UploadedFile::class, $result[0]);
        $this->assertInstanceOf(UploadedFile::class, $result[1]);

        $this->assertEquals($pathOne, $result[0]->getPathname());
        $this->assertEquals($pathTwo, $result[1]->getPathname());
    }

    public function testProcessDoesNotYieldIfSymlink(): void
    {
        $file = Mockery::mock(UploadedFile::class);

        $extractedFile = Mockery::mock(SplFileInfo::class);
        $extractedFile->expects('getPathname')->andReturn('my/path/file_one.pdf');

        $this->extractor
            ->expects('getFiles')
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFile]));

        $this->localFilesystem->expects('isSymlink')
            ->andReturnTrue();

        $result = iterator_to_array($this->strategy->process($file), false);

        $this->assertCount(0, $result);
    }

    public function testProcessYieldsOnlySafeFiles(): void
    {
        $file = Mockery::mock(UploadedFile::class);

        $extractedFileOne = Mockery::mock(SplFileInfo::class);
        $extractedFileOne->expects('getPathname')
            ->times(2)
            ->andReturn($pathOne = 'my/path/file_one.pdf');

        $extractedFileTwo = Mockery::mock(SplFileInfo::class);
        $extractedFileTwo->expects('getPathname')
            ->times(3)
            ->andReturn($pathTwo = 'my/path/file_two.doc');
        $extractedFileTwo->expects('getBasename')->andReturn('file_two.doc');

        $extractedFileThree = Mockery::mock(SplFileInfo::class);
        $extractedFileThree->expects('getPathname')->andReturn('my/path/file_four.doc');

        $this->extractor
            ->expects('getFiles')
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFileOne, $extractedFileTwo, $extractedFileThree]));

        $this->localFilesystem->expects('isSymlink')
            ->times(3)
            ->andReturn(false, false, true);

        $this->scanner->expects('scan')->with($pathOne)->andReturn(FileScanResult::UNSAFE);
        $this->scanner->expects('scan')->with($pathTwo)->andReturn(FileScanResult::SAFE);

        $result = iterator_to_array($this->strategy->process($file), false);

        $this->assertCount(1, $result);

        $this->assertInstanceOf(UploadedFile::class, $result[0]);

        $this->assertEquals($pathTwo, $result[0]->getPathname());
    }

    public function testCanProcessReturnsTrueOn7zExt(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->expects('getOriginalFileExtension')->andReturn('7z');
        $file->expects('getPathname')->andReturn($path = 'test.7z');

        $this->mimeTypes
            ->expects('guessMimeType')
            ->with($path)
            ->andReturn('application/x-7z-compressed');

        $this->assertTrue($this->strategy->canProcess($file));
    }

    public function testCanProcessReturnsTrueOnZipExt(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->expects('getOriginalFileExtension')->andReturn('zip');
        $file->expects('getPathname')->andReturn($path = 'test.zip');

        $this->mimeTypes
            ->expects('guessMimeType')
            ->with($path)
            ->andReturn('application/zip');

        $this->assertTrue($this->strategy->canProcess($file));
    }

    public function testCanProcessReturnsFalseOnPdf(): void
    {
        $path = 'my/path/file.pdf';

        $file = Mockery::mock(UploadedFile::class);
        $file->expects('getOriginalFileExtension')->andReturn('pdf');
        $file->expects('getPathname')->andReturn($path);

        $this->mimeTypes
            ->expects('guessMimeType')
            ->with($path)
            ->andReturn('application/pdf');

        $this->assertFalse($this->strategy->canProcess($file));
    }
}
