<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Service\Security\Authorization\AuthorizationMatrix;

readonly class DossierFactory
{
    public function __construct(
        private DossierTypeManager $dossierTypeManager,
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    public function create(DossierType $dossierType): AbstractDossier
    {
        $config = $this->dossierTypeManager->getConfigWithAccessCheck($dossierType);

        $dossier = new ($config->getEntityClass());
        $dossier->setOrganisation($this->authorizationMatrix->getActiveOrganisation());

        return $dossier;
    }
}
