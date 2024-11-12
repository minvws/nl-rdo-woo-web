<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Entity\Document;
use App\Service\DocumentService;
use App\Service\HistoryService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

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
    private DocumentDispatcher&MockInterface $documentDispatcher;

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
        $this->documentDispatcher = \Mockery::mock(DocumentDispatcher::class);

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
            $this->documentDispatcher,
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

    public function testReplace(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn($wooDecisionId = Uuid::v6());

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDocumentId')->andReturn($documentId = 'foo-123');
        $document->shouldReceive('getId')->andReturn($documentEntityId = Uuid::v6());
        $document->shouldReceive('getFileInfo->getType')->andReturn(FileType::PDF->value);
        $document->shouldReceive('getFileInfo->getSize')->andReturn(1234);

        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getClientOriginalName')->andReturn($filename = 'foo.pdf');

        $this->documentNumberExtractor->expects('extract')->with($filename, $wooDecision)->andReturn($documentId);

        $this->entityStorageService->expects('store')->andReturnTrue();

        $this->documentDispatcher->expects('dispatchReplaceDocumentCommand')->with(
            $wooDecisionId,
            $documentEntityId,
            \Mockery::any(),
            $filename,
        );

        $this->documentService->replace($wooDecision, $document, $upload);
    }
}
