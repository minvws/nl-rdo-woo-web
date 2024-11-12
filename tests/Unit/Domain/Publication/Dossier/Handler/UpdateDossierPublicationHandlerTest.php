<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Handler\UpdateDossierPublicationHandler;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateDossierPublicationHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierPublisher&MockInterface $dossierPublisher;
    private UpdateDossierPublicationHandler $handler;
    private MockInterface&DossierService $dossierService;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierPublisher = \Mockery::mock(DossierPublisher::class);

        $this->handler = new UpdateDossierPublicationHandler(
            $this->dossierService,
            $this->messageBus,
            $this->dossierPublisher,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfullyForDirectPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('publish')->with($dossier);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeSuccessfullyForPreviewPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('publishAsPreview')->with($dossier);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeSuccessfullyForScheduledPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('schedulePublication')->with($dossier);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('isCompleted')->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($dossier)->andReturnFalse();

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }
}
