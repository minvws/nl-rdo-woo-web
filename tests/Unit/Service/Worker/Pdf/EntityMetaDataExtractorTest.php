<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf;

use Closure;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Psr\Http\Message\StreamInterface;
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
use Webmozart\Assert\InvalidArgumentException;

use function sprintf;

final class EntityMetaDataExtractorTest extends UnitTestCase
{
    public function testExtractWithCacheMiss(): void
    {
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($id = Uuid::v6());

        $cache = Mockery::mock(CacheInterface::class);
        $cache
            ->expects('get')
            ->with(sprintf('%s-tika-metadata', $hash), Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($entity)
            ->andReturn($source = Utils::tryFopen('php://memory', 'r'));

        $tika = Mockery::mock(TikaService::class);
        $tika
            ->expects('extract')
            ->with(
                Mockery::on(static function (StreamInterface $stream) use ($source): bool {
                    return $stream->detach() === $source;
                }),
                'application/pdf',
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($id): bool {
                        self::assertEquals($id->toRfc4122(), $context->id);

                        return true;
                    },
                ),
            )
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $subTypeIndexer
            ->expects('index')
            ->with($entity, Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }));

        $extractor = new EntityMetaDataExtractor(
            Mockery::mock(LoggerInterface::class),
            $entityStorageService,
            $subTypeIndexer,
            $tika,
            $cache,
        );
        $extractor->extract($entity);
    }

    public function testExtractWithCacheHit(): void
    {
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $cache = Mockery::mock(CacheInterface::class);
        $cache
            ->expects('get')
            ->with(sprintf('%s-tika-metadata', $hash), Mockery::type('callable'))
            ->andReturn($metadata = ['key' => 'value']);

        $subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $subTypeIndexer
            ->expects('index')
            ->with($entity, Mockery::on(function (array $data) use ($metadata) {
                $this->assertSame($metadata, $data);

                return true;
            }));

        $extractor = new EntityMetaDataExtractor(
            Mockery::mock(LoggerInterface::class),
            Mockery::mock(EntityStorageService::class),
            $subTypeIndexer,
            Mockery::mock(TikaService::class),
            $cache,
        );
        $extractor->extract($entity);
    }

    public function testExtractWhenFetchingEntityFromStorageFails(): void
    {
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity
            ->expects('getId')
            ->andReturn($entityUuid = Uuid::v6());

        $cache = Mockery::mock(CacheInterface::class);
        $cache
            ->expects('get')
            ->with(sprintf('%s-tika-metadata', $hash), Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($entity)
            ->andReturnNull();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->expects('error')
            ->with('Failed to read file from storage', [
                'id' => $entityUuid,
                'class' => $entity::class,
            ]);

        $subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $subTypeIndexer
            ->expects('index')
            ->with($entity, []);

        $extractor = new EntityMetaDataExtractor(
            $logger,
            $entityStorageService,
            $subTypeIndexer,
            Mockery::mock(TikaService::class),
            $cache,
        );
        $extractor->extract($entity);
    }

    public function testExtractWhenIndexingOfEntityFails(): void
    {
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->times(2)->andReturn($entityUuid = Uuid::v6());

        $cache = Mockery::mock(CacheInterface::class);
        $cache
            ->expects('get')
            ->with(sprintf('%s-tika-metadata', $hash), Mockery::type('callable'))
            ->andReturnUsing(static fn (string $key, Closure $closure) => $closure());

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($entity)
            ->andReturn($source = Utils::tryFopen('php://memory', 'r'));

        $tika = Mockery::mock(TikaService::class);
        $tika
            ->expects('extract')
            ->with(
                Mockery::on(static function (StreamInterface $stream) use ($source): bool {
                    return $stream->detach() === $source;
                }),
                'application/pdf',
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($entityUuid): bool {
                        self::assertEquals($entityUuid->toRfc4122(), $context->id);

                        return true;
                    },
                ),
            )
            ->andReturn($tikaData = ['X-TIKA:content' => 'lorem ipsum', 'key' => 'value']);

        $subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $subTypeIndexer
            ->expects('index')
            ->with($entity, Mockery::on(function (array $data) use ($tikaData) {
                $expectedData = $tikaData;

                unset($expectedData['X-TIKA:content']);

                $this->assertSame($expectedData, $data);

                return true;
            }))
            ->andThrow($exception = new RuntimeException('Failed to create document'));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->expects('error')
            ->with('Failed to create document', [
                'id' => $entityUuid,
                'class' => $entity::class,
                'exception' => $exception->getMessage(),
            ]);

        $extractor = new EntityMetaDataExtractor(
            $logger,
            $entityStorageService,
            $subTypeIndexer,
            $tika,
            $cache,
        );

        $this->expectExceptionObject($exception);

        $extractor->extract($entity);
    }

    public function testExtractThrowsExceptionForEntityWithoutHash(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturnNull();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $extractor = new EntityMetaDataExtractor(
            Mockery::mock(LoggerInterface::class),
            Mockery::mock(EntityStorageService::class),
            Mockery::mock(SubTypeIndexer::class),
            Mockery::mock(TikaService::class),
            Mockery::mock(CacheInterface::class),
        );

        $this->expectException(InvalidArgumentException::class);

        $extractor->extract($entity);
    }
}
