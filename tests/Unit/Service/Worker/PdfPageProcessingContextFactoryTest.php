<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContextFactory;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Stats\WorkerStatsService;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class PdfPageProcessingContextFactoryTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorage;
    private WorkerStatsService&MockInterface $statsService;
    private LocalFilesystem&MockInterface $localFilesytem;
    private FileInfo&MockInterface $fileInfo;
    private PdfPageProcessingContextFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityStorage = \Mockery::mock(EntityStorageService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->localFilesytem = \Mockery::mock(LocalFilesystem::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);
        $this->factory = new PdfPageProcessingContextFactory(
            $this->entityStorage,
            $this->statsService,
            $this->localFilesytem,
        );
    }

    public function testCreateContentThrowsExceptionWhenDownloadFails(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->andReturnTrue();

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $pageNumber = 123;

        $this->statsService
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
        $this->fileInfo->shouldReceive('isUploaded')->andReturnTrue();

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $localFile = '/local/file.pdf';
        $pageNumber = 123;

        $this->statsService
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
        $this->fileInfo->shouldReceive('isUploaded')->andReturnTrue();

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $localFile = '/local/file.pdf';
        $tempDir = '/tmp/dir';
        $pageNumber = 123;

        $this->statsService
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

        self::assertNotNull($context);
        self::assertEquals($entity, $context->getEntity());
        self::assertEquals($pageNumber, $context->getPageNumber());
        self::assertEquals($localFile, $context->getLocalDocument());
        self::assertEquals($tempDir, $context->getWorkDirPath());
    }

    public function testCreateContextOnEntityWithoutUploadedFile(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->andReturnFalse();

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $pageNumber = 123;

        $context = $this->factory->createContext($entity, $pageNumber);

        self::assertNull($context);
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
