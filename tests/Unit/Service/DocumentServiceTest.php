<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\DocumentService;
use App\Service\HistoryService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentServiceTest extends MockeryTestCase
{
    private DocumentService $documentService;
    private EntityManagerInterface&MockInterface $entityManager;
    private MockInterface&EntityStorageService $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private HistoryService&MockInterface $historyService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->historyService->shouldReceive('addDocumentEntry');

        $this->documentService = new DocumentService(
            $this->entityManager,
            $this->entityStorageService,
            $this->thumbnailStorageService,
            $this->subTypeIndexer,
            $this->historyService,
        );

        parent::setUp();
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotInTheCurrentDossierAndNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
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
        $dossier = \Mockery::mock(WooDecision::class);
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
        $dossier = \Mockery::mock(WooDecision::class);
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
}
