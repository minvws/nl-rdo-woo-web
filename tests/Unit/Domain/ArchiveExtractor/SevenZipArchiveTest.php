<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ArchiveExtractor;

use App\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use App\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use App\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;
use App\Domain\ArchiveExtractor\SevenZipArchive;
use App\Tests\Unit\UnitTestCase;
use Archive7z\Archive7z;
use Mockery\MockInterface;

final class SevenZipArchiveTest extends UnitTestCase
{
    private const TIMEOUT = 60.0 * 5;

    private SevenZipArchiveFactory&MockInterface $factory;
    private Archive7z&MockInterface $factoryResult;
    private \SplFileInfo&MockInterface $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = \Mockery::mock(SevenZipArchiveFactory::class);
        $this->factoryResult = \Mockery::mock(Archive7z::class);
        $this->file = \Mockery::mock(\SplFileInfo::class);
    }

    public function testOpen(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);
    }

    public function testOpenThrowsExceptionWhenCalledMultipleTimes(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
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
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
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
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('setOutputDirectory')
            ->once()
            ->with($destination = 'destination');

        $this->factoryResult
            ->shouldReceive('extract')
            ->once();

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
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('setOutputDirectory')
            ->once()
            ->with($destination = 'destination')
            ->andThrow(new \Exception('Somthing went wrong'));

        $this->factoryResult->shouldNotReceive('extract');

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveMissingDestinationException::create($destination));

        $archive->extract($destination);
    }

    public function testExtractThrowsExceptionWhenExtractionFails(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->with($expectedPath, self::TIMEOUT)
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('setOutputDirectory')
            ->once()
            ->with($destination = 'destination');

        $this->factoryResult
            ->shouldReceive('extract')
            ->once()
            ->andThrow($ex = new \Exception('Somthing went wrong'));

        $archive = new SevenZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveRuntimeException::forExtractionFailure($ex));

        $archive->extract($destination);
    }
}
