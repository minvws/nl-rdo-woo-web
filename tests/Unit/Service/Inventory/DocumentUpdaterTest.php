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
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ObsoleteFileRemover;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Uid\Uuid;

use function str_repeat;

class DocumentUpdaterTest extends UnitTestCase
{
    private MockInterface&ObsoleteFileRemover $obsoleteFileRemover;
    private DocumentUpdater $documentUpdater;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private WooDecision&MockInterface $dossier;
    private DocumentRepository&MockInterface $repository;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(DocumentRepository::class);
        $this->obsoleteFileRemover = Mockery::mock(ObsoleteFileRemover::class);
        $this->documentDispatcher = Mockery::mock(DocumentDispatcher::class);
        $this->ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $this->dossier = Mockery::mock(WooDecision::class);

        $this->documentUpdater = new DocumentUpdater(
            $this->obsoleteFileRemover,
            $this->repository,
            $this->documentDispatcher,
            $this->ingestDispatcher,
        );

        parent::setUp();
    }

    public function testLinkingWithoutMetadataDoesNotRemoveExistingFile(): void
    {
        $document = new Document();
        $document->setDocumentNumber(DocumentNumber::fromString('tst-123'));

        $this->repository->expects('save')->with($document);

        $this->documentUpdater->linkDocument($document, $this->dossier);

        self::assertTrue($document->getDossiers()->contains($this->dossier));
    }

    public function testUpdatesMetadataAfterLinking(): void
    {
        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC);

        $existingDocument = Mockery::mock(Document::class);
        $existingDocument->expects('getDocumentNumber')->andReturn(DocumentNumber::fromString('tst-123'));
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
        $existingDocument->expects('setPublicationContext')->with($documentMetadata->getPublicationContext());
        $existingDocument->expects('getFileInfo')->andReturn(new FileInfo());
        $existingDocument->expects('addDossier')->with($this->dossier);

        $this->obsoleteFileRemover->expects('removeIfObsolete')->with($existingDocument);

        $this->repository->expects('save')->with($existingDocument);

        $this->documentUpdater->linkDocument($existingDocument, $this->dossier);
        $this->documentUpdater->updateMetadata($documentMetadata, $existingDocument);
    }

    public function testUpdateDocumentReferrals(): void
    {
        $newReferredDoc = Mockery::mock(Document::class);

        $oldReferredDoc = Mockery::mock(Document::class);
        $oldReferredDoc->expects('getDocumentNumber')->andReturn(DocumentNumber::fromString('publication-context-456'));

        $existingDocument = Mockery::mock(Document::class);
        $existingDocument->expects('getRefersTo')->andReturn(new ArrayCollection([$oldReferredDoc]));

        // Old referred document is no longer in metadata so should be removed
        $existingDocument->expects('removeRefersTo')->with($oldReferredDoc);

        // And a new referral should be added
        $existingDocument->expects('addRefersTo')->with($newReferredDoc);

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === 'publication-context-123',
            ))
            ->andReturn($newReferredDoc);

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === 'publication-context-456',
            ))
            ->andReturn($oldReferredDoc);

        $this->documentUpdater->updateDocumentReferralsByDocumentNumber($existingDocument, ['publication-context-123']);
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
            publicationContext: PublicationContext::fromString('pr3f1x-matt3r'),
            refersTo: ['pr3f1x-matt3r-123'],
        );
    }

    public function testDatabaseRemove(): void
    {
        $document = Mockery::mock(Document::class);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('removeDocument')->with($document);

        $this->documentUpdater->databaseRemove($document, $wooDecision);
    }

    public function testUpdateMetadataTruncatesLongFilenameAtGraphemeBoundary(): void
    {
        // 1023 ASCII chars + 1 multibyte (é) + tail; after truncation at 1024 graphemes the é must stay whole.
        $filename = str_repeat('a', 1023) . 'éxtra';
        $expectedName = str_repeat('a', 1023) . 'é';

        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC, $filename);

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('setSourceType')->with($documentMetadata->getSourceType());
        $fileInfo->expects('setName')->with($expectedName);

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')->andReturn(DocumentNumber::fromString('tst-123'));
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
        $document->expects('setPublicationContext')->with($documentMetadata->getPublicationContext());
        $document->expects('getFileInfo')->andReturn($fileInfo);
        $document->expects('addDossier')->with($this->dossier);

        $this->obsoleteFileRemover->expects('removeIfObsolete')->with($document);

        $this->repository->expects('save')->with($document);

        $this->documentUpdater->linkDocument($document, $this->dossier);
        $this->documentUpdater->updateMetadata($documentMetadata, $document);
    }
}
