<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;

readonly class ProductionReportDossierFileProvider implements DossierFileProviderInterface
{
    /**
     * @codeCoverageIgnore
     */
    public function getType(): DossierFileType
    {
        return DossierFileType::PRODUCTION_REPORT;
    }

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // A ProductionReport should never be available on the public site
        throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        if (! $dossier instanceof WooDecision) {
            throw DossierFileNotFoundException::forDossierTypeMismatch($this->getType(), $dossier);
        }

        $productionReport = $dossier->getProductionReport();
        if ($productionReport === null) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
        }

        return $productionReport;
    }
}
