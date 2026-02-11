<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Handler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Handler\UpdateDossierContentHandler;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateDossierContentHandlerTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateDossierContentHandler $handler;
    private MockInterface&DossierService $dossierService;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new UpdateDossierContentHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);

        $this->dossierWorkflowManager->expects('applyTransition')->with($covenant, DossierStatusTransition::UPDATE_CONTENT);

        $this->dossierService->expects('validateCompletion')->with($covenant);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierUpdatedEvent $message) use ($covenantUuid) {
                self::assertEquals($covenantUuid, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new UpdateDossierContentCommand($covenant)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $covenant = Mockery::mock(Covenant::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($covenant, DossierStatusTransition::UPDATE_CONTENT)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateDossierContentCommand($covenant)
        );
    }
}
