<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Entity\User;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait DossierAuthorizationTrait
{
    protected function testIfDossierIsAllowedByUser(User $user, Dossier $dossier): void
    {
        // If we need to filter on organisation, make sure the current user is allowed to access this dossier
        if (
            $this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)
            && ! in_array($dossier->getDocumentPrefix(), $user->getOrganisation()->getPrefixesAsArray())
        ) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }

        // If we need to filter on published, make sure the current user is allowed to access this dossier
        if (
            $this->isPublished($dossier)
            && ! $this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_PUBLISHED_DOSSIERS)
        ) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }

        // If we need to filter on unpublished, make sure the current user is allowed to access this dossier
        if (
            $this->isUnpublished($dossier)
            && ! $this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_UNPUBLISHED_DOSSIERS)
        ) {
            throw new AccessDeniedHttpException('You are not allowed to access this dossier');
        }
    }

    protected function isPublished(Dossier $dossier): bool
    {
        return $dossier->getStatus() == Dossier::STATUS_PUBLISHED
            || $dossier->getStatus() == Dossier::STATUS_PREVIEW
            || $dossier->getStatus() == Dossier::STATUS_RETRACTED
            || $dossier->getStatus() == Dossier::STATUS_SCHEDULED
        ;
    }

    protected function isUnpublished(Dossier $dossier): bool
    {
        return $dossier->getStatus() == Dossier::STATUS_CONCEPT;
    }
}
