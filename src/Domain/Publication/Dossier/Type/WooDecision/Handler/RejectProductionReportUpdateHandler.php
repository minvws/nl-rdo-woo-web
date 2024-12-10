<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RejectProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Exception\ProductionReportUpdaterException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RejectProductionReportUpdateHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private ProductionReportProcessRunRepository $processRunRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RejectProductionReportUpdateCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run = $command->dossier->getProcessRun();
        if (! $run) {
            throw ProductionReportUpdaterException::forNoRunFound();
        }

        try {
            $run->reject();
            $this->processRunRepository->save($run, true);
        } catch (\RuntimeException) {
            $this->logger->warning(sprintf(
                'Could not reject ProductionReportProcessRun %s with status %s',
                $run->getId(),
                $run->getStatus(),
            ));
        }
    }
}
