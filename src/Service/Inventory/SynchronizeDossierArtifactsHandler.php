<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
readonly class SynchronizeDossierArtifactsHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private LoggerInterface $logger,
        private InventoryUpdater $inventoryUpdater,
    ) {
    }

    public function __invoke(SynchronizeDossierArtifactsCommand $command): void
    {
        $dossier = $this->dossierRepository->find($command->getUuid());
        if (! $dossier instanceof AbstractDossier) {
            $this->logger->warning(sprintf('No dossier found in %s', self::class), [
                'uuid' => $command->getUuid(),
            ]);

            return;
        }

        if (! $dossier instanceof WooDecision) {
            return;
        }

        $this->inventoryUpdater->updateWooDecisionInventories($dossier);
    }
}
