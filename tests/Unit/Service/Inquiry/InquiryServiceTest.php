<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Search\SearchDispatcher;
use App\Entity\Document;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Message\GenerateInquiryArchivesMessage;
use App\Repository\DocumentRepository;
use App\Repository\InquiryRepository;
use App\Repository\WooDecisionRepository;
use App\Service\BatchDownloadService;
use App\Service\HistoryService;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\InquiryChangeset;
use App\Service\Storage\EntityStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

class InquiryServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private MessageBusInterface&MockInterface $messageBus;
    private BatchDownloadService&MockInterface $batchDownloads;
    private MockInterface&EntityStorageService $entityStorageService;
    private HistoryService&MockInterface $historyService;
    private InquiryService $inquiryService;
    private Organisation&MockInterface $organisation;
    private InquiryRepository&MockInterface $inquiryRepo;
    private DocumentRepository&MockInterface $documentRepo;
    private WooDecision&MockInterface $dossier;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private WooDecisionDispatcher&MockInterface $wooDecisionDispatcher;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private UuidV6 $dossierId;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->batchDownloads = \Mockery::mock(BatchDownloadService::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);
        $this->wooDecisionDispatcher = \Mockery::mock(WooDecisionDispatcher::class);
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);

        $this->inquiryService = new InquiryService(
            $this->entityManager,
            $this->messageBus,
            $this->batchDownloads,
            $this->entityStorageService,
            $this->historyService,
            $this->searchDispatcher,
            $this->wooDecisionDispatcher,
            $this->ingestDispatcher
        );

        $this->historyService->shouldReceive('addInquiryEntry')->andReturnNull();

        $this->organisation = \Mockery::mock(Organisation::class);
        $this->organisation->shouldReceive('getId->toRfc4122')->andReturn('dummy-org-id-123');

        $this->inquiryRepo = \Mockery::mock(InquiryRepository::class);
        $this->documentRepo = \Mockery::mock(DocumentRepository::class);

        $this->entityManager->shouldReceive('getRepository')->with(Inquiry::class)->andReturn($this->inquiryRepo);
        $this->entityManager->shouldReceive('getRepository')->with(Document::class)->andReturn($this->documentRepo);

        $this->dossierId = Uuid::v6();
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $this->dossier->shouldReceive('getId')->andReturn($this->dossierId);

        parent::setUp();
    }

    public function testUpdateInquiryLinks(): void
    {
        $addDoc1Id = Uuid::v6();
        $addDoc2Id = Uuid::v6();
        $removeDocId = Uuid::v6();

        $addDoc1 = \Mockery::mock(Document::class);
        $addDoc1->expects('addInquiry');
        $addDoc1->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));
        $addDoc1->shouldReceive('getId')->andReturn($addDoc1Id);

        $newDossierId = Uuid::v6();
        $newDossier = \Mockery::mock(WooDecision::class);
        $newDossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $newDossier->shouldReceive('getId')->andReturn($newDossierId);

        $addDoc2 = \Mockery::mock(Document::class);
        $addDoc2->expects('addInquiry');
        $addDoc2->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));
        $addDoc2->shouldReceive('getId')->andReturn($addDoc2Id);

        $removeDoc = \Mockery::mock(Document::class);
        // Disabled as part of #2868: $removeDoc->expects('removeInquiry');
        $removeDoc->shouldReceive('getId')->andReturn($removeDocId);

        $caseNr = 'case-123';
        $inquiryId = Uuid::v6();

        $this->historyService->expects('addDossierEntry')->with($this->dossier, 'dossier_inquiry_added', ['count' => 1, 'casenrs' => $caseNr]);
        $this->historyService->expects('addDossierEntry')->with($newDossier, 'dossier_inquiry_added', ['count' => 1, 'casenrs' => $caseNr]);
        $this->inquiryRepo->expects('findOneBy')->with(['organisation' => $this->organisation, 'casenr' => $caseNr])->andReturnNull();

        $this->documentRepo->expects('find')->with($addDoc1Id)->andReturn($addDoc1);
        $this->documentRepo->expects('find')->with($addDoc2Id)->andReturn($addDoc2);
        $this->documentRepo->expects('find')->with($removeDocId)->andReturn($removeDoc);

        $this->entityManager->expects('persist')->with(\Mockery::on(
            function (Inquiry $inquiry) use ($caseNr, $inquiryId): bool {
                self::assertEquals($this->organisation, $inquiry->getOrganisation());
                self::assertEquals($caseNr, $inquiry->getCasenr());

                // Set fake ID on doctrine entity
                $reflectionClass = new \ReflectionClass($inquiry::class);
                $idProperty = $reflectionClass->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($inquiry, $inquiryId);

                return true;
            }
        ));

        $dossierRepo = \Mockery::mock(WooDecisionRepository::class);
        $dossierRepo->shouldReceive('find')->with($newDossierId)->andReturn($newDossier);

        $this->entityManager->expects('persist')->with(\Mockery::type(Inquiry::class));
        $this->entityManager->expects('flush')->twice();
        $this->entityManager->expects('getRepository')->with(WooDecision::class)->andReturn($dossierRepo);

        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryId);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryArchivesMessage $message) use ($inquiryId) {
                self::assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($addDoc1Id, Document::class, false);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($addDoc2Id, Document::class, false);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($removeDocId, Document::class, false);

        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($this->dossierId);
        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($newDossierId);

        $this->inquiryService->updateInquiryLinks($this->organisation, $caseNr, [$addDoc1Id, $addDoc2Id], [$removeDocId], [$newDossierId]);
    }

    public function testUpdateInquiryLinksWithNewDossier(): void
    {
        $newDossierId = Uuid::v6();
        $newDossier = \Mockery::mock(WooDecision::class);
        $newDossier->shouldReceive('getId')->andReturn($newDossierId);
        $newDossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $addDoc1Id = Uuid::v6();
        $addDoc2Id = Uuid::v6();
        $removeDocId = Uuid::v6();

        $addDoc1 = \Mockery::mock(Document::class);
        $addDoc1->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));
        $addDoc1->shouldReceive('getId')->andReturn($addDoc1Id);

        $addDoc2 = \Mockery::mock(Document::class);
        $addDoc2->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));
        $addDoc2->shouldReceive('getId')->andReturn($addDoc2Id);

        $removeDoc = \Mockery::mock(Document::class);
        $removeDoc->shouldReceive('getId')->andReturn($removeDocId);

        $caseNr = 'case-123';
        $inquiryId = Uuid::v6();
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getCasenr')->andReturn($caseNr);
        $inquiry->expects('addDocument')->with($addDoc1);
        $inquiry->expects('addDocument')->with($addDoc2);
        $inquiry->expects('removeDocument')->with($removeDoc);
        $inquiry->expects('addDossier')->with($newDossier);
        $inquiry->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));
        $inquiry->shouldReceive('getId')->andReturn($inquiryId);

        $this->historyService->expects('addDossierEntry');

        $this->inquiryRepo->expects('findOneBy')->with(['organisation' => $this->organisation, 'casenr' => $caseNr])->andReturn($inquiry);

        $this->documentRepo->expects('find')->with($addDoc1Id)->andReturn($addDoc1);
        $this->documentRepo->expects('find')->with($addDoc2Id)->andReturn($addDoc2);
        $this->documentRepo->expects('find')->with($removeDocId)->andReturn($removeDoc);

        $dossierRepo = \Mockery::mock(WooDecisionRepository::class);
        $dossierRepo->shouldReceive('find')->with($newDossierId)->andReturn($newDossier);

        $this->entityManager->shouldReceive('getRepository')->with(WooDecision::class)->andReturn($dossierRepo);
        $this->entityManager->expects('persist')->with($inquiry);
        $this->entityManager->expects('flush');

        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryId);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryArchivesMessage $message) use ($inquiryId) {
                self::assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($addDoc1Id, Document::class, false);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($addDoc2Id, Document::class, false);
        $this->ingestDispatcher->expects('dispatchIngestMetadataOnlyCommand')->with($removeDocId, Document::class, false);

        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($newDossierId);

        $this->inquiryService->updateInquiryLinks($this->organisation, $caseNr, [$addDoc1Id, $addDoc2Id], [$removeDocId], [$newDossierId]);
    }

    public function testApplyChangesetAsync(): void
    {
        $organisationId = Uuid::v6();
        $organisation = \Mockery::mock(Organisation::class);
        $organisation->shouldReceive('getId')->andReturn($organisationId);
        $changeset = new InquiryChangeset($organisation);

        // Has no linked inquiries yet, so should be linked twice
        $docId123 = Uuid::v6();
        $changeset->updateCaseNrsForDocument(
            $this->createDocument($docId123, []),
            ['case-1', 'case-2'],
        );

        // Has two new inquiry links (case-1 and case-3), one unmodified/existing (case-2) and one removed ('case-4')
        $docId456 = Uuid::v6();
        $changeset->updateCaseNrsForDocument(
            $this->createDocument($docId456, ['case-2', 'case-4']),
            ['case-1', 'case-2', 'case-3']
        );

        // Docs 123 and 456 should be added to case-1
        $this->wooDecisionDispatcher->expects('dispatchUpdateInquiryLinksCommand')->with(
            $organisationId,
            'case-1',
            [$docId123, $docId456],
            [],
            [],
        );

        // Doc 123 should be added to case-2
        $this->wooDecisionDispatcher->expects('dispatchUpdateInquiryLinksCommand')->with(
            $organisationId,
            'case-2',
            [$docId123],
            [],
            [],
        );

        // Doc 456 should be removed from case-4
        $this->wooDecisionDispatcher->expects('dispatchUpdateInquiryLinksCommand')->with(
            $organisationId,
            'case-4',
            [],
            [$docId456],
            [],
        );

        // Doc 456 should be added to case-3
        $this->wooDecisionDispatcher->expects('dispatchUpdateInquiryLinksCommand')->with(
            $organisationId,
            'case-3',
            [$docId456],
            [],
            [],
        );

        $this->inquiryService->applyChangesetAsync($changeset);
    }

    /**
     * @param string[] $caseNumbers
     */
    private function createDocument(Uuid $id, array $caseNumbers): Document
    {
        $inquiries = new ArrayCollection();
        foreach ($caseNumbers as $caseNumber) {
            $inquiry = \Mockery::mock(Inquiry::class);
            $inquiry->shouldReceive('getCasenr')->andReturn($caseNumber);
            $inquiries->add($inquiry);
        }

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getInquiries')->andReturn($inquiries);
        $document->shouldReceive('getId')->andReturn($id);

        return $document;
    }
}
