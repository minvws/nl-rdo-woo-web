<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf;

use Closure;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;

final class EntityMetaDataExtractorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private EntityStorageService&MockInterface $entityStorageService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private TikaService&MockInterface $tika;
    private EntityWithFileInfo&MockInterface $entity;
    private CacheInterface&MockInterface $cache;
    private FileInfo&MockInterface $fileInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $this->tika = Mockery::mock(TikaService::class);

        $this->fileInfo = Mockery::mock(FileInfo::class);
        $this->fileInfo->expects('getHash')->andReturn('foobar');

        $this->entity = Mockery::mock(EntityWithFileInfo::class);
        $this->entity->expects('getFileInfo')->andReturn($this->fileInfo);

        $this->cache = Mockery::mock(CacheInterface::class);
    }

    public function testExtractWithCacheMiss(): void
    {
        $this->cache
            ->expects('get')
            ->with('foobar-tika-metadata', Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $this->entityStorageService
            ->expects('downloadEntity')
            ->with($this->entity)
            ->andReturn($localPdfPath = '/path/to/file.pdf');

        $this->entity->expects('getId')->andReturn($id = Uuid::v6());

        $this->tika
            ->expects('extract')
            ->with(
                $localPdfPath,
                'application/pdf',
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($id): bool {
                        self::assertEquals($id->toRfc4122(), $context->id);

                        return true;
                    },
                ),
            )
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $this->entityStorageService
            ->expects('removeDownload')
            ->with($localPdfPath);

        $this->subTypeIndexer
            ->expects('index')
            ->with($this->entity, Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }));

        $this->getExtractor()->extract($this->entity);
    }

    public function testExtractWithCacheHit(): void
    {
        $this->cache
            ->expects('get')
            ->with('foobar-tika-metadata', Mockery::type('callable'))
            ->andReturn($metadata = ['key' => 'value']);

        $this->subTypeIndexer
            ->expects('index')
            ->with($this->entity, Mockery::on(function (array $data) use ($metadata) {
                $this->assertSame($metadata, $data);

                return true;
            }));

        $this->getExtractor()->extract($this->entity);
    }

    public function testExtractWhenFetchingEntityFromStorageFails(): void
    {
        $this->cache
            ->expects('get')
            ->with('foobar-tika-metadata', Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $this->entityStorageService
            ->expects('downloadEntity')
            ->with($this->entity)
            ->andReturnFalse();

        $this->entity
            ->expects('getId')
            ->andReturn($entityUuid = Uuid::v6());

        $this->logger
            ->expects('error')
            ->with('Failed to save file to local storage', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $this->subTypeIndexer
            ->expects('index')
            ->with($this->entity, []);

        $this->getExtractor()->extract($this->entity);
    }

    public function testExtractWhenIndexingOfEntityFails(): void
    {
        $this->cache
            ->expects('get')
            ->with('foobar-tika-metadata', Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $this->entityStorageService
            ->expects('downloadEntity')
            ->with($this->entity)
            ->andReturn($localPdfPath = '/path/to/file.pdf');

        $this->entity->expects('getId')->times(2)->andReturn($entityUuid = Uuid::v6());

        $this->tika
            ->expects('extract')
            ->with(
                $localPdfPath,
                'application/pdf',
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($entityUuid): bool {
                        self::assertEquals($entityUuid->toRfc4122(), $context->id);

                        return true;
                    },
                ),
            )
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $this->entityStorageService
            ->expects('removeDownload')
            ->with($localPdfPath);

        $this->subTypeIndexer
            ->expects('index')
            ->with($this->entity, Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }))
            ->andThrow($exception = new RuntimeException('Failed to create document'));

        $this->logger
            ->expects('error')
            ->with('Failed to create document', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
                'exception' => $exception->getMessage(),
            ]);

        $this->expectExceptionObject($exception);

        $this->getExtractor()->extract($this->entity);
    }

    private function getExtractor(): EntityMetaDataExtractor
    {
        return new EntityMetaDataExtractor(
            $this->logger,
            $this->entityStorageService,
            $this->subTypeIndexer,
            $this->tika,
            $this->cache,
        );
    }
}
