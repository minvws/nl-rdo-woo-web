<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\DocumentService;
use Shared\Service\HistoryService;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;

class DocumentServiceTest extends UnitTestCase
{
    private DocumentService $documentService;
    private EntityManagerInterface&MockInterface $entityManager;
    private MockInterface&EntityStorageService $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private HistoryService&MockInterface $historyService;

    protected function setUp(): void
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
        $document->shouldReceive('getDossiers->isEmpty')->andReturnTrue();

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
        $document->shouldReceive('getDossiers->isEmpty')->andReturnFalse();

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
        $document->shouldReceive('getDossiers->isEmpty')->andReturnTrue();

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->subTypeIndexer->expects('remove')->with($document);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }
}
