<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Exception\DocumentWorkflowException;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class DocumentWithdrawServiceTest extends MockeryTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private MockInterface&EntityStorageService $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private DocumentWithdrawService $service;

    protected function setUp(): void
    {
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);
        $this->documentDispatcher = \Mockery::mock(DocumentDispatcher::class);

        $this->service = new DocumentWithdrawService(
            $this->documentRepository,
            $this->entityStorageService,
            $this->thumbnailStorageService,
            $this->ingestDispatcher,
            $this->documentDispatcher,
        );

        parent::setUp();
    }

    public function testWithdrawSuccessfully(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);

        $secondDossierUuid = Uuid::v6();
        $secondDossier = \Mockery::mock(WooDecision::class);
        $secondDossier->shouldReceive('getId')->andReturn($secondDossierUuid);

        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $uuid = Uuid::v6();
        $document->expects('withdraw')->with($reason, $explanation);
        $document->shouldReceive('getId')->andReturn($uuid);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier, $secondDossier]));
        $document->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $document->shouldReceive('isWithdrawn')->andReturnFalse();

        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')->with($document, true);

        $this->documentDispatcher
            ->expects('dispatchDocumentWithdrawnEvent')
            ->with($document, $reason, $explanation, false);

        $this->documentRepository->expects('save')->with($document, true);

        $this->service->withdraw($document, $reason, $explanation);
    }

    public function testWithdrawThrowsExceptionWhenDocumentDoesNotSupportWithdraw(): void
    {
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $uuid = Uuid::v6();
        $document->shouldReceive('getId')->andReturn($uuid);
        $document->shouldReceive('shouldBeUploaded')->andReturnFalse();

        $this->expectException(DocumentWorkflowException::class);

        $this->service->withdraw($document, $reason, $explanation);
    }

    public function testWithdrawAllDocuments(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);

        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $idA = Uuid::v6();
        $documentA = \Mockery::mock(Document::class);
        $documentA->expects('withdraw')->with($reason, $explanation);
        $documentA->shouldReceive('getId')->andReturn($idA);
        $documentA->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $documentA->shouldReceive('isWithdrawn')->andReturnFalse();

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($documentA);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($documentA);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')->with($documentA, true);

        $this->documentDispatcher
            ->expects('dispatchDocumentWithdrawnEvent')
            ->with($documentA, $reason, $explanation, true);

        $this->documentRepository->expects('save')->with($documentA, true);

        $idB = Uuid::v6();
        $documentB = \Mockery::mock(Document::class);
        $documentB->expects('withdraw')->with($reason, $explanation);
        $documentB->shouldReceive('getId')->andReturn($idB);
        $documentB->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $documentB->shouldReceive('isWithdrawn')->andReturnFalse();

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($documentB);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($documentB);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')->with($documentB, true);

        $this->documentDispatcher
            ->expects('dispatchDocumentWithdrawnEvent')
            ->with($documentB, $reason, $explanation, true);

        $this->documentRepository->expects('save')->with($documentB, true);

        $dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$documentA, $documentB]));

        $this->documentDispatcher
            ->expects('dispatchAllDocumentsWithdrawnEvent')
            ->with($dossier, $reason, $explanation);

        $this->service->withDrawAllDocuments($dossier, $reason, $explanation);
    }
}
