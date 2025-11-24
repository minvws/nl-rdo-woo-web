<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\GenerateInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Shared\Service\Inventory\Sanitizer\InventorySanitizer;
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
