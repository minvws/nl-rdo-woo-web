<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Handler;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierPublisher;
use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Handler\UpdateDossierPublicationHandler;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateDossierPublicationHandlerTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierPublisher&MockInterface $dossierPublisher;
    private UpdateDossierPublicationHandler $handler;
    private DossierService&MockInterface $dossierService;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->dossierPublisher = Mockery::mock(DossierPublisher::class);

        $this->handler = new UpdateDossierPublicationHandler(
            $this->dossierService,
            $this->messageBus,
            $this->dossierPublisher,
            new NullLogger(),
        );

        parent::setUp();
    }

    public function testInvokeSuccessfullyForDirectPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->expects('getId')->times(2)->andReturn($dossierId);

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('publish')->with($dossier);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeSuccessfullyForPreviewPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->expects('getId')->times(2)->andReturn($dossierId);

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('publishAsPreview')->with($dossier);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeSuccessfullyForScheduledPublication(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->expects('getId')->times(2)->andReturn($dossierId);

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($dossier)->andReturnTrue();

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->dossierPublisher->expects('schedulePublication')->with($dossier);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierUpdatedEvent $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->expects('getId')->times(3)->andReturn($dossierId);

        $this->dossierService->expects('validateCompletion')->with($dossier, false);

        $this->dossierPublisher->expects('canPublish')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($dossier)->andReturnFalse();

        $this->handler->__invoke(
            new UpdateDossierPublicationCommand($dossier)
        );
    }
}
