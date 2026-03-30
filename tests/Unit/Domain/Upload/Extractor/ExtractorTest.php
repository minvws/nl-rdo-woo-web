<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Extractor;

use ArrayIterator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\ArchiveExtractor\ArchiveInterface;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use Shared\Domain\Upload\Extractor\Extractor;
use Shared\Domain\Upload\Extractor\ExtractorException;
use Shared\Domain\Upload\Extractor\ExtractorFinderFactory;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function iterator_to_array;

final class ExtractorTest extends UnitTestCase
{
    private ArchiveInterface&MockInterface $archive;
    private LocalFilesystem&MockInterface $filesystem;
    private ExtractorFinderFactory&MockInterface $finderFactory;
    private SplFileInfo&MockInterface $file;
    private Finder&MockInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->archive = Mockery::mock(ArchiveInterface::class);
        $this->filesystem = Mockery::mock(LocalFilesystem::class);
        $this->finderFactory = Mockery::mock(ExtractorFinderFactory::class);
        $this->file = Mockery::mock(SplFileInfo::class);
        $this->finder = Mockery::mock(Finder::class);
    }

    public function testGetFiles(): void
    {
        $this->archive
            ->expects('open')
            ->with($this->file)
            ->andReturnTrue();

        $this->filesystem
            ->expects('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $this->archive
            ->expects('extract')
            ->with($tempDir);

        $this->finderFactory
            ->expects('create')
            ->with($tempDir)
            ->andReturn($this->finder);

        $this->finder
            ->expects('getIterator')
            ->andReturn(new ArrayIterator($expectedResult = [Mockery::mock(SplFileInfo::class)]));

        $this->archive
            ->expects('close');

        $this->filesystem
            ->expects('deleteDirectory')
            ->with($tempDir);

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        $result = $extractor->getFiles($this->file);

        $this->assertSame($expectedResult, iterator_to_array($result, false));
    }

    public function testGetFilesWrapsExceptionThrownByArchiveOpen(): void
    {
        $this->file
            ->expects('getPathname')
            ->times(3)
            ->andReturn('my path');

        $this->archive
            ->expects('open')
            ->with($this->file)
            ->andThrow($ex = ArchiveRuntimeException::forFailedToOpenArchive($this->file));

        $this->expectExceptionObject(ExtractorException::forFailingToOpenArchive($this->file, $ex));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }

    public function testGetFilesThrowsExceptionIfTempDirCouldNotBeCreated(): void
    {
        $this->file
            ->expects('getPathname')
            ->times(2)
            ->andReturn('my path');

        $this->archive
            ->expects('open')
            ->with($this->file);

        $this->filesystem
            ->expects('createTempDir')
            ->andReturnFalse();

        $this->archive
            ->expects('close');

        $this->expectExceptionObject(ExtractorException::forFailingToCreateTempDir($this->file));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }

    public function testGetFilesWrapsThrownArchiveExceptions(): void
    {
        $this->file
            ->expects('getPathname')
            ->times(2)
            ->andReturn('my path');

        $this->archive
            ->expects('open')
            ->with($this->file);

        $this->filesystem
            ->expects('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $this->archive
            ->expects('extract')
            ->with($tempDir)
            ->andThrow($ex = ArchiveRuntimeException::forExtractionFailure());

        $this->archive
            ->expects('close');

        $this->filesystem
            ->expects('deleteDirectory')
            ->with($tempDir);

        $this->expectExceptionObject(ExtractorException::forFailingToExtractFiles($this->file, $tempDir, $ex));

        $extractor = new Extractor($this->archive, $this->filesystem, $this->finderFactory);
        iterator_to_array($extractor->getFiles($this->file), false);
    }
}
