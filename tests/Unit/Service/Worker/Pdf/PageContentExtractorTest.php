<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\EntityWithFileInfo;
use App\Entity\FileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Service\Worker\Pdf\Tools\TikaService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PageContentExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected SubTypeIndexer&MockInterface $subTypeIndexer;
    protected TesseractService&MockInterface $tesseract;
    protected TikaService&MockInterface $tika;
    protected WorkerStatsService&MockInterface $statsService;
    protected EntityWithFileInfo&MockInterface $entity;
    protected FileInfo&MockInterface $fileInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->tesseract = \Mockery::mock(TesseractService::class);
        $this->tika = \Mockery::mock(TikaService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);
    }

    public function testExtract(): void
    {
        $this->fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->once()
            ->with($this->entity, $pageNr = 42)
            ->andReturn($localPath = 'localPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPath) {
                $result = $closure();

                $this->assertSame($localPath, $result);

                return true;
            }))
            ->andReturn($localPath);

        $this->tika
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tikaResult = ['X-TIKA:content' => 'lorem ipsum tika', 'name' => 'acme']);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tika', \Mockery::on(function (\Closure $closure) use ($tikaResult) {
                $result = $closure();

                $this->assertSame($tikaResult, $result);

                return true;
            }))
            ->andReturn($tikaResult);

        $this->tesseract
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tesseractResult = 'lorem ipsum tesseract');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tesseract', \Mockery::on(function (\Closure $closure) use ($tesseractResult) {
                $result = $closure();

                $this->assertSame($tesseractResult, $result);

                return true;
            }))
            ->andReturn($tesseractResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPath);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, "lorem ipsum tika\nlorem ipsum tesseract");

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.full.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->getExtractor()->extract($this->entity, $pageNr, false);
    }

    public function testExtractWhenDownloadingEntityFails(): void
    {
        $this->fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = \Mockery::mock(Uuid::class));

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->once()
            ->with($this->entity, $pageNr = 42)
            ->andReturnFalse();

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) {
                $result = $closure();

                $this->assertFalse($result);

                return true;
            }))
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to save entity to local storage', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
            ]);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, '');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.full.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->getExtractor()->extract($this->entity, $pageNr, false);
    }

    public function testExtractWhenUpdatingSubTypePageIndexFails(): void
    {
        $this->fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = \Mockery::mock(Uuid::class));

        $this->entityStorageService
            ->shouldReceive('downloadPage')
            ->once()
            ->with($this->entity, $pageNr = 42)
            ->andReturn($localPath = 'localPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPath) {
                $result = $closure();

                $this->assertSame($localPath, $result);

                return true;
            }))
            ->andReturn($localPath);

        $this->tika
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tikaResult = ['X-TIKA:content' => 'lorem ipsum tika', 'name' => 'acme']);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tika', \Mockery::on(function (\Closure $closure) use ($tikaResult) {
                $result = $closure();

                $this->assertSame($tikaResult, $result);

                return true;
            }))
            ->andReturn($tikaResult);

        $this->tesseract
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tesseractResult = 'lorem ipsum tesseract');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tesseract', \Mockery::on(function (\Closure $closure) use ($tesseractResult) {
                $result = $closure();

                $this->assertSame($tesseractResult, $result);

                return true;
            }))
            ->andReturn($tesseractResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPath);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, "lorem ipsum tika\nlorem ipsum tesseract")
            ->andThrow($thrownException = new \RuntimeException('indexPage failed'));

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.full.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to index page', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
                'exception' => $thrownException->getMessage(),
            ]);

        $this->getExtractor()->extract($this->entity, $pageNr, false);
    }

    private function getExtractor(): PageContentExtractor
    {
        return new PageContentExtractor(
            $this->logger,
            $this->entityStorageService,
            $this->subTypeIndexer,
            $this->tesseract,
            $this->tika,
            $this->statsService,
        );
    }
}
