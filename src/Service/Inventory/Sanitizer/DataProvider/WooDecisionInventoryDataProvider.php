<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer\DataProvider;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class WooDecisionInventoryDataProvider implements InventoryDataProviderInterface
{
    public function __construct(
        private WooDecision $dossier,
        /** @var array<array-key, Document> $documents */
        private array $documents,
    ) {
    }

    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function getInventoryEntity(): Inventory
    {
        $inventory = $this->dossier->getInventory();
        if (! $inventory) {
            $inventory = new Inventory();
            $inventory->setDossier($this->dossier);
        }

        return $inventory;
    }

    public function getFilename(): string
    {
        return 'inventarislijst-' . $this->dossier->getDossierNr();
    }
}
