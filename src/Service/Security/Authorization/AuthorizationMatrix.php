<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

use App\Entity\Organisation;
use App\Entity\User;
use App\Service\Security\OrganisationSwitcher;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationMatrix
{
    public const AUTH_MATRIX_ATTRIB = 'auth_matrix';

    /**
     * @param Entry[] $entries
     */
    public function __construct(
        private readonly Security $security,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly OrganisationSwitcher $organisationSwitcher,
        private readonly AuthorizationEntryRequestStore $entryStore,
        private readonly array $entries,
    ) {
    }

    /**
     * @return Entry[]
     */
    public function getAuthorizedMatches(string $prefix, string $permission): array
    {
        return $this->findMatches($prefix, $permission);
    }

    public function isAuthorized(string $prefix, string $permission): bool
    {
        return $this->security->getUser() instanceof User && count($this->findMatches($prefix, $permission)) > 0;
    }

    /**
     * @return Entry[]
     */
    protected function findMatches(string $prefix, string $permission): array
    {
        $matches = [];

        foreach ($this->entries as $entry) {
            // Skip if we don't match the route
            if ($entry->getPrefix() !== $prefix) {
                continue;
            }

            // Check roles
            foreach ($entry->getRoles() as $role) {
                if (! $this->authorizationChecker->isGranted($role)) {
                    continue;
                }

                // Check permissions
                $permissions = $entry->getPermissions();
                if (! isset($permissions[$permission]) || ! $permissions[$permission]) {
                    continue;
                }

                $matches[] = $entry;
            }
        }

        return $matches;
    }

    public function hasFilter(AuthorizationMatrixFilter $filter): bool
    {
        foreach ($this->entryStore->getEntries() as $entry) {
            if ($this->entryMatchesFilter($entry, $filter)) {
                return true;
            }
        }

        return false;
    }

    private function entryMatchesFilter(Entry $entry, AuthorizationMatrixFilter $filter): bool
    {
        $matches = false;

        switch ($filter) {
            case AuthorizationMatrixFilter::ORGANISATION_ONLY:
                if ($entry->getFilters()['organisation_only'] ?? false) {
                    $matches = true;
                }
                break;
            case AuthorizationMatrixFilter::PUBLISHED_DOSSIERS:
                if ($entry->getFilters()['published_dossiers'] ?? false) {
                    $matches = true;
                }
                break;
            case AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS:
                if ($entry->getFilters()['unpublished_dossiers'] ?? false) {
                    $matches = true;
                }
                break;
            default:
                throw AuthorizationMatrixException::forUnknownFilter($filter);
        }

        return $matches;
    }

    public function getActiveOrganisation(): Organisation
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            throw AuthorizationMatrixException::forNoActiveUser();
        }

        return $this->organisationSwitcher->getActiveOrganisation($user);
    }
}
