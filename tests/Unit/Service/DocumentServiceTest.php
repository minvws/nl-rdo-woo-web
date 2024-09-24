<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DocumentService;
use App\Service\HistoryService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DocumentServiceTest extends MockeryTestCase
{
    private DocumentService $documentService;
    private EntityManagerInterface&MockInterface $entityManager;
    private SubTypeIngester&MockInterface $ingester;
    private MockInterface&EntityStorageService $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private MessageBusInterface&MockInterface $messageBus;
    private HistoryService&MockInterface $historyService;
    private DocumentNumberExtractor&MockInterface $documentNumberExtractor;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->ingester = \Mockery::mock(SubTypeIngester::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->ingester = \Mockery::mock(SubTypeIngester::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->documentNumberExtractor = \Mockery::mock(DocumentNumberExtractor::class);

        $this->historyService->shouldReceive('addDocumentEntry');

        $this->documentService = new DocumentService(
            $this->entityManager,
            $this->ingester,
            $this->entityStorageService,
            $this->thumbnailStorageService,
            $this->subTypeIndexer,
            $this->messageBus,
            $this->historyService,
            $this->documentNumberExtractor,
        );

        parent::setUp();
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotInTheCurrentDossierAndNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->shouldReceive('getDossiers->contains')->with($dossier)->andReturnFalse();
        $document->shouldReceive('getDossiers->count')->andReturn(0);

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('flush');

        $this->subTypeIndexer->expects('remove')->with($document);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesNotRemoveTheDocumentWhenItIsLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnTrue();
        $document->shouldReceive('getDossiers->count')->andReturn(5);

        $dossier->expects('removeDocument')->with($document);

        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->subTypeIndexer->expects('index')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $dossier->expects('removeDocument')->with($document);

        $document->shouldReceive('getDossiers->contains')->with($dossier)->andReturnTrue();
        $document->shouldReceive('getDossiers->count')->andReturn(0);

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->subTypeIndexer->expects('remove')->with($document);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        \Mockery::close();
    }
}
