<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ConfirmProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Exception\ProductionReportUpdaterException;
use App\Service\HistoryService;
use App\Service\Utils\Utils;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ConfirmProductionReportUpdateHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private ProductionReportProcessRunRepository $processRunRepository,
        private HistoryService $historyService,
        private ProductionReportDispatcher $dispatcher,
    ) {
    }

    public function __invoke(ConfirmProductionReportUpdateCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run = $command->dossier->getProcessRun();
        if (! $run) {
            throw ProductionReportUpdaterException::forNoRunFound();
        }

        $run->confirm();
        $this->processRunRepository->save($run, true);

        $fileInfo = $run->getFileInfo();
        $this->historyService->addDossierEntry($command->dossier, 'dossier_update_inventory', [
            'filetype' => $fileInfo->getType(),
            'filename' => $fileInfo->getName(),
            'filesize' => Utils::getFileSize($run),
        ]);

        $this->dispatcher->dispatchProductionReportProcessRunCommand($run->getId());
    }
}
