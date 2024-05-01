<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\Handler;

use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantPublicationCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantUpdatedEvent;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\UpdateCovenantPublicationHandler;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateCovenantPublicationHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierPublisher&MockInterface $dossierPublisher;
    private UpdateCovenantPublicationHandler $handler;
    private MockInterface&DossierService $dossierService;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierPublisher = \Mockery::mock(DossierPublisher::class);

        $this->handler = new UpdateCovenantPublicationHandler(
            $this->dossierService,
            $this->messageBus,
            $this->dossierPublisher,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfullyForDirectPublication(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);
        $covenant->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($covenant, false);

        $this->dossierPublisher->expects('canPublish')->with($covenant)->andReturnTrue();
        $this->dossierPublisher->expects('canSchedulePublication')->with($covenant)->andReturnTrue();

        $this->dossierService->expects('updateHistory')->with($covenant);
        $this->dossierService->expects('validateCompletion')->with($covenant);

        $this->dossierPublisher->expects('publish')->with($covenant);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantUpdatedEvent $message) use ($covenantUuid) {
                self::assertEquals($covenantUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateCovenantPublicationCommand($covenant)
        );
    }

    public function testInvokeSuccessfullyForScheduledPublication(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);
        $covenant->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($covenant, false);

        $this->dossierPublisher->expects('canPublish')->with($covenant)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($covenant)->andReturnTrue();

        $this->dossierService->expects('updateHistory')->with($covenant);
        $this->dossierService->expects('validateCompletion')->with($covenant);

        $this->dossierPublisher->expects('schedulePublication')->with($covenant);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantUpdatedEvent $message) use ($covenantUuid) {
                self::assertEquals($covenantUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateCovenantPublicationCommand($covenant)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);
        $covenant->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($covenant, false);

        $this->dossierPublisher->expects('canPublish')->with($covenant)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($covenant)->andReturnFalse();

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateCovenantPublicationCommand($covenant)
        );
    }
}
