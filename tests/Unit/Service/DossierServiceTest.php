<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DossierService;
use App\Service\InventoryService;
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
    private UploadedFile|MockInterface $file;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->inventoryService = \Mockery::mock(InventoryService::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->messageBus = new CollectingMessageBus();

        $this->dossierService = new DossierService(
            $this->entityManager,
            $this->inventoryService,
            $this->messageBus,
            $this->logger,
        );

        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->file = \Mockery::mock(UploadedFile::class);

        parent::setUp();
    }

    public function testCreateIsRolledBackWhenProcessingOfInventoryFileReturnsErrors(): void
    {
        $this->dossier->expects('setCreatedAt');
        $this->dossier->expects('setUpdatedAt');
        $this->dossier->expects('setStatus')->with(Dossier::STATUS_CONCEPT);
        $this->dossier->expects('setDossierNr');

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $errors = [
            'an error',
            'another error',
        ];

        $this->inventoryService->expects('processInventory')->with($this->file, $this->dossier)->andReturns($errors);

        $this->entityManager->expects('rollback');
        $this->logger->expects('info')->with('Dossier creation failed', \Mockery::any());

        $result = $this->dossierService->create($this->dossier, $this->file);

        $this->assertEquals($result, $errors);
    }

    public function testCreateWithInventoryFileIsPersistedOnSuccess(): void
    {
        $this->dossier->expects('setCreatedAt');
        $this->dossier->expects('setUpdatedAt');
        $this->dossier->expects('setStatus')->with(Dossier::STATUS_CONCEPT);
        $this->dossier->expects('setDossierNr');

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $errors = [];

        $this->inventoryService->expects('processInventory')->with($this->file, $this->dossier)->andReturns($errors);

        $this->entityManager->expects('commit');
        $this->logger->expects('info')->with('Dossier created', \Mockery::any());

        $result = $this->dossierService->create($this->dossier, $this->file);

        $this->assertEquals($result, $errors);
    }

    public function testUpdateIsRolledBackWhenProcessingOfInventoryFileReturnsErrors(): void
    {
        $this->dossier->expects('setUpdatedAt');

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $errors = [
            'an error',
            'another error',
        ];

        $this->inventoryService->expects('processInventory')->with($this->file, $this->dossier)->andReturns($errors);

        $this->entityManager->expects('rollback');
        $this->logger->expects('info')->with('Dossier update failed', \Mockery::any());

        $result = $this->dossierService->update($this->dossier, $this->file);

        $this->assertEquals($result, $errors);
    }

    public function testUpdateWithInventoryFileIsPersistedOnSuccess(): void
    {
        $this->dossier->expects('setUpdatedAt');

        $this->entityManager->expects('beginTransaction');
        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('flush');

        $errors = [];

        $this->inventoryService->expects('processInventory')->with($this->file, $this->dossier)->andReturns($errors);

        $this->entityManager->expects('commit');
        $this->logger->expects('info')->with('Dossier updated', \Mockery::any());

        $result = $this->dossierService->update($this->dossier, $this->file);

        $this->assertEquals($result, $errors);
        $this->assertCount(1, $this->messageBus->dispatchedMessages());
    }

    public function testRemoveOnlyDeletesDocumentsThatAreNotRelatedToOtherDossiers(): void
    {
        $uniqueDocument = \Mockery::mock(Document::class);
        $uniqueDocument->expects('getDossiers->count')->andReturns(1);

        $reusedDocument = \Mockery::mock(Document::class);
        $reusedDocument->expects('getDossiers->count')->andReturns(3);

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$uniqueDocument, $reusedDocument]));

        $this->entityManager->expects('remove')->with($uniqueDocument);
        $this->entityManager->expects('remove')->with($this->dossier);
        $this->entityManager->expects('flush');

        $this->dossierService->remove($this->dossier);
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

    public function testChangeToCompletedIsRejectedWhenDocumentsAreMissing(): void
    {
        $newState = Dossier::STATUS_COMPLETED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnTrue();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $docA = \Mockery::mock(Document::class);
        $docA->expects('isUploaded')->andReturnTrue();

        $docB = \Mockery::mock(Document::class);
        $docB->expects('isUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$docA, $docB]));

        $this->logger->expects('error')->with('Invalid state change', \Mockery::any());
        $this->expectException(\InvalidArgumentException::class);

        $this->dossierService->changeState($this->dossier, $newState);
    }

    public function testChangeToCompletedIsAcceptedWhenDocumentsAreUploaded(): void
    {
        $newState = Dossier::STATUS_COMPLETED;
        $this->dossier->expects('isAllowedState')->with($newState)->andReturnTrue();
        $this->dossier->shouldReceive('getStatus')->andReturn(Dossier::STATUS_CONCEPT);

        $docA = \Mockery::mock(Document::class);
        $docA->expects('isUploaded')->andReturnTrue();

        $docB = \Mockery::mock(Document::class);
        $docB->expects('isUploaded')->andReturnTrue();

        $this->dossier->expects('getDocuments')->andReturns(new ArrayCollection([$docA, $docB]));
        $this->dossier->expects('setStatus')->with($newState);
        $this->entityManager->expects('flush');

        $this->logger->expects('info')->with('Dossier state changed', \Mockery::any());

        $this->dossierService->changeState($this->dossier, $newState);

        $this->assertCount(1, $this->messageBus->dispatchedMessages());
    }
}
