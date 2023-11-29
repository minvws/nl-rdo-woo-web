<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\DecisionDocument;
use App\Entity\Department;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Entity\GovernmentOfficial;
use App\Entity\Inquiry;
use App\Entity\Inventory;
use App\Entity\Judgement;
use App\Service\DocumentService;
use App\Service\DossierService;
use App\Service\DossierWorkflow\Step\DecisionStep;
use App\Service\DossierWorkflow\Step\DetailsStep;
use App\Service\DossierWorkflow\Step\DocumentsStep;
use App\Service\DossierWorkflow\Step\PublicationStep;
use App\Service\DossierWorkflow\WorkflowStatusFactory;
use App\Service\Inquiry\InquiryService;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierServiceTest extends MockeryTestCase
{
    private EntityManagerInterface|MockInterface $entityManager;
    private DossierService $dossierService;
    private LoggerInterface|MockInterface $logger;
    private MessageBusInterface|MockInterface $messageBus;
    private Dossier|MockInterface $dossier;
    private UploadedFile|MockInterface $decisionUpload;
    private MockInterface|DocumentStorageService $documentStorage;
    private DecisionDocument|MockInterface $decisionDocument;
    private InquirySessionService|MockInterface $inquirySession;
    private WorkflowStatusFactory $workflowFactory;
    private InquiryService|MockInterface $inquiryService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->messageBus = new CollectingMessageBus();
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->inquirySession = \Mockery::mock(InquirySessionService::class);
        $this->workflowFactory = new WorkflowStatusFactory(
            new DocumentsStep(),
            new DecisionStep(),
            new PublicationStep(),
            new DetailsStep(),
        );
        $this->inquiryService = \Mockery::mock(InquiryService::class);

        $this->dossierService = new DossierService(
            $this->entityManager,
            $this->messageBus,
            $this->logger,
            $this->inquirySession,
            $this->documentStorage,
            $this->workflowFactory,
            \Mockery::mock(DocumentService::class),
            $this->inquiryService,
        );

        $this->decisionDocument = $this->createDocument('decision', uploaded: true);
        $inventory = $this->createDocument('inventory', uploaded: true);

        $this->decisionUpload = \Mockery::mock(UploadedFile::class);
        $this->decisionUpload->shouldReceive('getClientOriginalExtension')->andReturn('pdf');
        $this->decisionUpload->shouldReceive('getRealPath')->zeroOrMoreTimes()->andReturn(__FILE__);
        $this->decisionUpload->shouldReceive('getClientOriginalName')->andReturn('xyz');
        $this->decisionUpload->shouldReceive('getSize')->andReturn(123);

        $this->documentStorage->shouldReceive('storeDocument')->with($this->decisionUpload, $this->decisionDocument)->andReturnTrue();

        $this->dossier = $this->createDossier();
        $this->dossier->setDecisionDocument($this->decisionDocument);
        $this->dossier->setInventory($inventory);

        parent::setUp();
    }

    public function testChangeStateIsRejectedWhenInvalid(): void
    {
        $this->dossier->setStatus(Dossier::STATUS_CONCEPT);

        $this->logger->expects('error')->with('Invalid state change', \Mockery::any());
        $this->expectException(\InvalidArgumentException::class);

        $this->dossierService->changeState($this->dossier, Dossier::STATUS_RETRACTED);
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

        $dossier = new Dossier();
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

        $dossier->setStatus(Dossier::STATUS_PUBLISHED);
        $this->assertTrue($this->dossierService->isViewingAllowed($dossier));

        $dossier->setStatus(Dossier::STATUS_RETRACTED);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([]);
        $dossier->setStatus(Dossier::STATUS_PREVIEW);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([$uuid1]);
        $this->assertTrue($this->dossierService->isViewingAllowed($dossier));

        $this->inquirySession->expects('getInquiries')->andReturn([$uuid2]);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));
        $this->inquirySession->expects('getInquiries')->andReturn([$uuid2]);
        $this->assertTrue($this->dossierService->isViewingAllowed($dossier, $document));
    }

    public function testIncompleteDossier()
    {
        $dossier = $this->createDossier();
        $this->assertFalse($dossier->isCompleted());

        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->dossierService->updateDetails($dossier);
        $this->assertFalse($dossier->isCompleted());
    }

    public function testCompletedDossier()
    {
        $document = $this->createDocument('regular', uploaded: true);
        $document2 = $this->createDocument('regular', uploaded: true);
        $decision = $this->createDocument('decision', uploaded: true);
        $inventory = $this->createDocument('inventory', uploaded: true);

        $dossier = $this->createDossier();
        $dossier->addDocument($document);
        $dossier->addDocument($document2);
        $dossier->setDecisionDocument($decision);
        $dossier->setInventory($inventory);
        $dossier->setPublicationDate(new \DateTimeImmutable('tomorrow'));
        $this->assertFalse($dossier->isCompleted());

        $this->entityManager->expects('persist')->with($dossier)->twice();
        $this->entityManager->expects('flush')->twice();

        $this->dossierService->updateDetails($dossier);
        $this->assertTrue($dossier->isCompleted());

        $dossier->setDecisionDocument(null);

        $this->dossierService->updateDetails($dossier);
        $this->assertFalse($dossier->isCompleted());
    }

    public function testInCompleteDossierWithNonuploadedDocument()
    {
        $document = $this->createDocument('regular', uploaded: true);
        $document2 = $this->createDocument('regular', uploaded: false);
        $decision = $this->createDocument('decision', uploaded: true);
        $inventory = $this->createDocument('inventory', uploaded: true);

        $dossier = $this->createDossier();
        $dossier->addDocument($document);
        $dossier->addDocument($document2);
        $dossier->setDecisionDocument($decision);
        $dossier->setInventory($inventory);
        $this->assertFalse($dossier->isCompleted());

        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->dossierService->updateDetails($dossier);
        $this->assertFalse($dossier->isCompleted());
    }

    protected function createDossier()
    {
        $dossier = new Dossier();
        $setId = \Closure::bind(fn ($id) => $this->id = $id, $dossier, $dossier);
        $setId(Uuid::v7());
        $dossier->setDossierNr('1000');
        $dossier->setDecision(Dossier::DECISION_PUBLIC);
        $dossier->setStatus(Dossier::STATUS_PREVIEW);
        $dossier->setSummary('aaa');
        $dossier->setTitle('aaa');
        $dossier->addDepartment(new Department());
        $dossier->addGovernmentOfficial(new GovernmentOfficial());
        $dossier->setDocumentPrefix('foo');
        $dossier->setPublicationReason('bar');
        $dossier->setDefaultSubjects(['baz']);
        $dossier->setCompleted(false);

        return $dossier;
    }

    protected function createDocument(string $type = 'regular', bool $uploaded = true): DecisionDocument|Inventory|Document
    {
        $fileInfo = new FileInfo();
        $fileInfo->setName($type . '.pdf');
        $fileInfo->setUploaded($uploaded);

        switch ($type) {
            default:
            case 'regular' :
                $document = new Document();
                $document->setDocumentNr('2000');
                $document->setJudgement(Judgement::PARTIAL_PUBLIC);
                break;
            case 'inventory' :
                $document = new Inventory();
                break;
            case 'decision':
                $document = new DecisionDocument();
                break;
        }

        $document->setFileInfo($fileInfo);

        return $document;
    }
}
