<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
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
        private readonly RequestStack $requestStack,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            // No user, no permission
            return false;
        }

        return count($this->findMatches($prefix, $permission)) > 0;
    }

    /**
     * @return Entry[]
     */
    protected function findMatches(string $prefix, string $permission): array
    {
        $matches = [];

        foreach ($this->entries as $entry) {
            // Skip if we don't match the route
            if ($entry->getPrefix() != $prefix) {
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
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \RuntimeException('No request available.');
        }

        /** @var Entry[] $entries */
        $entries = $request->attributes->get(AuthorizationMatrix::AUTH_MATRIX_ATTRIB);
        foreach ($entries as $entry) {
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
}
