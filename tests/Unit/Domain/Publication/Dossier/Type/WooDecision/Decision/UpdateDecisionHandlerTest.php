<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Decision;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\UpdateDecisionCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\UpdateDecisionHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
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

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $this->wooDecisionDispatcher = Mockery::mock(WooDecisionDispatcher::class);

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
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn($wooDecisionUuid);
        $wooDecision->shouldReceive('canProvideInventory')->andReturnFalse();

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_DECISION);

        $this->dossierService->expects('validateCompletion')->with($wooDecision);

        $this->wooDecisionDispatcher->expects('dispatchRemoveInventoryAndDocumentsCommand')->with($wooDecisionUuid);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DossierUpdatedEvent $message) use ($wooDecisionUuid) {
                self::assertEquals($wooDecisionUuid, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->handler->__invoke(
            new UpdateDecisionCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);

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
