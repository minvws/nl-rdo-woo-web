<?php

declare(strict_types=1);

namespace Shared\Service\Security\Authorization;

use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\OrganisationSwitcher;
use Shared\Service\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_any;
use function count;

class AuthorizationMatrix
{
    public const string AUTH_MATRIX_ATTRIB = 'auth_matrix';

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
        return $this->security->getUser() instanceof UserInterface && count($this->findMatches($prefix, $permission)) > 0;
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
        return array_any($this->entryStore->getEntries(), fn ($entry) => $this->entryMatchesFilter($entry, $filter));
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
