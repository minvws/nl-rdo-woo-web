<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Entity\Inquiry;
use App\Service\DossierService;
use App\Service\InquiryService;
use App\Service\Inventory\InventoryService;
use App\Service\Inventory\ProcessInventoryResult;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
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
    private InventoryService|MockInterface $inventoryService;
    private LoggerInterface|MockInterface $logger;
    private MessageBusInterface|MockInterface $messageBus;

    private Dossier|MockInterface $dossier;
    private UploadedFile|MockInterface $inventoryUpload;
    private UploadedFile|MockInterface $decisionUpload;
    private MockInterface|DocumentStorageService $documentStorage;
    private DecisionDocument|MockInterface $decisionDocument;
    private InquiryService|MockInterface $inquiryService;
    private FileInfo|MockInterface $decisionFileInfo;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->inventoryService = \Mockery::mock(InventoryService::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->messageBus = new CollectingMessageBus();
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->inquiryService = \Mockery::mock(InquiryService::class);

        $this->dossierService = new DossierService(
            $this->entityManager,
            $this->inventoryService,
            $this->messageBus,
            $this->logger,
            $this->inquiryService,
            $this->documentStorage,
        );

        $this->decisionFileInfo = \Mockery::mock(FileInfo::class);
        $this->decisionFileInfo->shouldReceive('setSourceType');
        $this->decisionFileInfo->shouldReceive('setType');
        $this->decisionFileInfo->shouldReceive('setName');

        $this->decisionDocument = \Mockery::mock(DecisionDocument::class);
        $this->decisionDocument->shouldReceive('getFileInfo')->andReturn($this->decisionFileInfo);

        $this->inventoryUpload = \Mockery::mock(UploadedFile::class);
        $this->decisionUpload = \Mockery::mock(UploadedFile::class);
        $this->decisionUpload->shouldReceive('getClientOriginalExtension')->andReturn('pdf');

        $this->documentStorage->shouldReceive('storeDocument')->with($this->decisionUpload, $this->decisionDocument)->andReturnTrue();

        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $this->dossier->shouldReceive('getDossierNr')->andReturn('test-123');
        $this->dossier->shouldReceive('getDecisionDocument')->andReturn($this->decisionDocument);
        $this->dossier->shouldReceive('setDecisionDocument')->with($this->decisionDocument);

        parent::setUp();
    }

    public function testCreateIsRolledBackWhenProcessingOfInventoryFileReturnsErrors(): void
    {
        $this->dossier->expects('setStatus')->with(Dossier::STATUS_CONCEPT);

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->dossier)->once();
        $this->entityManager->expects('persist')->with($this->decisionDocument);
        $this->entityManager->expects('flush');

        $expectedResult = new ProcessInventoryResult();
        $expectedResult->addGenericError('some error');

        $this->inventoryService->expects('processInventory')->with(
            $this->inventoryUpload,
            $this->dossier,
        )->andReturns($expectedResult);

        $this->entityManager->expects('rollback');
        $this->logger->expects('info')->with('Dossier creation failed', \Mockery::any());

        $actualResult = $this->dossierService->create($this->dossier, $this->inventoryUpload, $this->decisionUpload);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCreateWithInventoryFileIsPersistedOnSuccess(): void
    {
        $this->dossier->expects('setStatus')->with(Dossier::STATUS_CONCEPT);

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->decisionDocument);
        $this->entityManager->expects('persist')->with($this->dossier)->once();
        $this->entityManager->expects('flush');

        $expectedResult = new ProcessInventoryResult();

        $this->inventoryService->expects('processInventory')->with(
            $this->inventoryUpload,
            $this->dossier,
        )->andReturns($expectedResult);

        $this->entityManager->expects('commit');
        $this->logger->expects('info')->with('Dossier created', \Mockery::any());

        $actualResult = $this->dossierService->create($this->dossier, $this->inventoryUpload, $this->decisionUpload);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testUpdateIsRolledBackWhenProcessingOfInventoryFileReturnsErrors(): void
    {
        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->decisionDocument);
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $expectedResult = new ProcessInventoryResult();
        $expectedResult->addGenericError('some error');

        $this->inventoryService->expects('processInventory')->with(
            $this->inventoryUpload,
            $this->dossier,
        )->andReturns($expectedResult);

        $this->entityManager->expects('rollback');
        $this->logger->expects('info')->with('Dossier update failed', \Mockery::any());

        $actualResult = $this->dossierService->update($this->dossier, $this->inventoryUpload, $this->decisionUpload);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testUpdateWithInventoryFileIsPersistedOnSuccess(): void
    {
        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->decisionDocument);
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $expectedResult = new ProcessInventoryResult();

        $this->inventoryService->expects('processInventory')->with(
            $this->inventoryUpload,
            $this->dossier,
        )->andReturns($expectedResult);

        $this->entityManager->expects('commit');
        $this->logger->expects('info')->with('Dossier updated', \Mockery::any());

        $actualResult = $this->dossierService->update($this->dossier, $this->inventoryUpload, $this->decisionUpload);

        $this->assertEquals($expectedResult, $actualResult);
        $this->assertCount(1, $this->messageBus->dispatchedMessages());
    }

    public function testChangeStateIsRejectedWhenInvalid(): void
    {
        $newState = Dossier::STATUS_PUBLISHED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnFalse();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $this->logger->expects('error')->with('Invalid state change', \Mockery::any());
        $this->expectException(\InvalidArgumentException::class);

        $this->dossierService->changeState($this->dossier, $newState);
    }

    public function testChangeToCompletedIsRejectedWhenRequiredDocumentsAreMissing(): void
    {
        $newState = Dossier::STATUS_COMPLETED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnTrue();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $docRequiredNoUpload = \Mockery::mock(Document::class);
        $docRequiredNoUpload->expects('isUploaded')->andReturnFalse();
        $docRequiredNoUpload->expects('shouldBeUploaded')->andReturnTrue();

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$docRequiredNoUpload]));

        $this->logger->expects('error')->with('Invalid state change', \Mockery::any());
        $this->expectException(\InvalidArgumentException::class);

        $this->dossierService->changeState($this->dossier, $newState);
    }

    public function testChangeToCompletedIsRejectedWhenDecisionDocumentsIsMissing(): void
    {
        $newState = Dossier::STATUS_COMPLETED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnTrue();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $docRequired = \Mockery::mock(Document::class);
        $docRequired->expects('isUploaded')->andReturnTrue();
        $docRequired->expects('shouldBeUploaded')->andReturnTrue();

        $docNotRequired = \Mockery::mock(Document::class);
        $docNotRequired->expects('shouldBeUploaded')->andReturnFalse();
        $docNotRequired->shouldNotReceive('isUploaded');

        $this->decisionFileInfo->expects('isUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$docRequired, $docNotRequired]));

        $this->expectException(\InvalidArgumentException::class);

        $this->dossierService->changeState($this->dossier, $newState);
    }

    public function testChangeToCompletedIsAcceptedWhenRequiredDocumentsAreUploaded(): void
    {
        $newState = Dossier::STATUS_COMPLETED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnTrue();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $docRequired = \Mockery::mock(Document::class);
        $docRequired->expects('isUploaded')->andReturnTrue();
        $docRequired->expects('shouldBeUploaded')->andReturnTrue();

        $docNotRequired = \Mockery::mock(Document::class);
        $docNotRequired->expects('shouldBeUploaded')->andReturnFalse();
        $docNotRequired->shouldNotReceive('isUploaded');

        $this->decisionFileInfo->expects('isUploaded')->andReturnTrue();

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$docRequired, $docNotRequired]));
        $this->dossier->expects('setStatus')->with($newState);
        $this->entityManager->expects('flush');

        $this->logger->expects('info')->with('Dossier state changed', \Mockery::any());

        $this->dossierService->changeState($this->dossier, $newState);

        $this->assertCount(1, $this->messageBus->dispatchedMessages());
    }

    public function testIsAllowedToView()
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

        $dossier->setStatus(Dossier::STATUS_COMPLETED);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));

        $dossier->setStatus(Dossier::STATUS_RETRACTED);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));

        $this->inquiryService->expects('getInquiries')->andReturn([]);
        $dossier->setStatus(Dossier::STATUS_PREVIEW);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));

        $this->inquiryService->expects('getInquiries')->andReturn([$uuid1]);
        $this->assertTrue($this->dossierService->isViewingAllowed($dossier));

        $this->inquiryService->expects('getInquiries')->andReturn([$uuid2]);
        $this->assertFalse($this->dossierService->isViewingAllowed($dossier));
        $this->inquiryService->expects('getInquiries')->andReturn([$uuid2]);
        $this->assertTrue($this->dossierService->isViewingAllowed($dossier, $document));
    }
}
