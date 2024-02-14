<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Message\IngestMetadataOnlyMessage;
use App\Service\DocumentService;
use App\Service\Elastic\ElasticService;
use App\Service\FileProcessService;
use App\Service\HistoryService;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
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
    private ElasticService|MockInterface $elasticService;
    private MessageBusInterface|MockInterface $messageBus;
    private FileProcessService|MockInterface $fileProcessService;
    private HistoryService|MockInterface $historyService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->ingestLogger = \Mockery::mock(IngestLogger::class);
        $this->ingester = \Mockery::mock(IngestService::class);
        $this->documentStorageService = \Mockery::mock(DocumentStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->ingester = \Mockery::mock(IngestService::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->fileProcessService = \Mockery::mock(FileProcessService::class);
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->translator->shouldReceive('trans');

        $this->historyService->shouldReceive('addDocumentEntry');

        $this->documentService = new DocumentService(
            $this->entityManager,
            $this->ingestLogger,
            $this->translator,
            $this->ingester,
            $this->documentStorageService,
            $this->thumbnailStorageService,
            $this->elasticService,
            $this->messageBus,
            $this->fileProcessService,
            $this->historyService
        );

        parent::setUp();
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotInTheCurrentDossierAndNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnFalse();
        $document->expects('getDocumentNr')->andReturns('abc-123');
        $document->expects('getDossiers->count')->andReturn(0);

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('flush');

        $this->elasticService->expects('removeDocument')->with('abc-123');

        $this->documentStorageService->expects('deleteAllFilesForDocument')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForDocument')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesNotRemoveTheDocumentWhenItIsLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnTrue();
        $document->expects('getDossiers->count')->andReturn(5);

        $dossier->expects('removeDocument')->with($document);

        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->elasticService->expects('updateDocument')->with($document);

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }

    public function testRemoveDocumentFromDossierDoesRemoveTheDocumentWhenItIsNotLinkedToOtherDossiers(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $document = \Mockery::mock(Document::class);

        $dossier->expects('removeDocument')->with($document);

        $document->expects('getDossiers->contains')->with($dossier)->andReturnTrue();
        $document->expects('getDocumentNr')->andReturns('abc-123');
        $document->expects('getDossiers->count')->andReturn(0);

        $this->entityManager->expects('remove')->with($document);
        $this->entityManager->expects('persist')->with($dossier);
        $this->entityManager->expects('flush');

        $this->elasticService->expects('removeDocument')->with('abc-123');

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

        $uuid = Uuid::v6();
        $document->expects('withdraw')->with($reason, $explanation);
        $document->expects('getId')->andReturn($uuid);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IngestMetadataOnlyMessage $message) use ($uuid) {
                return $message->getUuid() === $uuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->entityManager->expects('persist')->with($document);
        $this->entityManager->expects('flush');

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
