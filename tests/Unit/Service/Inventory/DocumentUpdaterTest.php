<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

use function str_repeat;

class DocumentUpdaterTest extends UnitTestCase
{
    private MockInterface&EntityStorageService $entityStorageService;
    private MockInterface&ThumbnailStorageService $thumbnailStorageService;
    private DocumentUpdater $documentUpdater;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private WooDecision&MockInterface $dossier;
    private DocumentRepository&MockInterface $repository;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(DocumentRepository::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);
        $this->documentDispatcher = Mockery::mock(DocumentDispatcher::class);
        $this->ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $this->dossier = Mockery::mock(WooDecision::class);

        $this->documentUpdater = new DocumentUpdater(
            $this->entityStorageService,
            $this->thumbnailStorageService,
            $this->repository,
            $this->documentDispatcher,
            $this->ingestDispatcher,
        );

        parent::setUp();
    }

    public function testProcessRemovesObsoleteUpload(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);

        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC);

        $existingDocument = Mockery::mock(Document::class);
        $existingDocument->expects('getDocumentNr')->andReturn('tst-123');
        $existingDocument->expects('setJudgement')->with($documentMetadata->getJudgement());
        $existingDocument->expects('setDocumentDate')->with($documentMetadata->getDate());
        $existingDocument->expects('setFamilyId')->with($documentMetadata->getFamilyId());
        $existingDocument->expects('setDocumentId')->with($documentMetadata->getId());
        $existingDocument->expects('setThreadId')->with($documentMetadata->getThreadId());
        $existingDocument->expects('setGrounds')->with($documentMetadata->getGrounds());
        $existingDocument->expects('setPeriod')->with($documentMetadata->getPeriod());
        $existingDocument->expects('setSuspended')->with($documentMetadata->isSuspended());
        $existingDocument->expects('setLinks')->with($documentMetadata->getLinks());
        $existingDocument->expects('setRemark')->with($documentMetadata->getRemark());
        $existingDocument->expects('getFileInfo')->times(2)->andReturn($fileInfo);
        $existingDocument->expects('shouldBeUploaded')->andReturnFalse();
        $existingDocument->expects('addDossier')->with($this->dossier);

        $fileInfo->expects('setSourceType')->with($documentMetadata->getSourceType());
        $fileInfo->expects('setName')->with('file.doc');

        $this->repository->expects('save')->with($existingDocument);

        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($existingDocument);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($existingDocument);
        $fileInfo->expects('removeFileProperties');

        $this->documentUpdater->databaseUpdate($documentMetadata, $this->dossier, $existingDocument);
    }

    public function testProcess(): void
    {
        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC);

        $existingDocument = Mockery::mock(Document::class);
        $existingDocument->expects('getDocumentNr')->andReturn('tst-123');
        $existingDocument->expects('setJudgement')->with($documentMetadata->getJudgement());
        $existingDocument->expects('setDocumentDate')->with($documentMetadata->getDate());
        $existingDocument->expects('setFamilyId')->with($documentMetadata->getFamilyId());
        $existingDocument->expects('setDocumentId')->with($documentMetadata->getId());
        $existingDocument->expects('setThreadId')->with($documentMetadata->getThreadId());
        $existingDocument->expects('setGrounds')->with($documentMetadata->getGrounds());
        $existingDocument->expects('setPeriod')->with($documentMetadata->getPeriod());
        $existingDocument->expects('setSuspended')->with($documentMetadata->isSuspended());
        $existingDocument->expects('setLinks')->with($documentMetadata->getLinks());
        $existingDocument->expects('setRemark')->with($documentMetadata->getRemark());
        $existingDocument->expects('getFileInfo')->andReturn(new FileInfo());
        $existingDocument->expects('shouldBeUploaded')->andReturnTrue();
        $existingDocument->expects('addDossier')->with($this->dossier);

        $this->repository->expects('save')->with($existingDocument);

        $this->documentUpdater->databaseUpdate($documentMetadata, $this->dossier, $existingDocument);
    }

    public function testUpdateDocumentReferrals(): void
    {
        $newReferredDoc = Mockery::mock(Document::class);

        $oldReferredDoc = Mockery::mock(Document::class);
        $oldReferredDoc->expects('getDocumentNr')->andReturn('PREFIX-matter-456');
        $oldReferredDoc->expects('getDocumentId')->times(3)->andReturn(DocumentId::create('456'));

        $existingDocument = Mockery::mock(Document::class);
        $existingDocument->expects('getRefersTo')->andReturn(new ArrayCollection([$oldReferredDoc]));
        $existingDocument->expects('getDocumentNr')->andReturn('PREFIX-matter-1');
        $existingDocument->expects('getDocumentId')->times(3)->andReturn(DocumentId::create('1'));

        // Old referred document is no longer in metadata so should be removed
        $existingDocument->expects('removeRefersTo')->with($oldReferredDoc);

        // And a new referral should be added
        $existingDocument->expects('addRefersTo')->with($newReferredDoc);

        $this->dossier->expects('getDocumentPrefix')->times(5)->andReturn('PREFIX');

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->getValue() === 'PREFIX-matter-123',
            ))
            ->andReturn($newReferredDoc);

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->getValue() === 'PREFIX-matter-456',
            ))
            ->andReturn($oldReferredDoc);

        $this->documentUpdater->updateDocumentReferralsByDocumentNumber($this->dossier, $existingDocument, ['PREFIX-matter-123']);
    }

    public function testAsyncUpdate(): void
    {
        $docId = Uuid::v6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($docId);
        $document->expects('shouldBeUploaded')->andReturnTrue();

        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($docId, Document::class, false);

        $this->documentUpdater->asyncUpdate($document);
    }

    public function testAsyncDelete(): void
    {
        $docId = Uuid::v6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($docId);

        $dossierId = Uuid::v6();
        $this->dossier->expects('getId')->andReturn($dossierId);

        $this->documentDispatcher->expects('dispatchRemoveDocumentCommand')->with($dossierId, $docId);

        $this->documentUpdater->asyncRemove($document, $this->dossier);
    }

    private function getDocumentMetadata(Judgement $judgement, string $filename = 'file.doc'): DocumentMetadata
    {
        return new DocumentMetadata(
            date: PlainDate::create('2023-09-28'),
            filename: $filename,
            familyId: 1,
            sourceType: SourceType::EMAIL,
            grounds: ['5.1.1a', '5.1.1b'],
            id: DocumentId::create('123'),
            judgement: $judgement,
            period: '',
            threadId: 456,
            inquiryNumbers: new InquiryNumbers(['12-b', '13-a']),
            suspended: true,
            links: ['https://a.dummy.link/here'],
            remark: 'remark',
            matter: DocumentMatter::create('987'),
            refersTo: ['matter-123'],
        );
    }

    public function testDatabaseRemove(): void
    {
        $document = Mockery::mock(Document::class);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('removeDocument')->with($document);

        $this->documentUpdater->databaseRemove($document, $wooDecision);
    }

    public function testDatabaseUpdateTruncatesLongFilenameAtGraphemeBoundary(): void
    {
        // 1023 ASCII chars + 1 multibyte (é) + tail; after truncation at 1024 graphemes the é must stay whole.
        $filename = str_repeat('a', 1023) . 'éxtra';
        $expectedName = str_repeat('a', 1023) . 'é';

        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC, $filename);

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('setSourceType')->with($documentMetadata->getSourceType());
        $fileInfo->expects('setName')->with($expectedName);

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->andReturn('tst-123');
        $document->expects('setJudgement')->with($documentMetadata->getJudgement());
        $document->expects('setDocumentDate')->with($documentMetadata->getDate());
        $document->expects('setFamilyId')->with($documentMetadata->getFamilyId());
        $document->expects('setDocumentId')->with($documentMetadata->getId());
        $document->expects('setThreadId')->with($documentMetadata->getThreadId());
        $document->expects('setGrounds')->with($documentMetadata->getGrounds());
        $document->expects('setPeriod')->with($documentMetadata->getPeriod());
        $document->expects('setSuspended')->with($documentMetadata->isSuspended());
        $document->expects('setLinks')->with($documentMetadata->getLinks());
        $document->expects('setRemark')->with($documentMetadata->getRemark());
        $document->expects('getFileInfo')->andReturn($fileInfo);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('addDossier')->with($this->dossier);

        $this->repository->expects('save')->with($document);

        $this->documentUpdater->databaseUpdate($documentMetadata, $this->dossier, $document);
    }
}
