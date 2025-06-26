<?php

declare(strict_types=1);

namespace App\Service\Security\Event;

use App\Entity\User;

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
