<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\WithDrawDocumentHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use App\Exception\DocumentWorkflowException;
use App\Message\IngestMetadataOnlyMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Repository\DocumentRepository;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class WithDrawDocumentHandlerTest extends MockeryTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private MockInterface&DocumentStorageService $documentStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private WithDrawDocumentHandler $handler;

    public function setUp(): void
    {
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->documentStorageService = \Mockery::mock(DocumentStorageService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new WithDrawDocumentHandler(
            $this->documentRepository,
            $this->documentStorageService,
            $this->thumbnailStorageService,
            $this->messageBus,
            $this->dossierWorkflowManager,
        );

        parent::setUp();
    }

    public function testWithDrawSuccessfully(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);

        $secondDossierUuid = Uuid::v6();
        $secondDossier = \Mockery::mock(WooDecision::class);
        $secondDossier->shouldReceive('getId')->andReturn($secondDossierUuid);

        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->documentStorageService->expects('deleteAllFilesForDocument')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForDocument')->with($document);

        $uuid = Uuid::v6();
        $document->expects('withdraw')->with($reason, $explanation);
        $document->shouldReceive('getId')->andReturn($uuid);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier, $secondDossier]));
        $document->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $document->shouldReceive('isWithdrawn')->andReturnFalse();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IngestMetadataOnlyMessage $message) use ($uuid) {
                return $message->getUuid() === $uuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierArchivesMessage $message) use ($dossierUuid) {
                return $message->getUuid() === $dossierUuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierArchivesMessage $message) use ($secondDossierUuid) {
                return $message->getUuid() === $secondDossierUuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DocumentWithDrawnEvent $message) use ($uuid) {
                return $message->document->getId() === $uuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->documentRepository->expects('save')->with($document, true);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->handler->__invoke(
            new WithDrawDocumentCommand($dossier, $document, $reason, $explanation)
        );
    }

    public function testWithDrawIsBlockedWhenDocumentHasNoUpload(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->expectExceptionObject(DocumentWorkflowException::forActionNotAllowed($document, 'withdraw'));

        $this->handler->__invoke(
            new WithDrawDocumentCommand($dossier, $document, $reason, $explanation)
        );
    }
}
