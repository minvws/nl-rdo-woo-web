<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content;

use App\Domain\Ingest\Content\ContentExtractCacheKeyGenerator;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\LazyFileReference;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Uid\Uuid;

class ContentExtractServiceTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorage;
    private LoggerInterface&MockInterface $logger;
    private ContentExtractorInterface&MockInterface $extractorA;
    private ContentExtractorInterface&MockInterface $extractorB;
    private ContentExtractService $service;

    public function setUp(): void
    {
        $this->service = new ContentExtractService(
            $this->entityStorage = \Mockery::mock(EntityStorageService::class),
            $this->logger = \Mockery::mock(LoggerInterface::class),
            new TagAwareAdapter(new ArrayAdapter()),
            new ContentExtractCacheKeyGenerator(),
            [
                $this->extractorA = \Mockery::mock(ContentExtractorInterface::class),
                $this->extractorB = \Mockery::mock(ContentExtractorInterface::class),
            ],
        );

        parent::setUp();
    }

    public function testGetExtractsReturnsFailureFileIsNotUploaded(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo->isUploaded')->andReturnFalse();
        $entity->expects('getId')->andReturn(Uuid::v6());

        $this->logger->expects('log');

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertTrue($extracts->isFailure());
    }

    public function testGetExtractsAddHashWhenItIsNull(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturnNull();
        $fileInfo->shouldReceive('getHash')->andReturn('1295d266e56f7e3c42a2b5163bf0c4c7b4c3fb7640e7d3f14c65c85343b81ca4');

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $this->entityStorage
            ->expects('downloadEntity')
            ->with($entity)
            ->andReturn($localFile = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt');

        $this->entityStorage->expects('setHash')->with($entity, $localFile);

        $contentA = "A line1\nA line 2";
        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorA->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentA) {
                $fileReference->getPath();

                return $contentA;
            }
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->shouldReceive('getKey')->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentB) {
                $fileReference->getPath();

                return $contentB;
            }
        );

        $this->entityStorage->expects('removeDownload')->with($localFile);

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testGetExtractsUsesCacheForSecondRun(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturnNull();
        $fileInfo->shouldReceive('getHash')->andReturn('1295d266e56f7e3c42a2b5163bf0c4c7b4c3fb7640e7d3f14c65c85343b81ca4');

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $this->entityStorage
            ->expects('downloadEntity')
            ->with($entity)
            ->andReturn($localFile = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt');

        $this->entityStorage->expects('setHash')->with($entity, $localFile);

        $contentA = "A line1\nA line 2";
        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorA->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentA) {
                $fileReference->getPath();

                return $contentA;
            }
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->shouldReceive('getKey')->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentB) {
                $fileReference->getPath();

                return $contentB;
            }
        );

        $this->entityStorage->expects('removeDownload')->with($localFile);

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testDoubleExtractShouldCallExtractorTwiceWhenRefreshIsTrueForTheSecondRun(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturnNull();
        $fileInfo->shouldReceive('getHash')->andReturn('1295d266e56f7e3c42a2b5163bf0c4c7b4c3fb7640e7d3f14c65c85343b81ca4');

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $this->entityStorage
            ->expects('downloadEntity')
            ->twice()
            ->with($entity)
            ->andReturn($localFile = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt');

        $this->entityStorage->expects('setHash')->with($entity, $localFile);

        $contentA = "A line1\nA line 2";
        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorA->expects('getContent')->twice()->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentA) {
                $fileReference->getPath();

                return $contentA;
            }
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->shouldReceive('getKey')->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorB->expects('getContent')->twice()->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentB) {
                $fileReference->getPath();

                return $contentB;
            }
        );

        $this->entityStorage->expects('removeDownload')->twice()->with($localFile);

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors()->withRefresh(),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testExtractShouldCallOnlyExtractorMatchingByKey(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturnNull();
        $fileInfo->shouldReceive('getHash')->andReturn('1295d266e56f7e3c42a2b5163bf0c4c7b4c3fb7640e7d3f14c65c85343b81ca4');

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $this->entityStorage
            ->expects('downloadEntity')
            ->with($entity)
            ->andReturn($localFile = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt');

        $this->entityStorage->expects('setHash')->with($entity, $localFile);

        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->with($fileInfo)->andReturnFalse();

        $contentB = "B line1\nB line 2";
        $this->extractorB->shouldReceive('getKey')->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentB) {
                $fileReference->getPath();

                return $contentB;
            }
        );

        $this->entityStorage->expects('removeDownload')->with($localFile);

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertEquals(
            $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testGetExtractsForPageNumber(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturnNull();
        $fileInfo->shouldReceive('getHash')->andReturn('1295d266e56f7e3c42a2b5163bf0c4c7b4c3fb7640e7d3f14c65c85343b81ca4');

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $dummyFile = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        $dummyDocumentFile = 'foo/bar.txt';

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturn($dummyDocumentFile);
        $this->entityStorage->expects('setHash')->with($entity, $dummyDocumentFile);
        $this->entityStorage->expects('removeDownload')->with($dummyDocumentFile);

        $this->entityStorage->expects('downloadPage')->with($entity, 123)->andReturn($dummyFile);

        $contentA = "A line1\nA line 2";
        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorA->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentA) {
                $fileReference->getPath();

                return $contentA;
            }
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->shouldReceive('getKey')->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->shouldReceive('supports')->with($fileInfo)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($fileInfo, \Mockery::any())->andReturnUsing(
            function (FileInfo $fileInfo, LazyFileReference $fileReference) use ($contentB) {
                $fileReference->getPath();

                return $contentB;
            }
        );

        $this->entityStorage->expects('removeDownload')->with($dummyFile);

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors()->withPageNumber(123),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testSkipsExtractorIfNotEnabled(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->once()->andReturn('uuid');

        /** @var EntityWithFileInfo&MockInterface $entity */
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn($entityId = Uuid::v6());

        $this->logger->shouldReceive('log')->with(
            LogLevel::WARNING,
            'No content could be extracted',
            ['id' => $entityId, 'class' => $entity::class]
        );

        $contentExtractOptions = ContentExtractOptions::create()->withExtractor(ContentExtractorKey::TIKA);

        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldNotReceive('supports');
        $this->extractorA->shouldNotReceive('getContent');

        $service = new ContentExtractService(
            $this->entityStorage,
            $this->logger,
            new TagAwareAdapter(new ArrayAdapter()),
            new ContentExtractCacheKeyGenerator(),
            [
                $this->extractorA,
            ],
        );

        $extracts = $service->getExtracts(
            $entity,
            $contentExtractOptions,
        );

        self::assertTrue($extracts->isEmpty());
    }

    public function testItMarksExtractorsAsFailureWhenItFails(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getHash')->andReturn('uuid');

        /** @var EntityWithFileInfo&MockInterface $entity */
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn($entityId = Uuid::v6());
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitycachekey');

        $this->logger->shouldReceive('log')->with(
            LogLevel::WARNING,
            'No content could be extracted',
            ['id' => $entityId, 'class' => $entity::class]
        );

        $this->extractorA->shouldReceive('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->shouldReceive('supports')->andReturn(true);
        $this->extractorA->shouldReceive('getContent')->andThrow(new \Exception($exMessage = 'Extractor A failed'));

        $this->extractorB->shouldNotReceive('getKey');
        $this->extractorB->shouldNotReceive('supports');
        $this->extractorB->shouldNotReceive('getContent');

        $this->logger->shouldReceive('log')->with(
            LogLevel::ERROR,
            sprintf('Content extract error: %s', $exMessage),
            ['id' => $entityId, 'class' => $entity::class]
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        self::assertTrue($extracts->isFailure(), 'Extracts is marked as failure');
    }
}
