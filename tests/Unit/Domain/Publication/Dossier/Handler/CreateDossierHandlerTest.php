<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use App\Domain\Publication\Dossier\Handler\CreateDossierHandler;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CreateDossierHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private CreateDossierHandler $handler;
    private MockInterface&DossierService $dossierService;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

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
        $annualReport = \Mockery::mock(AnnualReport::class);
        $annualReport->shouldReceive('getId')->andReturn($annualReportUuid);

        $this->dossierWorkflowManager->expects('applyTransition')->with($annualReport, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->expects('validateCompletion')->with($annualReport);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierCreatedEvent $message) use ($annualReportUuid) {
                self::assertEquals($annualReportUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new CreateDossierCommand($annualReport)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $annualReport = \Mockery::mock(AnnualReport::class);

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
