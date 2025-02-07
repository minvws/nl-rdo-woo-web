<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Handler\UpdateDossierDetailsHandler;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateDossierDetailsHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateDossierDetailsHandler $handler;
    private MockInterface&DossierService $dossierService;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new UpdateDossierDetailsHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);

        $this->dossierWorkflowManager->expects('applyTransition')->with($covenant, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->expects('validateCompletion')->with($covenant);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierUpdatedEvent $message) use ($covenantUuid) {
                self::assertEquals($covenantUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateDossierDetailsCommand($covenant)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $covenant = \Mockery::mock(Covenant::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($covenant, DossierStatusTransition::UPDATE_DETAILS)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateDossierDetailsCommand($covenant)
        );
    }
}
