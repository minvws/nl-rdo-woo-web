<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (! $user instanceof User) {
            throw new CustomUserMessageAccountStatusException('Invalid user account');
        }

        if ($user->isDisabled()) {
            throw new CustomUserMessageAccountStatusException('User account is disabled');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (! $user instanceof User) {
            throw new AccessDeniedException();
        }

        if ($user->isDisabled()) {
            throw new AccessDeniedException();
        }
    }
}
