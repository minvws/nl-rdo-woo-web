<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\ArchiveExtractor;

use Archive7z\Archive7z;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use Shared\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;
use Shared\Domain\ArchiveExtractor\SevenZipArchive;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;

final class SevenZipArchiveTest extends UnitTestCase
{
    private const float TIMEOUT = 60.0 * 5;

    private SevenZipArchiveFactory&MockInterface $factory;
    private Archive7z&MockInterface $factoryResult;
    private SplFileInfo&MockInterface $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Mockery::mock(SevenZipArchiveFactory::class);
        $this->factoryResult = Mockery::mock(Archive7z::class);
        $this->file = Mockery::mock(SplFileInfo::class);
    }

    public function testOpen(): void
    {
        $this->file
            ->expects('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);
    }

    public function testOpenThrowsExceptionWhenCalledMultipleTimes(): void
    {
        $this->file
            ->expects('getPathname')
            ->times(3)
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveLogicException::forArchiveIsAlreadyOpen($this->file));

        $archive->open($this->file);
    }

    public function testClose(): void
    {
        $this->file
            ->expects('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $archive->close();
    }

    public function testCloseThrowsExceptionWhenArchiveIsNotOpen(): void
    {
        $archive = new SevenZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveLogicException::forNoOpenArchive());

        $archive->close();
    }

    public function testExtract(): void
    {
        $this->file
            ->expects('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->expects('setOutputDirectory')
            ->with($destination = 'destination');

        $this->factoryResult
            ->expects('extract');

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);
        $archive->extract($destination);
    }

    public function testExtractThrowsExceptionWhenArchiveIsNotOpen(): void
    {
        $archive = new SevenZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveLogicException::forNoOpenArchive());

        $archive->extract('destination');
    }

    public function testExtractThrowsExceptionWhenDestinationDirectoryIsMissing(): void
    {
        $this->file
            ->expects('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->expects('setOutputDirectory')
            ->with($destination = 'destination')
            ->andThrow(new Exception('Somthing went wrong'));

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveMissingDestinationException::create($destination));

        $archive->extract($destination);
    }

    public function testExtractThrowsExceptionWhenExtractionFails(): void
    {
        $this->file
            ->expects('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->expects('create')
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->expects('setOutputDirectory')
            ->with($destination = 'destination');

        $this->factoryResult
            ->expects('extract')
            ->andThrow($ex = new Exception('Somthing went wrong'));

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveRuntimeException::forExtractionFailure($ex));

        $archive->extract($destination);
    }
}
