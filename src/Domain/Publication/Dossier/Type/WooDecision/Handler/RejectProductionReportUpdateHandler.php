<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RejectProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Exception\InventoryUpdaterException;
use App\Repository\ProductionReportProcessRunRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RejectProductionReportUpdateHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private ProductionReportProcessRunRepository $processRunRepository,
    ) {
    }

    public function __invoke(RejectProductionReportUpdateCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run = $command->dossier->getProcessRun();
        if (! $run) {
            throw InventoryUpdaterException::forNoRunFound();
        }

        $run->reject();

        $this->processRunRepository->save($run, true);
    }
}
