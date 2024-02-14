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
    public const FILTER_ORGANISATION_ONLY = 'org_only';
    public const FILTER_PUBLISHED_DOSSIERS = 'published_dossiers';
    public const FILTER_UNPUBLISHED_DOSSIERS = 'unpublished_dossiers';

    /**
     * @param Entry[] $entries
     */
    public function __construct(
        private readonly Security $security,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly OrganisationSwitcher $organisationSwitcher,
        private readonly AuthorizationEntryRequestStore $entryStore,
        private readonly array $entries
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

    public function getFilter(string $filter): bool
    {
        foreach ($this->entryStore->getEntries() as $entry) {
            switch ($filter) {
                case self::FILTER_ORGANISATION_ONLY:
                    if ($entry->getFilters()['organisation_only'] ?? false) {
                        return true;
                    }
                    break;
                case self::FILTER_PUBLISHED_DOSSIERS:
                    if ($entry->getFilters()['published_dossiers'] ?? false) {
                        return true;
                    }
                    break;
                case self::FILTER_UNPUBLISHED_DOSSIERS:
                    if ($entry->getFilters()['unpublished_dossiers'] ?? false) {
                        return true;
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unknown filter "%s".', $filter));
            }
        }

        return false;
    }

    public function getActiveOrganisation(): Organisation
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            throw new \RuntimeException('No active user to get active organisation for');
        }

        return $this->organisationSwitcher->getActiveOrganisation($user);
    }
}
