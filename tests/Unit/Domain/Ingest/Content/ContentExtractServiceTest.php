<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use Exception;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Content\FileReferenceInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function sprintf;

use const PHP_EOL;

class ContentExtractServiceTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorage;
    private LoggerInterface&MockInterface $logger;
    private ContentExtractorInterface&MockInterface $extractorA;
    private ContentExtractorInterface&MockInterface $extractorB;
    private ContentExtractService $service;

    protected function setUp(): void
    {
        $this->service = new ContentExtractService(
            $this->entityStorage = Mockery::mock(EntityStorageService::class),
            $this->logger = Mockery::mock(LoggerInterface::class),
            [
                $this->extractorA = Mockery::mock(ContentExtractorInterface::class),
                $this->extractorB = Mockery::mock(ContentExtractorInterface::class),
            ],
        );

        parent::setUp();
    }

    public function testGetExtractsReturnsFailureFileIsNotUploaded(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo->isUploaded')->andReturnFalse();
        $entity->expects('getId')->andReturn(Uuid::v6());

        $this->logger->expects('log');

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withFileContents($this->getFaker()->sentence()),
        );

        self::assertTrue($extracts->isFailure());
    }

    public function testGetExtractsReadsStreamPerExtractorWhenNoFileContentsAreSet(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $this->entityStorage
            ->expects('retrieveResourceEntity')
            ->twice()
            ->with($entity)
            ->andReturn(Utils::tryFopen('php://memory', 'r'), Utils::tryFopen('php://memory', 'r'));

        $contentA = "A line1\nA line 2";
        $this->extractorA->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->expects('supports')->with($entity)->andReturnTrue();
        $this->extractorA->expects('getContent')->with($entity, Mockery::any())->andReturnUsing(
            static function (EntityWithFileInfo $entity, FileReferenceInterface $fileReference) use ($contentA) {
                $fileReference->getStream();

                return $contentA;
            },
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->expects('supports')->with($entity)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($entity, Mockery::any())->andReturnUsing(
            static function (EntityWithFileInfo $entity, FileReferenceInterface $fileReference) use ($contentB) {
                $fileReference->getStream();

                return $contentB;
            },
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

    public function testExtractShouldCallOnlyExtractorMatchingByKey(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $this->extractorA->expects('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->expects('supports')->with($entity)->andReturnFalse();

        $contentB = "B line1\nB line 2";
        $this->extractorB->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->expects('supports')->with($entity)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($entity, Mockery::any())->andReturnUsing(
            static function (EntityWithFileInfo $entity, FileReferenceInterface $fileReference) use ($contentB) {
                $fileReference->getStream();

                return $contentB;
            },
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withFileContents($this->getFaker()->sentence()),
        );

        self::assertEquals(
            $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testGetExtractsForPageNumber(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $fileContents = $this->getFaker()->sentence();

        $contentA = "A line1\nA line 2";
        $this->extractorA->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->expects('supports')->with($entity)->andReturnTrue();
        $this->extractorA->expects('getContent')->with($entity, Mockery::any())->andReturnUsing(
            static function (EntityWithFileInfo $entity, FileReferenceInterface $fileReference) use ($contentA) {
                $fileReference->getStream();

                return $contentA;
            },
        );

        $contentB = "B line1\nB line 2";
        $this->extractorB->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TIKA);
        $this->extractorB->expects('supports')->with($entity)->andReturnTrue();
        $this->extractorB->expects('getContent')->with($entity, Mockery::any())->andReturnUsing(
            static function (EntityWithFileInfo $entity, FileReferenceInterface $fileReference) use ($contentB) {
                $fileReference->getStream();

                return $contentB;
            },
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withPageNumber(123)
                ->withFileContents($fileContents),
        );

        self::assertEquals(
            $contentA . PHP_EOL . $contentB,
            $extracts->getCombinedContent(),
        );
    }

    public function testSkipsExtractorIfNotEnabled(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($entityId = Uuid::v6());

        $this->logger->expects('log')->with(
            LogLevel::WARNING,
            'No content could be extracted',
            ['id' => $entityId, 'class' => $entity::class],
        );

        $contentExtractOptions = ContentExtractOptions::create()->withExtractor(ContentExtractorKey::TIKA);

        $this->extractorA->expects('getKey')->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->expects('supports')->never();
        $this->extractorA->expects('getContent')->never();

        $service = new ContentExtractService(
            $this->entityStorage,
            $this->logger,
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
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($entityId = Uuid::v6());

        $this->extractorA->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TESSERACT);
        $this->extractorA->expects('supports')->andReturn(true);
        $this->extractorA->expects('getContent')->andThrow(new Exception($exMessage = 'Extractor A failed'));

        $this->extractorB->expects('getKey')->never();
        $this->extractorB->expects('supports')->never();
        $this->extractorB->expects('getContent')->never();

        $this->logger->expects('log')->with(
            LogLevel::ERROR,
            sprintf('Content extract error: %s', $exMessage),
            ['id' => $entityId, 'class' => $entity::class],
        );

        $extracts = $this->service->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withFileContents($this->getFaker()->sentence()),
        );

        self::assertTrue($extracts->isFailure(), 'Extracts is marked as failure');
    }

    public function testGetExtractsDoesNotSetHashWhenItIsNull(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $content = $this->getFaker()->sentence();

        $extractor = Mockery::mock(ContentExtractorInterface::class);
        $extractor->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TIKA);
        $extractor->expects('supports')->with($entity)->andReturnTrue();
        $extractor->expects('getContent')->with($entity, Mockery::any())->andReturn($content);

        $entityStorage = Mockery::mock(EntityStorageService::class);

        $service = new ContentExtractService(
            $entityStorage,
            Mockery::mock(LoggerInterface::class),
            [$extractor],
        );

        $extracts = $service->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withFileContents($this->getFaker()->sentence()),
        );

        self::assertEquals($content, $extracts->getCombinedContent());
    }
}
