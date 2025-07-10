<?php

declare(strict_types=1);

namespace App\Service\Security\Event;

use App\Service\Security\User;

readonly class UserCreatedEvent
{
    public function __construct(
        public User $user,
        public ?User $actor,
        /** @var array<string> */
        public array $roles,
    ) {
    }
}
