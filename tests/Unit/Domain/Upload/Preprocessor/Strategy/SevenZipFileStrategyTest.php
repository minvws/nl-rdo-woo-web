<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Preprocessor\Strategy;

use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\AntiVirus\FileScanResult;
use App\Domain\Upload\Extractor\Extractor;
use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Mime\MimeTypesInterface;

final class SevenZipFileStrategyTest extends UnitTestCase
{
    use IterableToGenerator;

    private Extractor&MockInterface $extractor;
    private MimeTypesInterface&MockInterface $mimeTypes;
    private ClamAvFileScanner&MockInterface $scanner;
    private SevenZipFileStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = \Mockery::mock(Extractor::class);
        $this->mimeTypes = \Mockery::mock(MimeTypesInterface::class);
        $this->scanner = \Mockery::mock(ClamAvFileScanner::class);

        $this->strategy = new SevenZipFileStrategy(
            $this->extractor,
            $this->mimeTypes,
            $this->scanner,
        );
    }

    public function testProcess(): void
    {
        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);

        $extractedFileOne = \Mockery::mock(\SplFileInfo::class);
        $extractedFileOne->shouldReceive('getPathname')->andReturn($pathOne = 'my/path/file_one.pdf');
        $extractedFileOne->shouldReceive('getBasename')->andReturn('file_one.pdf');

        $extractedFileTwo = \Mockery::mock(\SplFileInfo::class);
        $extractedFileTwo->shouldReceive('getPathname')->andReturn($pathTwo = 'my/path/file_two.doc');
        $extractedFileTwo->shouldReceive('getBasename')->andReturn('file_two.doc');

        $this->extractor
            ->shouldReceive('getFiles')
            ->once()
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFileOne, $extractedFileTwo]));

        $this->scanner->shouldReceive('scan')->with($pathOne)->andReturn(FileScanResult::SAFE);
        $this->scanner->shouldReceive('scan')->with($pathTwo)->andReturn(FileScanResult::SAFE);

        $result = iterator_to_array($this->strategy->process($file));

        $this->assertCount(2, $result);

        $this->assertInstanceOf(UploadedFile::class, $result[0]);
        $this->assertInstanceOf(UploadedFile::class, $result[1]);

        $this->assertEquals($pathOne, $result[0]->getPathname());
        $this->assertEquals($pathTwo, $result[1]->getPathname());
    }

    public function testProcessYieldsOnlySafeFiles(): void
    {
        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);

        $extractedFileOne = \Mockery::mock(\SplFileInfo::class);
        $extractedFileOne->shouldReceive('getPathname')->andReturn($pathOne = 'my/path/file_one.pdf');
        $extractedFileOne->shouldReceive('getBasename')->andReturn('file_one.pdf');

        $extractedFileTwo = \Mockery::mock(\SplFileInfo::class);
        $extractedFileTwo->shouldReceive('getPathname')->andReturn($pathTwo = 'my/path/file_two.doc');
        $extractedFileTwo->shouldReceive('getBasename')->andReturn('file_two.doc');

        $extractedFileThree = \Mockery::mock(\SplFileInfo::class);
        $extractedFileThree->shouldReceive('getPathname')->andReturn($pathThree = 'my/path/file_three.doc');
        $extractedFileThree->shouldReceive('getBasename')->andReturn('file_three.doc');

        $this->extractor
            ->shouldReceive('getFiles')
            ->once()
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFileOne, $extractedFileTwo]));

        $this->scanner->shouldReceive('scan')->with($pathOne)->andReturn(FileScanResult::UNSAFE);
        $this->scanner->shouldReceive('scan')->with($pathTwo)->andReturn(FileScanResult::SAFE);
        $this->scanner->shouldReceive('scan')->with($pathThree)->andReturn(FileScanResult::TECHNICAL_ERROR);

        $result = iterator_to_array($this->strategy->process($file));

        $this->assertCount(1, $result);

        $this->assertInstanceOf(UploadedFile::class, $result[0]);

        $this->assertEquals($pathTwo, $result[0]->getPathname());
    }

    public function testCanProcessReturnsTrueOn7zExt(): void
    {
        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('7z');

        $this->assertTrue($this->strategy->canProcess($file));
    }

    public function testCanProcessReturnsTrueOnZipExt(): void
    {
        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('zip');

        $this->assertTrue($this->strategy->canProcess($file));
    }

    public function testCanProcessReturnsFalseOnPdf(): void
    {
        $path = 'my/path/file.pdf';

        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('pdf');
        $file->shouldReceive('getPathname')->andReturn($path);

        $this->mimeTypes
            ->shouldReceive('guessMimeType')
            ->once()
            ->with($path)
            ->andReturn('application/pdf');

        $this->assertFalse($this->strategy->canProcess($file));
    }
}
