<?php

declare(strict_types=1);

namespace App\Service\Security\Event;

use App\Entity\User;
use MinVWS\AuditLogger\Contracts\LoggableUser;

readonly class UserUpdatedEvent
{
    public function __construct(
        public User $oldUser,
        public User $updatedUser,
        public LoggableUser $actor,
    ) {
    }
}
