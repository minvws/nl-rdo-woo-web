<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Extractor;

use App\Domain\ArchiveExtractor\ArchiveInterface;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use App\Domain\Upload\Extractor\Extractor;
use App\Domain\Upload\Extractor\ExtractorException;
use App\Domain\Upload\Extractor\ExtractorFinderFactory;
use App\Service\Storage\LocalFilesystem;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Finder\Finder;

final class ExtractorTest extends UnitTestCase
{
    private ArchiveInterface&MockInterface $archive;
    private LocalFilesystem&MockInterface $filesystem;
    private ExtractorFinderFactory&MockInterface $finderFactory;
    private \SplFileInfo&MockInterface $file;
    private Finder&MockInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->archive = \Mockery::mock(ArchiveInterface::class);
        $this->filesystem = \Mockery::mock(LocalFilesystem::class);
        $this->finderFactory = \Mockery::mock(ExtractorFinderFactory::class);
        $this->file = \Mockery::mock(\SplFileInfo::class);
        $this->finder = \Mockery::mock(Finder::class);
    }

    public function testGetFiles(): void
    {
        $this->archive
            ->shouldReceive('open')
            ->once()
            ->with($this->file)
            ->andReturnTrue();

        $this->filesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'tempDir');

        $this->archive
            ->shouldReceive('extract')
            ->once()
            ->with($tempDir);

        $this->finderFactory
            ->shouldReceive('create')
            ->once()
            ->with($tempDir)
            ->andReturn($this->finder);

        $this->finder
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn(new \ArrayIterator($expectedResult = [\Mockery::mock(\SplFileInfo::class)]));

        $this->archive
            ->shouldReceive('close')
            ->once();

        $this->filesystem
            ->shouldReceive('deleteDirectory')
            ->once()
            ->with($tempDir);

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        $result = $extractor->getFiles($this->file);

        $this->assertSame($expectedResult, iterator_to_array($result, false));
    }

    public function testGetFilesWrapsExceptionThrownByArchiveOpen(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn('my path');

        $this->archive
            ->shouldReceive('open')
            ->with($this->file)
            ->andThrow($ex = ArchiveRuntimeException::forFailedToOpenArchive($this->file));

        $this->expectExceptionObject(ExtractorException::forFailingToOpenArchive($this->file, $ex));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }

    public function testGetFilesThrowsExceptionIfTempDirCouldNotBeCreated(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn('my path');

        $this->archive
            ->shouldReceive('open')
            ->once()
            ->with($this->file);

        $this->filesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturnFalse();

        $this->archive
            ->shouldReceive('close')
            ->once();

        $this->expectExceptionObject(ExtractorException::forFailingToCreateTempDir($this->file));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }

    public function testGetFilesWrapsThrownArchiveExceptions(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn('my path');

        $this->archive
            ->shouldReceive('open')
            ->once()
            ->with($this->file);

        $this->filesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'tempDir');

        $this->archive
            ->shouldReceive('extract')
            ->once()
            ->with($tempDir)
            ->andThrow($ex = ArchiveRuntimeException::forExtractionFailure());

        $this->archive
            ->shouldReceive('close')
            ->once();

        $this->filesystem
            ->shouldReceive('deleteDirectory')
            ->once()
            ->with($tempDir);

        $this->expectExceptionObject(ExtractorException::forFailingToExtractFiles($this->file, $tempDir, $ex));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }
}
