<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\FileProvider;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\EntityWithFileInfo;
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
        return $this->getEntity($type, $dossier, $id, ApplicationId::PUBLIC);
    }

    public function getEntityForAdminUse(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
    ): EntityWithFileInfo {
        return $this->getEntity($type, $dossier, $id, ApplicationId::ADMIN);
    }

    private function getEntity(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
        ApplicationId $applicationId,
    ): EntityWithFileInfo {
        foreach ($this->providers as $provider) {
            if ($provider->getType() === $type) {
                return $applicationId->isAdmin()
                    ? $provider->getEntityForAdminUse($dossier, $id)
                    : $provider->getEntityForPublicUse($dossier, $id);
            }
        }

        throw DossierFileProviderException::forNoProviderAvailable($type);
    }
}
