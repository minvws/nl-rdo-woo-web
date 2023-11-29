<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\InventoryProcessRunMessage;
use App\Repository\InventoryProcessRunRepository;
use App\Service\Inventory\InventoryRunProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Process the inventory file for a dossier.
 */
#[AsMessageHandler]
class InventoryProcessRunHandler
{
    public function __construct(
        private readonly InventoryProcessRunRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly InventoryRunProcessor $runProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(InventoryProcessRunMessage $message): void
    {
        $run = $this->repository->find($message->getUuid());
        if ($run === null) {
            // No run found for this message
            $this->logger->warning('No processInventoryRun found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        if (! $run->isPending() && ! $run->isConfirmed()) {
            // No run found for this message
            $this->logger->warning('Inventory process run cannot be executed', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        try {
            $this->runProcessor->process($run);
            $this->entityManager->clear();
            unset($run);
        } catch (\Exception $exception) {
            $this->logger->error('Exception while processing inventory', ['exception' => $exception]);

            return;
        }
    }
}
