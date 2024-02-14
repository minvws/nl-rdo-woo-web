<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Entity\Judgement;
use App\Entity\Organisation;
use App\Enum\PublicationStatus;
use App\Message\IngestMetadataOnlyMessage;
use App\Message\RemoveDocumentMessage;
use App\Repository\DocumentRepository;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\DocumentNumber;
use App\Service\Inventory\DocumentUpdater;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DocumentUpdaterTest extends MockeryTestCase
{
    private MockInterface&DocumentStorageService $documentStorage;
    private MessageBusInterface&MockInterface $messageBus;
    private DocumentUpdater $documentUpdater;
    private Dossier&MockInterface $dossier;
    private DocumentRepository&MockInterface $repository;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(DocumentRepository::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier->shouldReceive('getDocumentPrefix')->andReturn('PREFIX');
        $this->dossier->shouldReceive('getStatus')->andReturn(PublicationStatus::CONCEPT);

        $organisation = \Mockery::mock(Organisation::class);
        $this->dossier->shouldReceive('getOrganisation')->andReturn($organisation);

        $this->documentUpdater = new DocumentUpdater(
            $this->messageBus,
            $this->documentStorage,
            $this->repository,
        );

        parent::setUp();
    }

    public function testProcessRemovesObsoleteUpload(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);

        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC);

        $existingDocument = \Mockery::mock(Document::class);
        $existingDocument->expects('getDocumentNr')->andReturn('tst-123');
        $existingDocument->expects('setJudgement')->with($documentMetadata->getJudgement());
        $existingDocument->shouldReceive('getDocumentId')->andReturn('456');
        $existingDocument->expects('setDocumentDate')->with($documentMetadata->getDate());
        $existingDocument->expects('setFamilyId')->with($documentMetadata->getFamilyId());
        $existingDocument->expects('setDocumentId')->with($documentMetadata->getId());
        $existingDocument->expects('setThreadId')->with($documentMetadata->getThreadId());
        $existingDocument->expects('setGrounds')->with($documentMetadata->getGrounds());
        $existingDocument->expects('setSubjects')->with($documentMetadata->getSubjects());
        $existingDocument->expects('setPeriod')->with($documentMetadata->getPeriod());
        $existingDocument->expects('setSuspended')->with($documentMetadata->isSuspended());
        $existingDocument->expects('setLinks')->with($documentMetadata->getLinks());
        $existingDocument->expects('setRemark')->with($documentMetadata->getRemark());
        $existingDocument->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $existingDocument->shouldReceive('shouldBeUploaded')->andReturnFalse();
        $existingDocument->expects('addDossier')->with($this->dossier);

        $fileInfo->expects('setSourceType')->with($documentMetadata->getSourceType());
        $fileInfo->expects('setName')->with('file.doc');

        $this->repository->expects('save')->with($existingDocument);

        $this->documentStorage->shouldReceive('deleteAllFilesForDocument')->with($existingDocument);
        $fileInfo->expects('removeFileProperties');
        $existingDocument->expects('setPageCount')->with(0);

        $this->documentUpdater->databaseUpdate($documentMetadata, $this->dossier, $existingDocument);
    }

    public function testProcess(): void
    {
        $documentMetadata = $this->getDocumentMetadata(Judgement::PUBLIC);

        $existingDocument = \Mockery::mock(Document::class);
        $existingDocument->expects('getDocumentNr')->andReturn('tst-123');
        $existingDocument->expects('setJudgement')->with($documentMetadata->getJudgement());
        $existingDocument->shouldReceive('getDocumentId')->andReturn('456');
        $existingDocument->expects('setDocumentDate')->with($documentMetadata->getDate());
        $existingDocument->expects('setFamilyId')->with($documentMetadata->getFamilyId());
        $existingDocument->expects('setDocumentId')->with($documentMetadata->getId());
        $existingDocument->expects('setThreadId')->with($documentMetadata->getThreadId());
        $existingDocument->expects('setGrounds')->with($documentMetadata->getGrounds());
        $existingDocument->expects('setSubjects')->with($documentMetadata->getSubjects());
        $existingDocument->expects('setPeriod')->with($documentMetadata->getPeriod());
        $existingDocument->expects('setSuspended')->with($documentMetadata->isSuspended());
        $existingDocument->expects('setLinks')->with($documentMetadata->getLinks());
        $existingDocument->expects('setRemark')->with($documentMetadata->getRemark());
        $existingDocument->expects('getFileInfo')->andReturn(new FileInfo());
        $existingDocument->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $existingDocument->expects('addDossier')->with($this->dossier);

        $this->repository->expects('save')->with($existingDocument);

        $this->documentUpdater->databaseUpdate($documentMetadata, $this->dossier, $existingDocument);
    }

    public function testUpdateDocumentReferrals(): void
    {
        $newReferredDoc = \Mockery::mock(Document::class);

        $oldReferredDoc = \Mockery::mock(Document::class);
        $oldReferredDoc->shouldReceive('getDocumentNr')->andReturn('PREFIX-matter-456');
        $oldReferredDoc->shouldReceive('getDocumentId')->andReturn('456');

        $existingDocument = \Mockery::mock(Document::class);
        $existingDocument->expects('getRefersTo')->andReturn(new ArrayCollection([$oldReferredDoc]));
        $existingDocument->shouldReceive('getDocumentNr')->andReturn('PREFIX-matter-1');
        $existingDocument->shouldReceive('getDocumentId')->andReturn('1');

        // Old referred document is no longer in metadata so should be removed
        $existingDocument->expects('removeReferralTo')->with($oldReferredDoc);

        // And a new referral should be added
        $existingDocument->expects('addReferralTo')->with($newReferredDoc);

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(\Mockery::on(
                static function (DocumentNumber $documentNumber): bool {
                    return $documentNumber->getValue() === 'PREFIX-matter-123';
                }
            ))
            ->andReturn($newReferredDoc);

        $this->repository
            ->expects('findByDocumentNumber')
            ->with(\Mockery::on(
                static function (DocumentNumber $documentNumber): bool {
                    return $documentNumber->getValue() === 'PREFIX-matter-456';
                }
            ))
            ->andReturn($oldReferredDoc);

        $this->documentUpdater->updateDocumentReferrals($this->dossier, $existingDocument, ['PREFIX-matter-123']);
    }

    public function testAsyncUpdate(): void
    {
        $docId = Uuid::v6();
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($docId);
        $document->expects('shouldBeUploaded')->andReturnTrue();

        $this->messageBus->expects('dispatch')->once()
            ->with(\Mockery::on(
                static function (IngestMetadataOnlyMessage $message) use ($docId) {
                    return $message->getUuid() === $docId && $message->getForceRefresh() === false;
                }
            ))
            ->andReturns(new Envelope(new \stdClass()));

        $this->documentUpdater->asyncUpdate($document);
    }

    public function testAsyncDelete(): void
    {
        $docId = Uuid::v6();
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($docId);

        $dossierId = Uuid::v6();
        $this->dossier->shouldReceive('getId')->andReturn($dossierId);

        $this->messageBus->expects('dispatch')->once()
            ->with(\Mockery::on(
                static function (RemoveDocumentMessage $message) use ($docId, $dossierId) {
                    return $message->getDocumentId() === $docId && $message->getDossierId() === $dossierId;
                }
            ))
            ->andReturns(new Envelope(new \stdClass()));

        $this->documentUpdater->asyncRemove($document, $this->dossier);
    }

    private function getDocumentMetadata(Judgement $judgement): DocumentMetadata
    {
        return new DocumentMetadata(
            date: new \DateTimeImmutable('2023-09-28 10:11:12'),
            filename: 'file.doc',
            familyId: 1,
            sourceType: 'email',
            grounds: ['5.1.1a', '5.1.1b'],
            id: '123',
            judgement: $judgement,
            period: '',
            subjects: ['subject a', 'subject b'],
            threadId: 456,
            caseNumbers: ['12-b', '13-a'],
            suspended: true,
            links: ['https://a.dummy.link/here'],
            remark: 'remark',
            matter: '987',
            refersTo: ['matter-123'],
        );
    }
}
