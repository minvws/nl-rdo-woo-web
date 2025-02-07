<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Service\Security\Authorization\AuthorizationMatrix;

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
