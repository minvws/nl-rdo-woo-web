<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Service\DocumentService;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentServiceTest extends MockeryTestCase
{
    private DocumentService $documentService;
    private EntityManagerInterface|MockInterface $entityManager;
    private TranslatorInterface|MockInterface $translator;
    private IngestLogger|MockInterface $ingestLogger;
    private IngestService|MockInterface $ingester;
    private MockInterface|DocumentStorageService $documentStorageService;
    private ThumbnailStorageService|MockInterface $thumbnailStorageService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->ingestLogger = \Mockery::mock(IngestLogger::class);
        $this->ingester = \Mockery::mock(IngestService::class);
        $this->documentStorageService = \Mockery::mock(DocumentStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->ingester = \Mockery::mock(IngestService::class);

        $this->translator->shouldReceive('trans')->zeroOrMoreTimes();

        $this->documentService = new DocumentService(
            $this->entityManager,
            $this->ingestLogger,
            $this->translator,
            $this->ingester,
            $this->documentStorageService,
            $this->thumbnailStorageService,
        );

        parent::setUp();
    }

    public function testRemoveDocumentFromDossierThrowsExceptionWhenTheDocumentIsNotInTheDossier(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnFalse();

        $this->expectException(\RuntimeException::class);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesNotRemoveTheDocumentWhenItIsLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnTrue();

        $dossier->expects('removeDocument')->with($document);

        $document->expects('getDossiers->count')->andReturn(5);

        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnTrue();

        $dossier->expects('removeDocument')->with($document);

        $document->expects('getDossiers->count')->andReturn(0);

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->documentStorageService->expects('deleteAllFilesForDocument')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForDocument')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testWithDraw(): void
    {
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->documentStorageService->expects('deleteAllFilesForDocument')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForDocument')->with($document);

        $document->expects('withdraw')->with($reason, $explanation);

        $this->entityManager->expects('persist')->with($document);
        $this->entityManager->expects('flush');

        $this->ingester->expects('ingest')->with($document, \Mockery::type(Options::class));

        $this->ingestLogger->expects('success');

        $this->documentService->withdraw($document, $reason, $explanation);
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
