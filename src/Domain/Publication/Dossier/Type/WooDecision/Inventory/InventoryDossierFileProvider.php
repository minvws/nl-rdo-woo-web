<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;

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
