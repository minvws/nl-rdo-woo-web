<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Preprocessor\Strategy;

use App\Domain\Upload\Extractor\Extractor;
use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class SevenZipFileStrategyTest extends UnitTestCase
{
    use IterableToGenerator;

    private Extractor&MockInterface $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = \Mockery::mock(Extractor::class);
    }

    public function testProcess(): void
    {
        $strategy = new SevenZipFileStrategy($this->extractor);

        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);

        $extractedFileOne = \Mockery::mock(\SplFileInfo::class);
        $extractedFileOne->shouldReceive('getPathname')->andReturn($pathOne = 'my/path/file_one.pdf');

        $extractedFileTwo = \Mockery::mock(\SplFileInfo::class);
        $extractedFileTwo->shouldReceive('getPathname')->andReturn($pathTwo = 'my/path/file_two.doc');

        $this->extractor
            ->shouldReceive('getFiles')
            ->once()
            ->with($file)
            ->andReturn($this->iterableToGenerator([$extractedFileOne, $extractedFileTwo]));

        $result = iterator_to_array($strategy->process($file));

        $this->assertCount(2, $result);

        $this->assertInstanceOf(UploadedFile::class, $result[0]);
        $this->assertInstanceOf(UploadedFile::class, $result[1]);

        $this->assertEquals($pathOne, $result[0]->getPathname());
        $this->assertEquals($pathTwo, $result[1]->getPathname());
    }

    public function testCanProcessReturnsTrue(): void
    {
        $strategy = new SevenZipFileStrategy($this->extractor);

        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('7z');

        $this->assertTrue($strategy->canProcess($file));
    }

    public function testCanProcessReturnsFalseOnPdf(): void
    {
        $strategy = new SevenZipFileStrategy($this->extractor);

        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('pdf');

        $this->assertFalse($strategy->canProcess($file));
    }

    public function testCanProcessReturnsFalseOnZipFile(): void
    {
        $strategy = new SevenZipFileStrategy($this->extractor);

        /** @var UploadedFile&MockInterface $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFileExtension')->andReturn('zip');

        $this->assertFalse($strategy->canProcess($file));
    }
}
