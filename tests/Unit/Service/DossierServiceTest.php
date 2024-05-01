<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\IndexDossierMessage;
use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Entity\Inquiry;
use App\Message\IngestDecisionMessage;
use App\Service\DossierService;
use App\Service\DossierWizard\WizardStatusFactory;
use App\Service\HistoryService;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierService $dossierService;
    private WooDecision&MockInterface $dossier;
    private MessageBusInterface&MockInterface $messageBus;
    private LoggerInterface&MockInterface $logger;
    private DocumentStorageService&MockInterface $documentStorage;
    private HistoryService&MockInterface $historyService;
    private InquirySessionService&MockInterface $inquirySession;
    private WizardStatusFactory&MockInterface $wizardStatusFactory;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->inquirySession = \Mockery::mock(InquirySessionService::class);
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->wizardStatusFactory = \Mockery::mock(WizardStatusFactory::class);

        $this->dossierService = new DossierService(
            $this->entityManager,
            $this->messageBus,
            $this->logger,
            $this->inquirySession,
            $this->documentStorage,
            $this->wizardStatusFactory,
            $this->historyService,
        );

        $this->entityManager->shouldReceive('getUnitOfWork->getOriginalEntityData')->andReturn([]);

        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        parent::setUp();
    }

    public function testIsAllowedToView(): void
    {
        $uuid1 = Uuid::v4();
        $inquiry1 = new Inquiry();
        $setId = \Closure::bind(fn ($id) => $this->id = $id, $inquiry1, $inquiry1);
        $setId($uuid1);
        $inquiry1->setCasenr('900');

        $uuid2 = Uuid::v4();
        $inquiry2 = new Inquiry();
        $setId = \Closure::bind(fn ($id) => $this->id = $id, $inquiry2, $inquiry2);
        $setId($uuid2);
        $inquiry2->setCasenr('901');

        $dossier = new WooDecision();
        $setId = \Closure::bind(fn ($id) => $this->id = $id, $dossier, $dossier);
        $setId(Uuid::v7());
        $dossier->setDossierNr('1000');

        $document = new Document();
        $document->setDocumentNr('2000');

        $dossier->addInquiry($inquiry1);
        $inquiry1->addDossier($dossier);
        $inquiry1->addDocument($document);

        $document->addInquiry($inquiry2);
        $inquiry2->addDocument($document);

        $dossier->setStatus(DossierStatus::PUBLISHED);
        self::assertTrue($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([]);
        $dossier->setStatus(DossierStatus::PREVIEW);
        self::assertFalse($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([$uuid1]);
        self::assertTrue($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([$uuid2]);
        self::assertFalse($this->dossierService->isViewingAllowed($dossier));
        $this->inquirySession->expects('getInquiries')->andReturn([$uuid2]);
        self::assertTrue($this->dossierService->isViewingAllowed($dossier, $document));
    }

    public function testIsViewingAllowedDefaultsToFalseForNonWooDecisionDossier(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);

        $result = $this->dossierService->isViewingAllowed($dossier);

        $this->assertFalse($result);
        $this->inquirySession->shouldNotHaveReceived('getInquiries');
    }

    public function testDossierCompletionIsSetToTrueWhenWorkflowStatusIsCompleted(): void
    {
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $this->wizardStatusFactory->expects('getWizardStatus->isCompleted')->andReturnTrue();

        $this->dossier->expects('setCompleted')->with(true);
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);

        $this->messageBus->expects('dispatch')
            ->with(\Mockery::type(IndexDossierMessage::class))
            ->andReturns(new Envelope(new \stdClass()));

        $this->dossierService->updateDetails($this->dossier);
    }

    public function testDossierCompletionIsSetToFalseWhenWorkflowStatusIsIncomplete(): void
    {
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $this->wizardStatusFactory->expects('getWizardStatus->isCompleted')->andReturnFalse();

        $this->dossier->expects('setCompleted')->with(false);
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);

        $this->messageBus->expects('dispatch')
            ->with(\Mockery::type(IndexDossierMessage::class))
            ->andReturns(new Envelope(new \stdClass()));

        $this->dossierService->updateDetails($this->dossier);
    }

    public function testUpdateDecisionDocumentForInitialUpload(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getRealPath')->andReturn(__FILE__);
        $upload->shouldReceive('getClientOriginalName')->andReturn('bar');
        $upload->shouldReceive('getSize')->andReturn(123);

        $this->logger->expects('info');
        $this->entityManager->expects('persist')->with(\Mockery::type(DecisionDocument::class));

        $this->documentStorage
            ->expects('storeDocument')
            ->with($upload, \Mockery::type(DecisionDocument::class))
            ->andReturnTrue();

        $this->wizardStatusFactory->expects('getWizardStatus->isCompleted')->andReturnTrue();

        $this->dossier->expects('setCompleted')->with(true);
        $this->dossier->expects('getDecisionDocument')->andReturnNull();
        $this->dossier->expects('setDecisionDocument')->with(\Mockery::type(DecisionDocument::class));
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $this->messageBus->expects('dispatch')
            ->with(\Mockery::type(IngestDecisionMessage::class))
            ->andReturns(new Envelope(new \stdClass()));

        $this->dossierService->updateDecisionDocument($upload, $this->dossier);
    }

    public function testUpdateDecisionDocument(): void
    {
        $decisionDocument = \Mockery::mock(DecisionDocument::class);
        $decisionDocument->shouldReceive('getFileInfo')->andReturn(new FileInfo());

        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getRealPath')->andReturn(__FILE__);
        $upload->shouldReceive('getClientOriginalName')->andReturn('bar');
        $upload->shouldReceive('getSize')->andReturn(123);

        $this->logger->expects('info');
        $this->entityManager->expects('persist')->with($decisionDocument);

        $this->documentStorage
            ->expects('storeDocument')
            ->with($upload, \Mockery::type(DecisionDocument::class))
            ->andReturnTrue();

        $this->wizardStatusFactory->expects('getWizardStatus->isCompleted')->andReturnTrue();

        $this->dossier->expects('setCompleted')->with(true);
        $this->dossier->expects('getDecisionDocument')->andReturn($decisionDocument);

        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $this->messageBus->expects('dispatch')
            ->with(\Mockery::type(IngestDecisionMessage::class))
            ->andReturns(new Envelope(new \stdClass()));

        $this->historyService
            ->expects('addDossierEntry')
            ->with($this->dossier, 'dossier_update_decision', \Mockery::any());

        $this->dossierService->updateDecisionDocument($upload, $this->dossier);
    }
}
