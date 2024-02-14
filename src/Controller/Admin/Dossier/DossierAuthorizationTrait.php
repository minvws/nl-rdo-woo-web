<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait DossierAuthorizationTrait
{
    protected function testIfDossierIsAllowedByUser(Dossier $dossier): void
    {
        // Filter on active organisation
        if ($dossier->getOrganisation() !== $this->authorizationMatrix->getActiveOrganisation()) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }

        // If we need to filter on published, make sure the current user is allowed to access this dossier
        if (
            ! $dossier->getStatus()->isNewOrConcept()
            && ! $this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_PUBLISHED_DOSSIERS)
        ) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }

        // If we need to filter on unpublished, make sure the current user is allowed to access this dossier
        if (
            $dossier->getStatus()->isNewOrConcept()
            && ! $this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_UNPUBLISHED_DOSSIERS)
        ) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }
    }
}
