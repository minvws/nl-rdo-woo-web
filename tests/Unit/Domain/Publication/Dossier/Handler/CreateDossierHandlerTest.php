<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Handler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Command\CreateDossierCommand;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Domain\Publication\Dossier\Handler\CreateDossierHandler;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CreateDossierHandlerTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private CreateDossierHandler $handler;
    private MockInterface&DossierService $dossierService;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new CreateDossierHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $annualReportUuid = Uuid::v6();
        $annualReport = Mockery::mock(AnnualReport::class);
        $annualReport->shouldReceive('getId')->andReturn($annualReportUuid);

        $this->dossierWorkflowManager->expects('applyTransition')->with($annualReport, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->expects('validateCompletion')->with($annualReport);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierCreatedEvent $message) use ($annualReportUuid) {
                self::assertEquals($annualReportUuid, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new CreateDossierCommand($annualReport)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $annualReport = Mockery::mock(AnnualReport::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($annualReport, DossierStatusTransition::UPDATE_DETAILS)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new CreateDossierCommand($annualReport)
        );
    }
}
