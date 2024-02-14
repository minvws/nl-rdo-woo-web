<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\AuthorizationMatrix;
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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, self::MARKER . '.');
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

        return true;
    }
}
