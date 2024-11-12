<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveInventoryCommand;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use App\Service\Inventory\InventoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RemoveInventoryHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private InventoryService $inventoryService,
        private DossierService $dossierService,
    ) {
    }

    public function __invoke(RemoveInventoryCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->inventoryService->removeInventories($command->dossier);

        $this->dossierService->validateCompletion($command->dossier);
    }
}
