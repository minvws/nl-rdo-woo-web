<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\FileProvider;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class DossierFileProviderManager
{
    /**
     * @param iterable<DossierFileProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('woo_platform.publication.dossier.file_provider')]
        private iterable $providers,
    ) {
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
