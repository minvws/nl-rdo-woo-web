<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadDispatcher;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\BatchDownload\BatchDownloadStatus;
use App\Domain\Publication\BatchDownload\BatchDownloadStorage;
use App\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class BatchDownloadServiceTest extends MockeryTestCase
{
    private BatchDownloadRepository&MockInterface $batchRepository;
    private BatchDownloadDispatcher&MockInterface $dispatcher;
    private BatchDownloadStorage&MockInterface $storage;
    private BatchDownloadTypeInterface&MockInterface $typeA;
    private BatchDownloadTypeInterface&MockInterface $typeB;
    private BatchDownloadService $service;

    protected function setUp(): void
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
        $oldBatchA->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::COMPLETED);
        $oldBatchA->expects('markAsOutdated');

        $oldBatchB = \Mockery::mock(BatchDownload::class);
        $oldBatchB->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::COMPLETED);
        $oldBatchB->expects('markAsOutdated');

        // Already outdated so should not be updated again
        $oldBatchC = \Mockery::mock(BatchDownload::class);
        $oldBatchC->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::OUTDATED);

        $this->batchRepository->expects('getAllForScope')->with($scope)->andReturns([$oldBatchA, $oldBatchB, $oldBatchC]);

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
        $oldBatchA->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::COMPLETED);
        $oldBatchA->expects('markAsOutdated');

        $oldBatchB = \Mockery::mock(BatchDownload::class);
        $oldBatchB->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::COMPLETED);
        $oldBatchB->expects('markAsOutdated');

        $this->batchRepository->expects('getAllForScope')->with($scope)->andReturns([$oldBatchA, $oldBatchB]);

        $this->batchRepository->expects('save')->with($oldBatchA);
        $this->batchRepository->expects('save')->with($oldBatchB);

        $this->typeA->shouldReceive('supports')->with($scope)->andReturnFalse();
        $this->typeB->shouldReceive('supports')->with($scope)->andReturnTrue();
        $this->typeB->shouldReceive('isAvailableForBatchDownload')->with($scope)->andReturnFalse();

        $this->service->refresh($scope);
    }

    public function refreshForScopeWithBothADossierAndInquiryDoesNothing(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $inquiry = \Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $dossier);

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
        // $this->typeB->shouldReceive('getFileBaseName')->with($scope)->andReturn('123');

        /** @var BatchDownloadService&MockInterface $service */
        $service = \Mockery::mock(BatchDownloadService::class, [
            $this->batchRepository,
            $this->dispatcher,
            $this->storage,
            [$this->typeA, $this->typeB],
        ])->makePartial();

        $service->shouldNotReceive('create');

        $service->refresh($scope);
    }

    public function testCreateWithScopeWithBothADossierAndInquiry(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $inquiry = \Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $dossier);

        /** @var BatchDownloadService&MockInterface $service */
        $service = \Mockery::mock(BatchDownloadService::class, [
            $this->batchRepository,
            $this->dispatcher,
            $this->storage,
            [$this->typeA, $this->typeB],
        ])->makePartial();

        $service
            ->shouldReceive('findOrCreate')
            ->with(\Mockery::on(function (BatchDownloadScope $scope) use ($dossier) {
                if ($scope->containsBothInquiryAndWooDecision()) {
                    return false;
                }

                if ($scope->wooDecision !== $dossier) {
                    return false;
                }

                return true;
            }))
            ->once()
            ->andReturn(\Mockery::mock(BatchDownload::class));

        $service->create($scope);
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

    public function testExists(): void
    {
        $batchDownload = \Mockery::mock(BatchDownload::class);
        $batchDownload->shouldReceive('getId')->once()->andReturn($uuid = Uuid::v6());

        $this->batchRepository->shouldReceive('exists')->once()->with($uuid)->andReturnTrue();

        $this->assertTrue($this->service->exists($batchDownload));
    }
}
