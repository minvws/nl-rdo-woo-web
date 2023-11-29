<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\EntityWithFileInfo;
use App\Entity\Inventory;

class DossierInventoryDataProvider implements InventoryDataProviderInterface
{
    public function __construct(
        private readonly Dossier $dossier,
        /** @var Document[] $documents */
        private readonly array $documents,
    ) {
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): iterable
    {
        return $this->documents;
    }

    public function getInventoryEntity(): EntityWithFileInfo
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
