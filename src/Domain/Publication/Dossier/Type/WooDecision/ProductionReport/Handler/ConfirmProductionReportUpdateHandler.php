<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ConfirmProductionReportUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Exception\ProductionReportUpdaterException;
use Shared\Service\HistoryService;
use Shared\Service\Utils\Utils;
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
        $this->historyService->addDossierEntry($command->dossier->getId(), 'dossier_update_inventory', [
            'filetype' => $fileInfo->getType(),
            'filename' => $fileInfo->getName(),
            'filesize' => Utils::getFileSize($run),
        ]);

        $this->dispatcher->dispatchProductionReportProcessRunCommand($run->getId());
    }
}
