<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ArchiveExtractor;

use App\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use App\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use App\Domain\ArchiveExtractor\Factory\ZipArchiveFactory;
use App\Domain\ArchiveExtractor\ZipArchive;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class ZipArchiveTest extends UnitTestCase
{
    private vfsStreamDirectory $root;
    private ZipArchiveFactory&MockInterface $factory;
    private \ZipArchive&MockInterface $factoryResult;
    private \SplFileInfo&MockInterface $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        $this->factory = \Mockery::mock(ZipArchiveFactory::class);
        $this->factoryResult = \Mockery::mock(\ZipArchive::class);
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
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $archive = new ZipArchive($this->factory);
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
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveLogicException::forArchiveIsAlreadyOpen($this->file));

        $archive->open($this->file);
    }

    public function testOpenThrowsExceptionWhenCallingOpenReturnsFalse(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturnFalse();

        $archive = new ZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveRuntimeException::forFailedToOpenArchive($this->file));

        $archive->open($this->file);
    }

    public function testOpenThrowsExceptionWhenCallingOpenReturnsErrorCode(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(1337);

        $archive = new ZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveRuntimeException::forFailedToOpenArchive($this->file));

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
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->factoryResult
            ->shouldReceive('close')
            ->once()
            ->andReturnTrue();

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);

        $archive->close();
    }

    public function testCloseThrowsExceptionWhenArchiveIsNotOpen(): void
    {
        $archive = new ZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveLogicException::forNoOpenArchive());

        $archive->close();
    }

    public function testCloseThrowsWhenClosingFails(): void
    {
        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->factoryResult
            ->shouldReceive('close')
            ->once()
            ->andReturnFalse();

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveRuntimeException::forFailedToCloseArchive());

        $archive->close();
    }

    public function testExtract(): void
    {
        vfsStream::newDirectory($destination = 'destination')->at($this->root);
        $destinationPath = sprintf('%s/%s', $this->root->url(), $destination);

        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->factoryResult
            ->shouldReceive('extractTo')
            ->once()
            ->with($destinationPath)
            ->andReturnTrue();

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);
        $archive->extract($destinationPath);
    }

    public function testExtractThrowsExceptionWhenArchiveIsNotOpen(): void
    {
        $archive = new ZipArchive($this->factory);

        $this->expectExceptionObject(ArchiveLogicException::forNoOpenArchive());

        $archive->extract('destination');
    }

    public function testExtractThrowsExceptionWhenDestinationDirectoryIsMissing(): void
    {
        $destinationPath = sprintf('%s/does_not_exist', $this->root->url());

        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->factoryResult->shouldNotReceive('extractTo');

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveMissingDestinationException::create($destinationPath));

        $archive->extract($destinationPath);
    }

    public function testExtractThrowsExceptionWhenExtractionFails(): void
    {
        vfsStream::newDirectory($destination = 'destination')->at($this->root);
        $destinationPath = sprintf('%s/%s', $this->root->url(), $destination);

        $this->file
            ->shouldReceive('getPathname')
            ->andReturn($expectedPath = 'path');

        $this->factory
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->factoryResult);

        $this->factoryResult
            ->shouldReceive('open')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->factoryResult
            ->shouldReceive('extractTo')
            ->once()
            ->with($destinationPath)
            ->andReturnFalse();

        $archive = new ZipArchive($this->factory);
        $archive->open($this->file);

        $this->expectExceptionObject(ArchiveRuntimeException::forExtractionFailure());

        $archive->extract($destinationPath);
    }
}
