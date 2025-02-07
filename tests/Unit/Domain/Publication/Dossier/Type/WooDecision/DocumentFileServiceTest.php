<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Exception\DocumentFileSetException;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileUploadRepository;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

class DocumentFileServiceTest extends UnitTestCase
{
    private DocumentFileDispatcher&MockInterface $dispatcher;
    private DocumentFileSetRepository&MockInterface $documentFileSetRepository;
    private DocumentFileUploadRepository&MockInterface $documentFileUploadRepository;
    private WooDecision&MockInterface $wooDecision;
    private EntityStorageService&MockInterface $entityStorageService;
    private DocumentFileService $service;

    public function setUp(): void
    {
        $this->wooDecision = \Mockery::mock(WooDecision::class);
        $this->dispatcher = \Mockery::mock(DocumentFileDispatcher::class);
        $this->documentFileSetRepository = \Mockery::mock(DocumentFileSetRepository::class);
        $this->documentFileUploadRepository = \Mockery::mock(DocumentFileUploadRepository::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);

        $this->service = new DocumentFileService(
            $this->dispatcher,
            $this->documentFileSetRepository,
            $this->documentFileUploadRepository,
            $this->entityStorageService,
        );
    }

    public function testGetDocumentFileUsesExistingUncompletedSet(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        self::assertSame(
            $documentFileSet,
            $this->service->getDocumentFileSet($this->wooDecision),
        );
    }

    public function testAddUploadThrowsExceptionWhenSetIsNotOpenForUploads(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());
        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::PROCESSING_UPLOADS);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->expectException(DocumentFileSetException::class);

        $this->service->addUpload(
            $this->wooDecision,
            \Mockery::mock(UploadedFile::class),
        );
    }

    public function testAddUploadSuccessfully(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getOriginalFilename')->andReturn('file.txt');

        $this->documentFileUploadRepository
            ->expects('save')
            ->with(\Mockery::type(DocumentFileUpload::class));

        $this->entityStorageService->expects('storeEntity')
            ->with($upload, \Mockery::type(DocumentFileUpload::class));

        $this->documentFileUploadRepository
            ->expects('save')
            ->with(\Mockery::type(DocumentFileUpload::class), true);

        $this->service->addUpload(
            $this->wooDecision,
            $upload,
        );
    }

    public function testGetDocumentFileCreatesNewSetWhenNoneFound(): void
    {
        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturnNull();

        $this->documentFileSetRepository
            ->expects('save')
            ->with(\Mockery::type(DocumentFileSet::class), true);

        $set = $this->service->getDocumentFileSet($this->wooDecision);

        self::assertTrue($set->getStatus()->isOpenForUploads());
    }

    public function testStartProcessingUploadsThrowsExceptionForInvalidStatus(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::PROCESSING_UPLOADS);
        $documentFileSet
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $this->expectException(DocumentFileSetException::class);
        $this->service->startProcessingUploads($this->wooDecision);
    }

    public function testStartProcessingUploadsThrowsExceptionWhenNoUploadsAvailable(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        $documentFileSet
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        $this->expectException(DocumentFileSetException::class);
        $this->service->startProcessingUploads($this->wooDecision);
    }

    public function testStartProcessingUploadsSuccessfully(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::PROCESSING_UPLOADS);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(1);

        $this->dispatcher->expects('dispatchProcessDocumentFileSetUploadsCommand')->with($documentFileSet);

        $this->service->startProcessingUploads($this->wooDecision);
    }

    public function testStartProcessingUploadsThrowsExceptionWhenStatusCannotBeUpdated(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        $documentFileSet
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(1);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::PROCESSING_UPLOADS)
            ->andThrow(new \RuntimeException('oops'));

        $this->expectException(DocumentFileSetException::class);
        $this->service->startProcessingUploads($this->wooDecision);
    }

    #[DataProvider('getInvalidDossierStatusDataForConfirmUpdates')]
    public function testConfirmUpdatesThrowsExceptionForInvalidDossierStatus(DossierStatus $status): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn($status);
        $this->wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DocumentFileSetException::class);
        $this->service->confirmUpdates($this->wooDecision);
    }

    /**
     * @return array<string,array{status:DossierStatus}>
     */
    public static function getInvalidDossierStatusDataForConfirmUpdates(): array
    {
        return [
            'new' => ['status' => DossierStatus::NEW],
            'deleted' => ['status' => DossierStatus::DELETED],
        ];
    }

    public function testConfirmUpdatesThrowsExceptionForInvalidStatus(): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::SCHEDULED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::PROCESSING_UPLOADS);

        $this->expectException(DocumentFileSetException::class);
        $this->service->confirmUpdates($this->wooDecision);
    }

    public function testConfirmUpdatesSuccessfully(): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->expects('getStatus')
            ->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::CONFIRMED);

        $this->dispatcher->expects('dispatchProcessDocumentFileSetUpdatesCommand')->with($documentFileSet);

        $this->service->confirmUpdates($this->wooDecision);
    }

    public function testRejectUpdatesThrowsExceptionForInvalidStatus(): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $documentFileSet
            ->expects('getStatus')
            ->twice()
            ->andReturn(DocumentFileSetStatus::PROCESSING_UPLOADS);

        $this->expectException(DocumentFileSetException::class);
        $this->service->rejectUpdates($this->wooDecision);
    }

    public function testRejectUpdatesSuccessfully(): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->expects('getStatus')
            ->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::REJECTED);

        $this->service->rejectUpdates($this->wooDecision);
    }

    #[DataProvider('getInvalidDossierStatusDataForRejectUpdates')]
    public function testRejectUpdatesThrowsExceptionForInvalidDossierStatus(DossierStatus $status): void
    {
        $this->wooDecision->shouldReceive('getStatus')->andReturn($status);
        $this->wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DocumentFileSetException::class);
        $this->service->rejectUpdates($this->wooDecision);
    }

    /**
     * @return array<string,array{status:DossierStatus}>
     */
    public static function getInvalidDossierStatusDataForRejectUpdates(): array
    {
        return [
            'new' => ['status' => DossierStatus::NEW],
            'concept' => ['status' => DossierStatus::CONCEPT],
            'deleted' => ['status' => DossierStatus::DELETED],
        ];
    }

    public function testCheckProcessingUploadsCompletionDoesNotUpdateStatusWhenThereAreStillUploadsToProcess(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(12);

        $this->service->checkProcessingUploadsCompletion($documentFileSet);
    }

    public function testCheckProcessingUploadsCompletionConfirmsUpdatesForConceptDossier(): void
    {
        $this->wooDecision
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::CONCEPT);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($this->wooDecision);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::NEEDS_CONFIRMATION);

        $documentFileSet
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        $this->documentFileSetRepository
            ->expects('findUncompletedByDossier')
            ->with($this->wooDecision)
            ->andReturn($documentFileSet);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::CONFIRMED);

        $this->dispatcher->expects('dispatchProcessDocumentFileSetUpdatesCommand')->with($documentFileSet);

        $this->service->checkProcessingUploadsCompletion($documentFileSet);
    }

    public function testCheckProcessingUploadsCompletionSetsStatusToNeedsConfirmationForPublishedDossier(): void
    {
        $this->wooDecision
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($this->wooDecision);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::NEEDS_CONFIRMATION);

        $this->service->checkProcessingUploadsCompletion($documentFileSet);
    }

    public function testCheckProcessingUpdatesCompletionDoesNotUpdateStatusWhenThereAreStillUpdatesToProcess(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('countUpdatesToProcess')
            ->with($documentFileSet)
            ->andReturn(12);

        $this->service->checkProcessingUpdatesCompletion($documentFileSet);
    }

    public function testCheckProcessingUpdatesCompletionSetsStatusToCompleted(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('countUpdatesToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        $this->documentFileSetRepository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::COMPLETED);

        $this->service->checkProcessingUpdatesCompletion($documentFileSet);
    }

    public function testHasUploads(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(12);

        self::assertTrue($this->service->hasUploads($documentFileSet));
    }

    public function testHasUploadsReturnsFalse(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        self::assertFalse($this->service->hasUploads($documentFileSet));
    }

    public function testCanProcess(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getStatus')->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(1);

        self::assertTrue($this->service->canProcess($documentFileSet));
    }

    public function testCanProcessWithInvalidStatus(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getStatus')->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);

        self::assertFalse($this->service->canProcess($documentFileSet));
    }

    public function testCanProcessWithoutUploads(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getStatus')->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);

        $this->documentFileSetRepository
            ->expects('countUploadsToProcess')
            ->with($documentFileSet)
            ->andReturn(0);

        self::assertFalse($this->service->canProcess($documentFileSet));
    }
}
