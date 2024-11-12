<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\UpdateDecisionHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateDecisionHandlerTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateDecisionHandler $handler;
    private MockInterface&DossierService $dossierService;
    private MockInterface&WooDecisionDispatcher $wooDecisionDispatcher;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->wooDecisionDispatcher = \Mockery::mock(WooDecisionDispatcher::class);

        $this->handler = new UpdateDecisionHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
            $this->messageBus,
            $this->wooDecisionDispatcher
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $wooDecisionUuid = Uuid::v6();
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn($wooDecisionUuid);
        $wooDecision->shouldReceive('needsInventoryAndDocuments')->andReturnFalse();

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_DECISION);

        $this->dossierService->expects('validateCompletion')->with($wooDecision);

        $this->wooDecisionDispatcher->expects('dispatchRemoveInventoryAndDocumentsCommand')->with($wooDecisionUuid);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DossierUpdatedEvent $message) use ($wooDecisionUuid) {
                self::assertEquals($wooDecisionUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateDecisionCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($wooDecision, DossierStatusTransition::UPDATE_DECISION)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateDecisionCommand($wooDecision)
        );
    }
}
