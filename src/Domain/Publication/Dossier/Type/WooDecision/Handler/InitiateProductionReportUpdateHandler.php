<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\InitiateProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Upload\FileType\FileType;
use App\Exception\ProcessInventoryException;
use App\Exception\ProductionReportUpdaterException;
use App\Service\Storage\EntityStorageService;
use App\SourceType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class InitiateProductionReportUpdateHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private ProductionReportProcessRunRepository $processRunRepository,
        private EntityStorageService $entityStorage,
        private LoggerInterface $logger,
        private ProductionReportDispatcher $dispatcher,
    ) {
    }

    public function __invoke(InitiateProductionReportUpdateCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->removeExistingProcessRun($command);

        $run = $this->processRunRepository->create($command->dossier);
        $file = $run->getFileInfo();
        $file->setSourceType(SourceType::SPREADSHEET);
        $file->setType(FileType::XLS->value);
        $file->setName($command->upload->getClientOriginalName());

        $this->processRunRepository->save($run, true);

        if (! $this->entityStorage->storeEntity($command->upload, $run)) {
            $this->logger->error('Could not store the production report spreadsheet.', [
                'dossier' => $command->dossier->getId()->toRfc4122(),
                'filename' => $command->upload->getClientOriginalName(),
            ]);

            $run->addGenericException(ProcessInventoryException::forInventoryCannotBeStored());
            $run->fail();

            $this->processRunRepository->save($run, true);

            throw ProductionReportUpdaterException::forUploadCannotBeStored();
        }

        $this->dispatcher->dispatchProductionReportProcessRunCommand($run->getId());
    }

    private function removeExistingProcessRun(InitiateProductionReportUpdateCommand $command): void
    {
        $existingRun = $command->dossier->getProcessRun();
        if ($existingRun === null) {
            return;
        }

        if ($existingRun->isNotFinal()) {
            throw ProductionReportUpdaterException::forExistingRunIsNotFinal();
        }

        $this->entityStorage->removeFileForEntity($existingRun);
        $this->processRunRepository->remove($existingRun, true);
    }
}
