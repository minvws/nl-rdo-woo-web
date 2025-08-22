<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Ingest\Content\Extractor\Tika\TikaService;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class EntityMetaDataExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected SubTypeIndexer&MockInterface $subTypeIndexer;
    protected TikaService&MockInterface $tika;
    protected WorkerStatsService&MockInterface $statsService;
    protected EntityWithFileInfo&MockInterface $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->tika = \Mockery::mock(TikaService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
    }

    public function testExtract(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturn($localPdfPath = '/path/to/file.pdf');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPdfPath) {
                $result = $closure();

                $this->assertSame($localPdfPath, $result);

                return true;
            }))
            ->andReturn($localPdfPath);

        $this->tika
            ->shouldReceive('extract')
            ->once()
            ->with($localPdfPath)
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tika', \Mockery::on(function (\Closure $closure) use ($tikaData) {
                $result = $closure();

                $this->assertSame($tikaData, $result);

                return true;
            }))
            ->andReturn($tikaData);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $this->subTypeIndexer
            ->shouldReceive('index')
            ->once()
            ->with($this->entity, \Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }));

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->getExtractor()->extract($this->entity, false);
    }

    public function testExtractWhenFetchingEntityFromStorageFails(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
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

        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = Uuid::v6());

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to save file to local storage', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $this->subTypeIndexer
            ->shouldReceive('index')
            ->once()
            ->with($this->entity, []);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->getExtractor()->extract($this->entity, false);
    }

    public function testExtractWhenIndexingOfEntityFails(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturn($localPdfPath = '/path/to/file.pdf');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPdfPath) {
                $result = $closure();

                $this->assertSame($localPdfPath, $result);

                return true;
            }))
            ->andReturn($localPdfPath);

        $this->tika
            ->shouldReceive('extract')
            ->once()
            ->with($localPdfPath)
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tika', \Mockery::on(function (\Closure $closure) use ($tikaData) {
                $result = $closure();

                $this->assertSame($tikaData, $result);

                return true;
            }))
            ->andReturn($tikaData);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = Uuid::v6());

        $this->subTypeIndexer
            ->shouldReceive('index')
            ->once()
            ->with($this->entity, \Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }))
            ->andThrow($exception = new \RuntimeException('Failed to create document'));

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to create document', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
                'exception' => $exception->getMessage(),
            ]);

        $this->getExtractor()->extract($this->entity, false);
    }

    private function getExtractor(): EntityMetaDataExtractor
    {
        return new EntityMetaDataExtractor(
            $this->logger,
            $this->entityStorageService,
            $this->subTypeIndexer,
            $this->tika,
            $this->statsService
        );
    }
}
