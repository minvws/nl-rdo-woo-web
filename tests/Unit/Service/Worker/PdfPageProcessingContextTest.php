<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Domain\Ingest\Process\PdfPage\PdfPageException;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContextFactory;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class PdfPageProcessingContextTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorage;
    private WorkerStatsService&MockInterface $workerStats;
    private LocalFilesystem&MockInterface $localFilesytem;
    private PdfPageProcessingContextFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityStorage = \Mockery::mock(EntityStorageService::class);
        $this->workerStats = \Mockery::mock(WorkerStatsService::class);
        $this->localFilesytem = \Mockery::mock(LocalFilesystem::class);
        $this->factory = new PdfPageProcessingContextFactory(
            $this->entityStorage,
            $this->workerStats,
            $this->localFilesytem,
        );
    }

    public function testCreateContentThrowsExceptionWhenDownloadFails(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $pageNumber = 123;

        $this->workerStats
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }))
            ->andReturnFalse();

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturnFalse();

        $this->expectException(PdfPageException::class);

        $this->factory->createContext($entity, $pageNumber);
    }

    public function testCreateContentThrowsExceptionWhenTempDirCannotBeCreated(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $localFile = '/local/file.pdf';
        $pageNumber = 123;

        $this->workerStats
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localFile) {
                $result = $closure();

                self::assertEquals($localFile, $result);

                return true;
            }))
            ->andReturn($localFile);

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturn($localFile);

        $this->localFilesytem->expects('createTempDir')->andReturnFalse();

        $this->expectException(PdfPageException::class);

        $this->factory->createContext($entity, $pageNumber);
    }

    public function testCreateContentSuccessful(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $localFile = '/local/file.pdf';
        $tempDir = '/tmp/dir';
        $pageNumber = 123;

        $this->workerStats
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localFile) {
                $result = $closure();

                self::assertEquals($localFile, $result);

                return true;
            }))
            ->andReturn($localFile);

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturn($localFile);
        $this->localFilesytem->expects('createTempDir')->andReturn($tempDir);

        $context = $this->factory->createContext($entity, $pageNumber);

        self::assertEquals($entity, $context->getEntity());
        self::assertEquals($pageNumber, $context->getPageNumber());
        self::assertEquals($localFile, $context->getLocalDocument());
        self::assertEquals($tempDir, $context->getWorkDirPath());
    }

    public function testTeardown(): void
    {
        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $context->shouldReceive('getLocalDocument')->andReturn($localDocument = '/local/file.pdf');
        $context->shouldReceive('getWorkDirPath')->andReturn($workDir = '/temp/dir');

        $this->entityStorage->expects('removeDownload')->with($localDocument);
        $this->localFilesytem->expects('deleteDirectory')->with($workDir);

        $this->factory->teardown($context);
    }
}
