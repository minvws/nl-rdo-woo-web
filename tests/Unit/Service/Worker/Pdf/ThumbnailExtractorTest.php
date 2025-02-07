<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmThumbnailResult;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Uid\Uuid;

final class ThumbnailExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected ThumbnailStorageService&MockInterface $thumbnailStorageService;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected LocalFilesystem&MockInterface $localFilesystem;
    protected PdftoppmService&MockInterface $pdftoppmService;
    protected EntityWithFileInfo&MockInterface $entity;
    protected vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->localFilesystem = \Mockery::mock(LocalFilesystem::class);
        $this->pdftoppmService = \Mockery::mock(PdftoppmService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);

        $this->root = vfsStream::setup();
    }

    public function testExtract(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'vfs://root/temp');

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->with($this->entity, $pageNr = 42)
            ->once()
            ->andReturn($sourcePdf = '/source/file.pdf');

        $targetPath = $tempDir . '/thumb';

        $pdftoppmResult = new PdftoppmThumbnailResult(
            exitCode: 0,
            params: [],
            errorMessage: null,
            sourcePdf: $sourcePdf,
            targetPath: $targetPath,
        );

        $this->pdftoppmService
            ->shouldReceive('createThumbnail')
            ->with($sourcePdf, $targetPath)
            ->once()
            ->andReturn($pdftoppmResult = $pdftoppmResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->with($sourcePdf)
            ->once();

        vfsStream::create(['temp' => ['thumb.png' => '']]);

        $this->thumbnailStorageService
            ->shouldReceive('store')
            ->with($this->entity, \Mockery::on(function (File $file) use ($targetPath) {
                $this->assertSame($targetPath . '.png', $file->getPathname());

                return true;
            }), $pageNr)
            ->once();

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->with($tempDir)
            ->once();

        $this->getInstance()->extract($this->entity, $pageNr, true);
    }

    public function testExtractWhenCreatingTempDirFails(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturnFalse();

        $this->entityStorageService->shouldNotReceive('downloadPage');
        $this->pdftoppmService->shouldNotReceive('createThumbnail');

        $this->getInstance()->extract($this->entity, 42, true);
    }

    public function testExtractWhenDownloadingFails(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'vfs://root/temp');

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->with($this->entity, $pageNr = 42)
            ->once()
            ->andReturnFalse();

        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = Uuid::v6());

        $this->logger
            ->shouldReceive('error')
            ->with('Cannot download page from storage', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
            ])
            ->once();

        $this->getInstance()->extract($this->entity, $pageNr, true);
    }

    public function testExtractWhenCreatingThumbnailFails(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'vfs://root/temp');

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->with($this->entity, $pageNr = 42)
            ->once()
            ->andReturn($sourcePdf = '/source/file.pdf');

        $targetPath = $tempDir . '/thumb';

        $pdftoppmResult = new PdftoppmThumbnailResult(
            exitCode: 1,
            params: [],
            errorMessage: $errorMessage = 'My error message',
            sourcePdf: $sourcePdf,
            targetPath: $targetPath,
        );

        $this->pdftoppmService
            ->shouldReceive('createThumbnail')
            ->with($sourcePdf, $targetPath)
            ->once()
            ->andReturn($pdftoppmResult = $pdftoppmResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->with($sourcePdf)
            ->once();

        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = Uuid::v6());

        $this->logger
            ->shouldReceive('error')
            ->with('Failed to create thumbnail for entity', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
                'sourcePath' => $pdftoppmResult->sourcePdf,
                'targetPath' => $pdftoppmResult->targetPath,
                'error_output' => $pdftoppmResult->errorMessage,
            ])
            ->once();

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->with($tempDir)
            ->once();

        $this->getInstance()->extract($this->entity, $pageNr, true);
    }

    private function getInstance(): ThumbnailExtractor
    {
        return new ThumbnailExtractor(
            $this->logger,
            $this->thumbnailStorageService,
            $this->entityStorageService,
            $this->localFilesystem,
            $this->pdftoppmService,
        );
    }
}
