<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ProductionReportProcessRunCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use Shared\Service\Inventory\InventoryRunProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProductionReportProcessRunHandler
{
    public function __construct(
        private ProductionReportProcessRunRepository $repository,
        private LoggerInterface $logger,
        private InventoryRunProcessor $runProcessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ProductionReportProcessRunCommand $message): void
    {
        $run = $this->repository->find($message->getUuid());
        if ($run === null) {
            $this->logger->warning('No ProductionReportProcessRun found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        if (! $run->isPending() && ! $run->isConfirmed()) {
            $this->logger->warning('ProductionReportProcessRun cannot be executed', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        try {
            $this->runProcessor->process($run);
            $this->entityManager->clear();
            unset($run);
        } catch (Exception $exception) {
            $this->logger->error('Exception while processing ProductionReportProcessRun', ['exception' => $exception]);

            return;
        }
    }
}
