<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Message\GenerateInquiryArchivesMessage;
use App\Message\GenerateInquiryInventoryMessage;
use App\Repository\DocumentRepository;
use App\Repository\InquiryRepository;
use App\Service\BatchDownloadService;
use App\Service\HistoryService;
use App\Service\Inquiry\InquiryService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class InquiryServiceTest extends MockeryTestCase
{
    private EntityManagerInterface|MockInterface $entityManager;
    private MessageBusInterface|MockInterface $messageBus;
    private BatchDownloadService|MockInterface $batchDownloads;
    private MockInterface|DocumentStorageService $documentStorage;
    private HistoryService|MockInterface $historyService;
    private InquiryService $inquiryService;
    private Organisation|MockInterface $organisation;
    private InquiryRepository|MockInterface $inquiryRepo;
    private DocumentRepository|MockInterface $documentRepo;
    private Dossier|MockInterface $dossier;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->batchDownloads = \Mockery::mock(BatchDownloadService::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->inquiryService = new InquiryService(
            $this->entityManager,
            $this->messageBus,
            $this->batchDownloads,
            $this->documentStorage,
            $this->historyService,
        );

        $this->organisation = \Mockery::mock(Organisation::class);
        $this->organisation->expects('getId->toRfc4122')->andReturn('dummy-org-id-123');

        $this->inquiryRepo = \Mockery::mock(InquiryRepository::class);
        $this->documentRepo = \Mockery::mock(DocumentRepository::class);

        $this->entityManager->expects('getRepository')->with(Inquiry::class)->andReturn($this->inquiryRepo);
        $this->entityManager->expects('getRepository')->with(Document::class)->zeroOrMoreTimes()->andReturn($this->documentRepo);

        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier->shouldReceive('isPubliclyAvailable')->andReturnTrue();

        parent::setUp();
    }

    public function testUpdateDocumentsForCaseCreatesNewInquiryWhenNotFound(): void
    {
        $addDoc1Id = Uuid::v6();
        $addDoc2Id = Uuid::v6();
        $removeDocId = Uuid::v6();

        $addDoc1 = \Mockery::mock(Document::class);
        $addDoc1->expects('addInquiry');
        $addDoc1->expects('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));

        $addDoc2 = \Mockery::mock(Document::class);
        $addDoc2->expects('addInquiry');
        $addDoc2->expects('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));

        $removeDoc = \Mockery::mock(Document::class);
        $removeDoc->expects('removeInquiry');

        $caseNr = 'case-123';
        $inquiryId = Uuid::v6();

        $this->historyService->expects('addDossierEntry')->with($this->dossier, 'inquiry_added', ['count' => 1, 'casenrs' => $caseNr]);
        $this->inquiryRepo->expects('findOneBy')->with(['organisation' => $this->organisation, 'casenr' => $caseNr])->andReturnNull();

        $this->documentRepo->expects('find')->with($addDoc1Id)->andReturn($addDoc1);
        $this->documentRepo->expects('find')->with($addDoc2Id)->andReturn($addDoc2);
        $this->documentRepo->expects('find')->with($removeDocId)->andReturn($removeDoc);

        $this->entityManager->expects('persist')->with(\Mockery::on(
            function (Inquiry $inquiry) use ($caseNr, $inquiryId): bool {
                $this->assertEquals($this->organisation, $inquiry->getOrganisation());
                $this->assertEquals($caseNr, $inquiry->getCasenr());

                // Set fake ID on doctrine entity
                $reflectionClass = new \ReflectionClass(get_class($inquiry));
                $idProperty = $reflectionClass->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($inquiry, $inquiryId);

                return true;
            }
        ));
        $this->entityManager->expects('persist')->with(\Mockery::type(Inquiry::class));
        $this->entityManager->expects('flush');

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryInventoryMessage $message) use ($inquiryId) {
                $this->assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryArchivesMessage $message) use ($inquiryId) {
                $this->assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->inquiryService->updateDocumentsForCase($this->organisation, $caseNr, [$addDoc1Id, $addDoc2Id], [$removeDocId]);
    }

    public function testUpdateDocumentsForCaseUpdatesExistingInquiry(): void
    {
        $addDoc1Id = Uuid::v6();
        $addDoc2Id = Uuid::v6();
        $removeDocId = Uuid::v6();

        $addDoc1 = \Mockery::mock(Document::class);
        $addDoc1->expects('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));

        $addDoc2 = \Mockery::mock(Document::class);
        $addDoc2->expects('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));

        $removeDoc = \Mockery::mock(Document::class);

        $caseNr = 'case-123';
        $inquiryId = Uuid::v6();
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->expects('getCasenr')->zeroOrMoreTimes()->andReturn($caseNr);
        $inquiry->expects('addDocument')->with($addDoc1);
        $inquiry->expects('addDocument')->with($addDoc2);
        $inquiry->expects('removeDocument')->with($removeDoc);
        $inquiry->expects('getDossiers')->zeroOrMoreTimes()->andReturn(new ArrayCollection([$this->dossier]));
        $inquiry->expects('getId')->zeroOrMoreTimes()->andReturn($inquiryId);

        $this->inquiryRepo->expects('findOneBy')->with(['organisation' => $this->organisation, 'casenr' => $caseNr])->andReturn($inquiry);

        $this->documentRepo->expects('find')->with($addDoc1Id)->andReturn($addDoc1);
        $this->documentRepo->expects('find')->with($addDoc2Id)->andReturn($addDoc2);
        $this->documentRepo->expects('find')->with($removeDocId)->andReturn($removeDoc);

        $this->entityManager->expects('persist')->with($inquiry);
        $this->entityManager->expects('flush');

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryInventoryMessage $message) use ($inquiryId) {
                $this->assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (GenerateInquiryArchivesMessage $message) use ($inquiryId) {
                $this->assertEquals($inquiryId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->inquiryService->updateDocumentsForCase($this->organisation, $caseNr, [$addDoc1Id, $addDoc2Id], [$removeDocId]);
    }
}
