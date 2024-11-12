<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\GenerateInventoryCommand;
use App\Repository\WooDecisionRepository;
use App\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateInventoryHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private InventoryDataProviderFactory $inventoryDataProviderFactory,
        private LoggerInterface $logger,
        private InventorySanitizer $inventorySanitizer,
    ) {
    }

    public function __invoke(GenerateInventoryCommand $message): void
    {
        $wooDecision = $this->wooDecisionRepository->find($message->getUuid());
        if (! $wooDecision) {
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        $this->inventorySanitizer->generateSanitizedInventory(
            $this->inventoryDataProviderFactory->forWooDecision($wooDecision),
        );
    }
}
