<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AuthMatrixVoter extends Voter
{
    private const MARKER = 'AuthMatrix';

    public function __construct(
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly AuthorizationEntryRequestStore $entryStore,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (! str_starts_with($attribute, self::MARKER . '.')) {
            return false;
        }

        return $subject === null || $subject instanceof AbstractDossier;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $parts = explode('.', $attribute, 3);
        if (count($parts) !== 3) {
            return false;
        }

        [$marker, $prefix, $permission] = $parts;
        if ($marker !== self::MARKER) {
            return false;
        }

        if (! $this->authorizationMatrix->isAuthorized($prefix, $permission)) {
            return false;
        }

        $this->entryStore->storeEntries(...$this->authorizationMatrix->getAuthorizedMatches($prefix, $permission));

        if ($subject instanceof AbstractDossier) {
            return $this->isDossierAllowedForUser($subject);
        }

        return true;
    }

    private function isDossierAllowedForUser(AbstractDossier $dossier): bool
    {
        if ($dossier->getOrganisation() !== $this->authorizationMatrix->getActiveOrganisation()) {
            return false;
        }

        // If we need to filter on published, make sure the current user is allowed to access this dossier
        if (
            ! $dossier->getStatus()->isNewOrConcept()
            && ! $this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
        ) {
            return false;
        }

        // If we need to filter on unpublished, make sure the current user is allowed to access this dossier
        if (
            $dossier->getStatus()->isNewOrConcept()
            && ! $this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
        ) {
            return false;
        }

        return true;
    }
}
