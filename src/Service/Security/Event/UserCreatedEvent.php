<?php

declare(strict_types=1);

namespace Shared\Service\Security\Event;

use Shared\Service\Security\User;

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
