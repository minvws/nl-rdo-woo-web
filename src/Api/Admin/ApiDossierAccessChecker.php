<?php

declare(strict_types=1);

namespace Shared\Api\Admin;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

readonly class ApiDossierAccessChecker
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function ensureUserIsAllowedToUpdateDossier(Uuid $dossierId): void
    {
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        if (! $this->authorizationChecker->isGranted('AuthMatrix.dossier.update', $dossier)) {
            throw new AccessDeniedException();
        }
    }
}
