<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\FileProvider;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\EntityWithFileInfo;
use App\Enum\ApplicationMode;

class DossierFileProviderManager
{
    /**
     * @var iterable<DossierFileProviderInterface>
     */
    private iterable $providers;

    /**
     * @param iterable<DossierFileProviderInterface> $providers
     */
    public function __construct(
        iterable $providers,
    ) {
        $this->providers = $providers;
    }

    public function getEntityForPublicUse(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
    ): EntityWithFileInfo {
        return $this->getEntity($type, $dossier, $id, ApplicationMode::PUBLIC);
    }

    public function getEntityForAdminUse(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
    ): EntityWithFileInfo {
        return $this->getEntity($type, $dossier, $id, ApplicationMode::ADMIN);
    }

    private function getEntity(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
        ApplicationMode $mode,
    ): EntityWithFileInfo {
        foreach ($this->providers as $provider) {
            if ($provider->getType() === $type) {
                return $mode === ApplicationMode::ADMIN
                    ? $provider->getEntityForAdminUse($dossier, $id)
                    : $provider->getEntityForPublicUse($dossier, $id);
            }
        }

        throw DossierFileProviderException::forNoProviderAvailable($type);
    }
}
