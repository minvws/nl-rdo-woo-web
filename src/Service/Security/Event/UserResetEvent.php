<?php

declare(strict_types=1);

namespace Shared\Service\Security\Event;

use Shared\Service\Security\User;

readonly class UserResetEvent
{
    public function __construct(
        public User $user,
        public ?User $actor,
        public bool $resetPassword,
        public bool $resetTwoFactorAuth,
    ) {
    }
}
