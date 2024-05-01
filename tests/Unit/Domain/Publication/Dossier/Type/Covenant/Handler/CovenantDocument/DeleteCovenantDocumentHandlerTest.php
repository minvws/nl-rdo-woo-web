<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Dossier\Type\Covenant\Command\DeleteCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantDocumentDeletedEvent;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CovenantDocumentNotFoundException;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\DeleteCovenantDocumentHandler;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\CovenantRepository;
use App\Service\Storage\DocumentStorageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Exception\TransitionException;

class DeleteCovenantDocumentHandlerTest extends MockeryTestCase
{
    private CovenantDocumentRepository&MockInterface $covenantDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DeleteCovenantDocumentHandler $handler;
    private MockInterface&CovenantRepository $dossierRepository;
    private DocumentStorageService&MockInterface $documentStorage;

    public function setUp(): void
    {
        $this->covenantDocumentRepository = \Mockery::mock(CovenantDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(CovenantRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);

        $this->handler = new DeleteCovenantDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->covenantDocumentRepository,
            $this->dossierRepository,
            $this->documentStorage,
        );

        parent::setUp();
    }

    public function testEntityIsDeleted(): void
    {
        $docUuid = Uuid::v6();
        $covenantDocument = \Mockery::mock(CovenantDocument::class);
        $covenantDocument->shouldReceive('getId')->andReturn($docUuid);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturn($covenantDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::DELETE_COVENANT_DOCUMENT);

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($covenantDocument);
        $this->covenantDocumentRepository->expects('remove')->with($covenantDocument, true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantDocumentDeletedEvent $message) use ($covenantDocument) {
                return $message->covenantDocumentId === $covenantDocument->getId();
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->documentStorage->expects('removeFileForEntity')->with($covenantDocument);

        $this->handler->__invoke(
            new DeleteCovenantDocumentCommand($dossierUuid)
        );
    }

    public function testEntityIsNotDeletedWhenTheWorkflowTransitionFails(): void
    {
        $docUuid = Uuid::v6();
        $covenantDocument = \Mockery::mock(CovenantDocument::class);
        $covenantDocument->shouldReceive('getId')->andReturn($docUuid);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturn($covenantDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $transition = DossierStatusTransition::DELETE_COVENANT_DOCUMENT;
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition)
            ->andThrows(
                DossierWorkflowException::forTransitionFailed(
                    $dossier,
                    $transition,
                    \Mockery::mock(TransitionException::class),
                )
            );

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($covenantDocument);

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new DeleteCovenantDocumentCommand($dossierUuid)
        );
    }

    public function testExceptionIsThrownWhenDocumentCannotBeFound(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturnNull();

        $this->expectException(CovenantDocumentNotFoundException::class);

        $this->handler->__invoke(
            new DeleteCovenantDocumentCommand($dossierUuid)
        );
    }
}
