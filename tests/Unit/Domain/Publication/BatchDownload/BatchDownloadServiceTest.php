<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadDispatcher;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\BatchDownload\BatchDownloadStorage;
use App\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class BatchDownloadServiceTest extends MockeryTestCase
{
    private BatchDownloadRepository&MockInterface $batchRepository;
    private BatchDownloadDispatcher&MockInterface $dispatcher;
    private BatchDownloadStorage&MockInterface $storage;
    private BatchDownloadTypeInterface&MockInterface $typeA;
    private BatchDownloadTypeInterface&MockInterface $typeB;
    private BatchDownloadService $service;

    public function setUp(): void
    {
        $this->batchRepository = \Mockery::mock(BatchDownloadRepository::class);
        $this->dispatcher = \Mockery::mock(BatchDownloadDispatcher::class);
        $this->storage = \Mockery::mock(BatchDownloadStorage::class);
        $this->typeA = \Mockery::mock(BatchDownloadTypeInterface::class);
        $this->typeB = \Mockery::mock(BatchDownloadTypeInterface::class);

        $this->service = new BatchDownloadService(
            $this->batchRepository,
            $this->dispatcher,
            $this->storage,
            [$this->typeA, $this->typeB],
        );

        parent::setUp();
    }

    public function testRefreshForDossierRemovesAllBatchesAndCreatesANewBatchWithAllDocuments(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($dossier);

        $oldBatchA = \Mockery::mock(BatchDownload::class);
        $oldBatchA->expects('markAsOutdated');
        $oldBatchB = \Mockery::mock(BatchDownload::class);
        $oldBatchB->expects('markAsOutdated');

        $this->batchRepository->expects('getAllForScope')->with($scope)->andReturns([$oldBatchA, $oldBatchB]);

        $this->batchRepository->expects('save')->with($oldBatchA);
        $this->batchRepository->expects('save')->with($oldBatchB);

        $this->typeA->shouldReceive('supports')->with($scope)->andReturnFalse();
        $this->typeB->shouldReceive('supports')->with($scope)->andReturnTrue();
        $this->typeB->shouldReceive('isAvailableForBatchDownload')->with($scope)->andReturnTrue();
        $this->typeB->shouldReceive('getFileBaseName')->with($scope)->andReturn('123');

        $batchValidator = \Mockery::on(
            static function (BatchDownload $batch) use ($dossier): bool {
                self::assertEquals($dossier, $batch->getDossier());

                return true;
            }
        );
        $this->batchRepository->expects('save')->with($batchValidator);

        $this->dispatcher
            ->expects('dispatchGenerateBatchDownloadCommand')
            ->with($batchValidator);

        $this->service->refresh($scope);
    }

    public function testRefreshForDossierDoesNotGenerateNewArchiveForEntityThatIsNotAvailableForBatchDownload(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($dossier);

        $oldBatchA = \Mockery::mock(BatchDownload::class);
        $oldBatchA->expects('markAsOutdated');
        $oldBatchB = \Mockery::mock(BatchDownload::class);
        $oldBatchB->expects('markAsOutdated');

        $this->batchRepository->expects('getAllForScope')->with($scope)->andReturns([$oldBatchA, $oldBatchB]);

        $this->batchRepository->expects('save')->with($oldBatchA);
        $this->batchRepository->expects('save')->with($oldBatchB);

        $this->typeA->shouldReceive('supports')->with($scope)->andReturnFalse();
        $this->typeB->shouldReceive('supports')->with($scope)->andReturnTrue();
        $this->typeB->shouldReceive('isAvailableForBatchDownload')->with($scope)->andReturnFalse();

        $this->service->refresh($scope);
    }

    public function testFindOrCreateReusesExistingBatch(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($dossier);

        $expectedBatch = \Mockery::mock(BatchDownload::class);

        $this->batchRepository->expects('getBestAvailableBatchDownloadForScope')->with($scope)->andReturns($expectedBatch);

        $batch = $this->service->findOrCreate($scope);

        self::assertSame($expectedBatch, $batch);
    }

    public function testFindOrCreateMakesNewBatch(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($dossier);

        $this->batchRepository->expects('getBestAvailableBatchDownloadForScope')->with($scope)->andReturnNull();

        $batchValidator = \Mockery::on(
            static function (BatchDownload $batch) use ($dossier): bool {
                self::assertEquals($dossier, $batch->getDossier());

                return true;
            }
        );
        $this->batchRepository->expects('save')->with($batchValidator);

        $this->dispatcher
            ->expects('dispatchGenerateBatchDownloadCommand')
            ->with($batchValidator);

        $batch = $this->service->findOrCreate($scope);

        $this->assertEquals($dossier, $batch->getDossier());
    }
}
