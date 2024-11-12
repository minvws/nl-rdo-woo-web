<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\RemoveInventoryHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use App\Service\Inventory\InventoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class RemoveInventoryHandlerTest extends UnitTestCase
{
    private InventoryService&MockInterface $inventoryService;
    private DossierService&MockInterface $dossierService;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private RemoveInventoryHandler $handler;

    public function setUp(): void
    {
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->inventoryService = \Mockery::mock(InventoryService::class);
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->handler = new RemoveInventoryHandler(
            $this->dossierWorkflowManager,
            $this->inventoryService,
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);
        $this->inventoryService->expects('removeInventories')->with($wooDecision);
        $this->dossierService->expects('validateCompletion')->with($wooDecision);

        $this->handler->__invoke(
            new RemoveInventoryCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new RemoveInventoryCommand($wooDecision)
        );
    }
}
