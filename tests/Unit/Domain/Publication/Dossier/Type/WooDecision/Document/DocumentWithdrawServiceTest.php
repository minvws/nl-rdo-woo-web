<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\DocumentWorkflowException;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DocumentWithdrawServiceTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private MockInterface&EntityStorageService $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private DocumentWithdrawService $service;

    protected function setUp(): void
    {
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);
        $this->ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $this->documentDispatcher = Mockery::mock(DocumentDispatcher::class);

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
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = Mockery::mock(Document::class);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $uuid = Uuid::v6();
        $document->expects('withdraw')->with($reason, $explanation);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('isWithdrawn')->andReturnFalse();

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
        $document = Mockery::mock(Document::class);

        $document->expects('shouldBeUploaded')->andReturnFalse();

        $this->expectException(DocumentWorkflowException::class);

        $this->service->withdraw($document, $reason, $explanation);
    }

    public function testWithdrawAllDocuments(): void
    {
        $dossier = Mockery::mock(WooDecision::class);

        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $documentA = Mockery::mock(Document::class);
        $documentA->expects('withdraw')->with($reason, $explanation);
        $documentA->expects('shouldBeUploaded')->andReturnTrue();
        $documentA->expects('isWithdrawn')->andReturnFalse();

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($documentA);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($documentA);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')->with($documentA, true);

        $this->documentDispatcher
            ->expects('dispatchDocumentWithdrawnEvent')
            ->with($documentA, $reason, $explanation, true);

        $this->documentRepository->expects('save')->with($documentA, true);

        $documentB = Mockery::mock(Document::class);
        $documentB->expects('withdraw')->with($reason, $explanation);
        $documentB->expects('shouldBeUploaded')->andReturnTrue();
        $documentB->expects('isWithdrawn')->andReturnFalse();

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($documentB);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($documentB);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')->with($documentB, true);

        $this->documentDispatcher
            ->expects('dispatchDocumentWithdrawnEvent')
            ->with($documentB, $reason, $explanation, true);

        $this->documentRepository->expects('save')->with($documentB, true);

        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$documentA, $documentB]));

        $this->documentDispatcher
            ->expects('dispatchAllDocumentsWithdrawnEvent')
            ->with($dossier, $reason, $explanation);

        $this->service->withDrawAllDocuments($dossier, $reason, $explanation);
    }
}
