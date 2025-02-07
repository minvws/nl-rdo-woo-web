<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\FileProvider;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\EntityWithFileInfo;

readonly class InventoryDossierFileProvider implements DossierFileProviderInterface
{
    /**
     * @codeCoverageIgnore
     */
    public function getType(): DossierFileType
    {
        return DossierFileType::INVENTORY;
    }

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        if (! $dossier instanceof WooDecision) {
            throw DossierFileNotFoundException::forDossierTypeMismatch($this->getType(), $dossier);
        }

        $inventory = $dossier->getInventory();
        if ($inventory === null) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
        }

        return $inventory;
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // No additional checks needed
        return $this->getEntityForPublicUse($dossier, $id);
    }
}
